<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SerialNumber;
use Illuminate\Http\Request;

class SerialNumberController extends Controller
{
    /**
     * Get serial number details by serial number string
     * Used for material checking before picking from warehouse
     */
    public function getBySerialNumber(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string',
        ]);

        $serialNumber = $this->findSerialNumberForMobileCheck($request->serial_number);

        if (!$serialNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Serial number tidak ditemukan'
            ], 404);
        }

        // Validate SN status is ready
        if (!in_array($serialNumber->status, ['ready', 'available'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Serial Number {$serialNumber->serial_number} tidak dapat diverifikasi karena status saat ini: " . strtoupper($serialNumber->status),
                'code' => 'INVALID_STATUS'
            ], 400);
        }

        if (! $serialNumber->can_install) {
            return response()->json([
                'status' => 'error',
                'message' => "Serial Number {$serialNumber->serial_number} dalam kondisi {$serialNumber->condition_label}. Tidak boleh dipasang.",
                'code' => 'INVALID_CONDITION',
                'data' => [
                    'serial_number' => $serialNumber->serial_number,
                    'condition_status' => $serialNumber->effective_condition_status,
                    'condition_label' => $serialNumber->condition_label,
                    'can_install' => false,
                    'install_block_reason' => $serialNumber->install_block_reason,
                ],
            ], 400);
        }
        
        // Validate SN not already used in unit on wall (Double check)
        $unitOnWall = \App\Models\UnitOnWall::where('serial_number_id', $serialNumber->id)
            ->where('status', 'active')
            ->first();
        
        if ($unitOnWall) {
            return response()->json([
                'status' => 'error',
                'message' => "Serial Number {$serialNumber->serial_number} sedang terpasang di lokasi (Unit On Wall)",
                'code' => 'IN_USE_UNIT'
            ], 400);
        }

        $product = $serialNumber->masterProduct;
        
        // Get primary photo or first photo
        $productPhoto = null;
        if ($product->primaryPhoto) {
            $productPhoto = $product->primaryPhoto->photo_path;
        } elseif ($product->product_photos && is_array($product->product_photos) && count($product->product_photos) > 0) {
            // Get first photo from product_photos array
            $firstPhoto = $product->product_photos[0];
            $productPhoto = is_array($firstPhoto) ? ($firstPhoto['url'] ?? null) : $firstPhoto;
        }

        // Get packaging size
        $packagingSize = null;
        if ($product->packagingSize) {
            $packagingSize = $product->packagingSize->name ?? $product->packagingSize->code;
        } elseif ($product->packaging_size) {
            $packagingSize = $product->packaging_size;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $serialNumber->id, // Serial Number ID
                'serial_number' => $serialNumber->serial_number,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_code' => $product->sku ?? '-',
                'product_type' => $product->productType->name ?? '-',
                'packaging_size' => $packagingSize,
                'photo' => $productPhoto ? asset($productPhoto) : null,
                'warehouse' => $serialNumber->warehouse->name ?? '-',
                'branch' => $serialNumber->warehouse?->branch?->name ?? '-',
                'status' => $serialNumber->status,
                'condition_status' => $serialNumber->effective_condition_status,
                'condition_label' => $serialNumber->condition_label,
                'can_install' => $serialNumber->can_install,
                'install_block_reason' => $serialNumber->install_block_reason,
            ]
        ]);
    }

    private function findSerialNumberForMobileCheck(string $serialNumber): ?SerialNumber
    {
        $normalizedSerial = trim($serialNumber);
        $relations = [
            'masterProduct.productCategory',
            'masterProduct.productType',
            'masterProduct.packagingSize',
            'masterProduct.primaryPhoto',
            'warehouse.branch',
        ];

        $baseQuery = SerialNumber::with($relations)
            ->whereRaw('serial_number = ?', [$normalizedSerial]);

        $readyWarehouseSerial = (clone $baseQuery)
            ->whereIn('status', ['ready', 'available'])
            ->where(function ($query) {
                $query->whereNull('location_type')
                    ->orWhere('location_type', 'warehouse');
            })
            ->orderBy('id')
            ->first();

        if ($readyWarehouseSerial) {
            return $readyWarehouseSerial;
        }

        return $baseQuery
            ->orderByRaw("CASE WHEN status IN ('ready', 'available') THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }
}
