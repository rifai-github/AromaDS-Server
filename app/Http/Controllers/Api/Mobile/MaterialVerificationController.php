<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\JobSchedule;
use App\Models\InventoryIssuing;
use App\Models\InventoryIssuingItem;
use App\Services\Warehouse\SerialNumberIssuingLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaterialVerificationController extends Controller
{
    /**
     * Verify and confirm material pickup from warehouse
     * 
     * Flow:
     * 1. User di mobile app ceklis material yang diambil
     * 2. Submit verification
     * 3. Update InventoryIssuing: received_by = current user, status = processed
     * 4. Update JobSchedule: status = barang_diambil
     * 5. Enable "Tiba di Lokasi" button in mobile app
     */
    public function verifyMaterials(Request $request, $jobScheduleId)
    {
        try {
            $request->validate([
                'inventory_issuing_id' => 'required|exists:inventory_issuings,id',
                'materials' => 'required|array',
                'materials.*.item_id' => 'required|exists:inventory_issuing_items,id',
                'materials.*.quantity_received' => 'required|numeric|min:0',
                'materials.*.verified' => 'required|boolean',
                'materials.*.serial_number' => 'nullable|string', // SN yang di-scan (optional)
                'materials.*.serial_number_id' => 'nullable|exists:serial_numbers,id', // ID SN yang di-scan (optional)
            ]);

            DB::beginTransaction();

            // Get job schedule
            $jobSchedule = JobSchedule::findOrFail($jobScheduleId);

            if (in_array(strtolower(trim((string) $jobSchedule->type)), ['remove', 'remove_free', 'remove free'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job remove tidak memerlukan verifikasi material. Unit diambil dari Unit On Wall.',
                    'code' => 'MATERIAL_NOT_REQUIRED',
                ], 422);
            }
            
            // Get inventory issuing
            $inventoryIssuing = InventoryIssuing::with('items')->findOrFail($request->inventory_issuing_id);
            
            // Verify that this inventory issuing belongs to this job
            if ($inventoryIssuing->reference_no !== $jobSchedule->material_issue_number) {
                // Try to find by job schedule relation
                // Check if reference_no matches any material issue for this job
                $materialIssue = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($jobSchedule) {
                    $q->where('job_schedule_id', $jobSchedule->id);
                })->first();
                
                if (!$materialIssue || $inventoryIssuing->reference_no !== $materialIssue->issue_number) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Inventory issuing tidak sesuai dengan job schedule ini'
                    ], 400);
                }
            }
            
            // Check if already verified
            // Status legend:
            // - pending: Un Prepare (Belum disiapkan gudang)
            // - processed: Ready (Sudah disiapkan gudang, siap diambil teknisi)
            // - sent: Finish (Sudah diverifikasi/diambil teknisi)
            // - received: Received (Sudah diterima - flow gudang ke gudang)
            
            // Only 'sent' and 'received' mean it's actually finished verification/pickup.
            // 'processed' means warehouse is DONE, but technician is JUST STARTING verification.
            if (in_array($inventoryIssuing->status, ['sent', 'received'])) {
                // Fix: If already verified but job status is 'assign_material' or 'barang_siap_diambil' (legacy/out-of-sync success),
                // auto-fix it to 'barang_diambil' so mobile app can proceed.
                if (in_array($jobSchedule->status, ['assign_material', 'barang_siap_diambil', 'barang_dipersiapkan', 'teknisi_tiba_dilokasi'])) {
                    $relatedJobs = \App\Models\JobSchedule::whereHas('jobAssignSchedules.jobAssignMaterialIssues.materialIssue', function($q) use ($inventoryIssuing) {
                        $q->where('issue_number', $inventoryIssuing->reference_no);
                    })->get();

                    if ($relatedJobs->isEmpty()) {
                        $relatedJobs = collect([$jobSchedule]);
                    }

                    foreach ($relatedJobs as $relatedJob) {
                        if (in_array($relatedJob->status, ['assign_material', 'barang_siap_diambil', 'barang_dipersiapkan', 'teknisi_tiba_dilokasi'])) {
                            $relatedJob->update([
                                'status' => 'barang_diambil',
                                'material_checked' => true,
                                'material_checked_at' => $inventoryIssuing->received_at ?? now(),
                                'updated_by' => auth()->id(),
                            ]);
                        }
                    }
                    
                    Log::info("Auto-repaired job status to 'barang_diambil' during re-verification attempt.");
                    
                    DB::commit();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Material verifikasi berhasil diperbarui (Auto-repair status)',
                        'data' => $jobSchedule
                    ]);
                }
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material sudah pernah diverifikasi sebelumnya'
                ], 400);
            }
            
            // Fix: Check and correct status inconsistency
            // If job schedule status is 'assign_material' but inventory issuing is still 'pending',
            // this means status was incorrectly updated. Fix it by reverting job schedule status.
            if (($jobSchedule->status === 'barang_diambil') && $inventoryIssuing->status === 'pending') {
                Log::warning("Status inconsistency detected: Job Schedule {$jobSchedule->job_number} is '{$jobSchedule->status}' but Inventory Issuing {$inventoryIssuing->issuing_number} is still 'pending'. Reverting job schedule status to 'barang_dipersiapkan'.");
                
                $relatedJobs = \App\Models\JobSchedule::whereHas('jobAssignSchedules.jobAssignMaterialIssues.materialIssue', function($q) use ($inventoryIssuing) {
                    $q->where('issue_number', $inventoryIssuing->reference_no);
                })->get();

                if ($relatedJobs->isEmpty()) {
                    $relatedJobs = collect([$jobSchedule]);
                }

                foreach ($relatedJobs as $relatedJob) {
                    $relatedJob->update([
                        'status' => 'barang_dipersiapkan',
                        'material_checked' => false,
                        'material_checked_at' => null,
                        'updated_by' => auth()->id(),
                    ]);
                }
                
                Log::info("Job schedule {$jobSchedule->job_number} status reverted to 'barang_dipersiapkan' to fix inconsistency.");
            }

            if ($inventoryIssuing->status !== 'processed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material belum Ready to Issue. Harap tunggu gudang memproses Inventory Issuing terlebih dahulu.'
                ], 400);
            }
            
            // Verify all materials are checked
            $allVerified = true;
            foreach ($request->materials as $material) {
                if (!$material['verified']) {
                    $allVerified = false;
                    break;
                }
            }
            
            if (!$allVerified) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Semua material harus diceklis sebelum verifikasi'
                ], 400);
            }
            
            // Update inventory issuing items with received quantities and link serial numbers
            foreach ($request->materials as $material) {
                $item = InventoryIssuingItem::findOrFail($material['item_id']);
                
                // GROUPING FIX: Find all items with the same product in this issuing 
                // to distribute the quantity if grouped items were sent as one
                $siblingItems = InventoryIssuingItem::where('inventory_issuing_id', $inventoryIssuing->id)
                    ->where('product_id', $item->product_id)
                    ->get();
                
                $totalReceivedToDistribute = $material['quantity_received'];
                
                foreach ($siblingItems as $sItem) {
                    // How much to give this specific item?
                    // Typically, we give what was issued, up to the remaining total to distribute
                    $take = min($sItem->quantity_issued, $totalReceivedToDistribute);
                    
                    // Prepare update data
                    $updateData = [
                        'quantity_received' => $take,
                        'updated_by' => auth()->id(),
                    ];
                    
                    // Subtract from the pool
                    $totalReceivedToDistribute -= $take;
                    
                    // Get room_name for this item (for logging and validation)
                    $itemRoomName = null;
                    // Try extract from notes first
                    if ($sItem->notes && preg_match('/Room:\s*([^\s,]+)/i', $sItem->notes, $matches)) {
                        $itemRoomName = trim($matches[1]);
                    }
                    
                    // Link serial number if provided (Only for the first item that needs it or if matching room)
                    // Note: Serial Number in grouping is tricky, but usually technician scans one by one.
                    // If grouped, we apply the scanned SN to the first available slot for that product.
                    if (!empty($material['serial_number_id']) && $take > 0 && !$sItem->serial_number_id) {
                        $serialNumber = \App\Models\SerialNumber::find($material['serial_number_id']);
                        
                        if ($serialNumber && $serialNumber->master_product_id == $sItem->product_id) {
                            $serialLinkService = app(SerialNumberIssuingLinkService::class);

                            if (! $serialLinkService->isReadyInWarehouse($serialNumber)) {
                                DB::rollBack();

                                return response()->json([
                                    'status' => 'error',
                                    'message' => "Serial Number {$serialNumber->serial_number} tidak siap di warehouse atau masih aktif di Unit On Wall.",
                                    'code' => 'INVALID_SERIAL_NUMBER_STATE',
                                ], 400);
                            }

                            if ($serialLinkService->requiresExclusiveLink($serialNumber)) {
                                $serialLinkService->releaseStaleLinks($serialNumber, $sItem->id, auth()->id());

                                $preparedLink = $serialLinkService->findPreparedLink($serialNumber->id, $sItem->id);
                                if ($preparedLink) {
                                    DB::rollBack();

                                    return response()->json([
                                        'status' => 'error',
                                        'message' => "Serial Number {$serialNumber->serial_number} masih dipakai di Inventory Issuing {$preparedLink->inventoryIssuing?->issuing_number}. Tidak bisa dipakai di dua WI yang masih disiapkan.",
                                        'code' => 'SERIAL_NUMBER_RESERVED',
                                    ], 400);
                                }
                            }

                            // Link SN to issuing item
                            $updateData['serial_number_id'] = $serialNumber->id;
                            Log::info("Linking serial number {$serialNumber->serial_number} to inventory issuing item {$sItem->id}");
                        }
                    }
                    
                    $sItem->update($updateData);
                    
                    $logMessage = "Updated inventory issuing item {$sItem->id} (Grouped): quantity_received = {$take}, serial_number_id = " . ($updateData['serial_number_id'] ?? 'null');
                    if ($itemRoomName) {
                        $logMessage .= ", Room: {$itemRoomName}";
                    }
                    Log::info($logMessage);
                    
                    if ($totalReceivedToDistribute <= 0 && $siblingItems->count() > 1) {
                         // If we've distributed everything and there are more siblings, stop here for this product group
                         // But we continue the outer loop to process other product groups
                    }
                }
                
                // Safety check: if there's still quantity left after distributing to all known siblings
                if ($totalReceivedToDistribute > 0) {
                    Log::warning("Quantity mismatch: {$totalReceivedToDistribute} units of product {$item->product_id} left undistributed in Issuing {$inventoryIssuing->id}");
                }
            }
            
            // Update inventory issuing
            $inventoryIssuing->update([
                'received_by' => auth()->id(),
                'received_at' => now(),
                'status' => 'processed', // Changed from pending to processed (intermediate status)
                'updated_by' => auth()->id(),
            ]);
            
            Log::info("Inventory issuing {$inventoryIssuing->issuing_number} marked as processed by user " . auth()->id());
            
            // AUTO-FINALIZE: stock is posted once when WI reaches Ready (processed),
            // then pickup verification only moves the WI to sent/on-hand.
            $inventoryIssuing->load(['items.product', 'warehouse', 'branch']);

            app(\App\Services\Warehouse\InventoryIssuingService::class)
                ->postReadyStockIfMissing($inventoryIssuing);

            $inventoryIssuing->update([
                'status' => 'sent', // Final status: issued
                'updated_by' => auth()->id(),
            ]);

            // Requirement 1: Update SN status to 'on_hand' and location to 'technician'
            $serialNumberIds = $inventoryIssuing->items()
                ->whereNotNull('serial_number_id')
                ->pluck('serial_number_id')
                ->toArray();
            
            if (!empty($serialNumberIds)) {
                \App\Models\SerialNumber::whereIn('id', $serialNumberIds)->update([
                    'status' => 'on_hand',
                    'location_type' => 'technician',
                    'location_id' => $inventoryIssuing->received_by ?? auth()->id(),
                    'updated_by' => auth()->id()
                ]);
                Log::info("Serial Numbers updated to On Hand for Issuing ID: {$inventoryIssuing->id} via mobile verification");
            }
            
            Log::info("Inventory issuing {$inventoryIssuing->issuing_number} auto-finalized to 'sent' status after material verification");
            Log::info("Inventory issuing {$inventoryIssuing->issuing_number} auto-finalized successfully");
            
            // MOM9: Update ALL related job schedules for this material issue (Multiple rooms/grouped jobs)
            $materialIssueNum = $inventoryIssuing->reference_no;
            $allRelatedJobs = \App\Models\JobSchedule::whereHas('jobAssignSchedules.jobAssignMaterialIssues.materialIssue', function($q) use ($materialIssueNum) {
                $q->where('issue_number', $materialIssueNum);
            })->get();

            if ($allRelatedJobs->isEmpty()) {
                // Fallback to current job if query returns nothing (rare case)
                $allRelatedJobs = collect([$jobSchedule]);
            }

            foreach ($allRelatedJobs as $relatedJob) {
                // Semua tipe pekerjaan (CSR, IR, dll) -> barang_diambil setelah verifikasi material di gudang
                // Teknisi harus klik "Tiba di Lokasi" secara manual di aplikasi untuk mengubah status ke teknisi_tiba_dilokasi
                $targetStatus = 'barang_diambil';

                // Only update if not already in a more advanced status
                if (!in_array($relatedJob->status, ['teknisi_tiba_dilokasi', 'in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'])) {
                    $relatedJob->update([
                        'status' => $targetStatus,
                        'material_checked' => true,
                        'material_checked_at' => now(),
                        'updated_by' => auth()->id(),
                    ]);
                    Log::info("Job schedule {$relatedJob->job_number} (Type: {$relatedJob->type}) status updated to '{$targetStatus}' via mass sync during verification");
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Material berhasil diverifikasi. Anda dapat melanjutkan ke lokasi.',
                'data' => [
                    'job_schedule_id' => $jobSchedule->id,
                    'job_number' => $jobSchedule->job_number,
                    'status' => $jobSchedule->status,
                    'status_text' => $jobSchedule->status_text,
                    'inventory_issuing_id' => $inventoryIssuing->id,
                    'issuing_number' => $inventoryIssuing->issuing_number,
                    'received_by' => auth()->user()->name,
                    'received_at' => $inventoryIssuing->received_at->format('Y-m-d H:i:s'),
                    'can_arrive_at_location' => true, // Enable "Tiba di Lokasi" button
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Material verification error: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat verifikasi material: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get materials list for verification
     * Returns inventory issuing items for a job schedule
     */
    public function getMaterialsForVerification($jobScheduleId)
    {
        try {
            $jobSchedule = JobSchedule::findOrFail($jobScheduleId);

            if (in_array(strtolower(trim((string) $jobSchedule->type)), ['remove', 'remove_free', 'remove free'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job remove tidak memerlukan verifikasi material. Anda dapat langsung tiba di lokasi.',
                    'code' => 'MATERIAL_NOT_REQUIRED'
                ], 404);
            }
            
            // Find inventory issuing by material issue reference
            $materialIssue = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($jobSchedule) {
                $q->where('job_schedule_id', $jobSchedule->id);
            })->first();
            
            if (!$materialIssue) {
                // Check if this is a service job without material (e.g., first service)
                // Return a more informative message
                $isServiceJob = in_array(strtolower($jobSchedule->type), ['service', 'servis']);
                $message = $isServiceJob 
                    ? 'Job service ini tidak memerlukan verifikasi material. Anda dapat langsung ke lokasi.'
                    : 'Material issue tidak ditemukan untuk job ini. Silakan hubungi admin.';
                
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                    'code' => 'NO_MATERIAL_ISSUE'
                ], 404);
            }
            
            $inventoryIssuing = InventoryIssuing::with(['items.product'])
                ->where('reference_no', $materialIssue->issue_number)
                ->first();
            
            if (!$inventoryIssuing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Inventory issuing tidak ditemukan'
                ], 404);
            }
            
            // Load MaterialIssueItem to get room_name mapping
            $materialIssue->load('items');
            $roomNameMap = [];
            foreach ($materialIssue->items as $materialIssueItem) {
                if ($materialIssueItem->product_id && $materialIssueItem->room_name) {
                    // Map by product_id (key) to room_name (value)
                    // If same product_id appears multiple times (different rooms), store as array
                    if (!isset($roomNameMap[$materialIssueItem->product_id])) {
                        $roomNameMap[$materialIssueItem->product_id] = [];
                    }
                    $roomNameMap[$materialIssueItem->product_id][] = $materialIssueItem->room_name;
                }
            }
            
            // Check if already verified
            // Status 'processed' means warehouse is ready, but technician hasn't verified yet.
            // Status 'sent' or 'received' means verification/pickup is complete.
            $alreadyVerified = in_array($inventoryIssuing->status, ['sent', 'received']);
            
            $materials = $inventoryIssuing->items->groupBy('product_id')->map(function($group) use ($roomNameMap) {
                $first = $group->first();
                
                // Aggregate room names for all items in this product group
                $rooms = [];
                foreach ($group as $item) {
                    $roomName = null;
                    // Priority 1: From RoomNameMap
                    if (isset($roomNameMap[$item->product_id])) {
                        // Since multiple items of same product can be in different rooms, 
                        // we should try to match the specific item if possible, but for now 
                        // we just collect all unique room names for this product
                        foreach ($roomNameMap[$item->product_id] as $name) {
                            $rooms[] = $name;
                        }
                    }
                    
                    // Priority 2: From Notes
                    if ($item->notes && preg_match('/Room:\s*([^\s,]+)/i', $item->notes, $matches)) {
                        $rooms[] = trim($matches[1]);
                    }
                }
                
                $uniqueRooms = array_unique(array_filter($rooms));
                
                return [
                    'item_id' => $first->id,
                    'product_id' => $first->product_id,
                    'product_name' => $first->product->name ?? 'N/A',
                    'product_code' => $first->product->sku ?? $first->product->part_no ?? 'N/A',
                    'quantity_requested' => $group->sum('quantity_requested'),
                    'quantity_issued' => $group->sum('quantity_issued'),
                    'quantity_received' => $group->sum('quantity_received'),
                    'unit' => $first->product->unit ?? 'pcs',
                    'notes' => $group->pluck('notes')->filter()->unique()->implode(', '),
                    'room_name' => !empty($uniqueRooms) ? implode(', ', $uniqueRooms) : null,
                ];
            })->values();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'inventory_issuing_id' => $inventoryIssuing->id,
                    'issuing_number' => $inventoryIssuing->issuing_number,
                    'issue_date' => $inventoryIssuing->issue_date->format('Y-m-d'),
                    'status' => $inventoryIssuing->status,
                    'already_verified' => $alreadyVerified,
                    'received_by' => $inventoryIssuing->receivedBy ? $inventoryIssuing->receivedBy->name : null,
                    'received_at' => $inventoryIssuing->received_at ? $inventoryIssuing->received_at->format('Y-m-d H:i:s') : null,
                    'materials' => $materials,
                    'total_items' => $materials->count(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Get materials error: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
