<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\UnitOnWall;
use App\Models\Customer;
use App\Models\Building;
use App\Models\MasterRoom;
use App\Models\MasterRental;
use App\Models\MasterProduct;
use App\Models\SerialNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitOnWallController extends Controller
{
    public function index(Request $request)
    {
        $query = UnitOnWall::with(['customer', 'building', 'room', 'rental', 'product', 'serialNumber']);

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by building
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        // Filter by room
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by rental
        if ($request->filled('rental_id')) {
            $query->where('rental_id', $request->rental_id);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by serial number
        if ($request->filled('serial_no')) {
            $query->whereHas('serialNumber', function ($q) use ($request) {
                $q->where('serial_no', 'like', '%' . $request->serial_no . '%');
            });
        }

        // Filter by install date
        if ($request->filled('start_date')) {
            $query->whereDate('install_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('install_date', '<=', $request->end_date);
        }

        $units = $query->orderBy('install_date', 'desc')->paginate(15);

        return view('operational.unit-on-walls.index', compact('units'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        $buildings = Building::where('status', 'active')->get();
        $rooms = MasterRoom::where('status', 'occupied')->get();
        $rentals = MasterRental::where('is_active', true)->get();
        $products = MasterProduct::where('is_active', true)->get();
        $serialNumbers = SerialNumber::where('transfer_status', 'installed')->get();
        
        return view('operational.unit-on-walls.create', compact('customers', 'buildings', 'rooms', 'rentals', 'products', 'serialNumbers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'required|exists:master_rooms,id',
            'rental_id' => 'required|exists:master_rentals,id',
            'product_id' => 'required|exists:master_products,id',
            'serial_number_id' => 'nullable|exists:serial_numbers,id',
            'install_date' => 'required|date|before_or_equal:today',
            'last_service_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $unit = UnitOnWall::create([
                'customer_id' => $request->customer_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'rental_id' => $request->rental_id,
                'product_id' => $request->product_id,
                'serial_number_id' => $request->serial_number_id,
                'install_date' => $request->install_date,
                'last_service_date' => $request->last_service_date,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Update serial number status if provided
            if ($request->serial_number_id) {
                $serialNumber = SerialNumber::find($request->serial_number_id);
                $serialNumber->update([
                    'unit_status' => 'in_use',
                    'transfer_status' => 'installed',
                ]);
            }

            DB::commit();

            return redirect()->route('operational.unit-on-walls.show', $unit)
                ->with('success', 'Unit on Wall berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(UnitOnWall $unitOnWall)
    {
        $unitOnWall->load(['customer', 'building', 'room', 'rental', 'product', 'serialNumber']);
        
        return view('operational.unit-on-walls.show', compact('unitOnWall'));
    }

    public function edit(UnitOnWall $unitOnWall)
    {
        $customers = Customer::where('status', 'active')->get();
        $buildings = Building::where('status', 'active')->get();
        $rooms = MasterRoom::where('status', 'occupied')->orWhere('id', $unitOnWall->room_id)->get();
        $rentals = MasterRental::where('is_active', true)->get();
        $products = MasterProduct::where('is_active', true)->get();
        $serialNumbers = SerialNumber::where('transfer_status', 'installed')->orWhere('id', $unitOnWall->serial_number_id)->get();
        
        return view('operational.unit-on-walls.edit', compact('unitOnWall', 'customers', 'buildings', 'rooms', 'rentals', 'products', 'serialNumbers'));
    }

    public function update(Request $request, UnitOnWall $unitOnWall)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'required|exists:master_rooms,id',
            'rental_id' => 'required|exists:master_rentals,id',
            'product_id' => 'required|exists:master_products,id',
            'serial_number_id' => 'nullable|exists:serial_numbers,id',
            'install_date' => 'required|date|before_or_equal:today',
            'last_service_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $oldSerialNumberId = $unitOnWall->serial_number_id;

            $unitOnWall->update([
                'customer_id' => $request->customer_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'rental_id' => $request->rental_id,
                'product_id' => $request->product_id,
                'serial_number_id' => $request->serial_number_id,
                'install_date' => $request->install_date,
                'last_service_date' => $request->last_service_date,
                'notes' => $request->notes,
            ]);

            // Update serial number statuses
            if ($oldSerialNumberId != $request->serial_number_id) {
                // Free up old serial number
                if ($oldSerialNumberId) {
                    $oldSerialNumber = SerialNumber::find($oldSerialNumberId);
                    $oldSerialNumber->update([
                        'unit_status' => 'available',
                        'transfer_status' => 'in_warehouse',
                    ]);
                }

                // Update new serial number
                if ($request->serial_number_id) {
                    $newSerialNumber = SerialNumber::find($request->serial_number_id);
                    $newSerialNumber->update([
                        'unit_status' => 'in_use',
                        'transfer_status' => 'installed',
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('operational.unit-on-walls.show', $unitOnWall)
                ->with('success', 'Unit on Wall berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(UnitOnWall $unitOnWall)
    {
        try {
            // Free up serial number
            if ($unitOnWall->serial_number_id) {
                $serialNumber = SerialNumber::find($unitOnWall->serial_number_id);
                $serialNumber->update([
                    'unit_status' => 'available',
                    'transfer_status' => 'in_warehouse',
                ]);
            }

            $unitOnWall->delete();
            return redirect()->route('operational.unit-on-walls.index')
                ->with('success', 'Unit on Wall berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function service(Request $request, UnitOnWall $unitOnWall)
    {
        $request->validate([
            'service_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            $unitOnWall->update([
                'last_service_date' => $request->service_date,
                'notes' => $request->notes,
            ]);

            return back()->with('success', 'Service berhasil dicatat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function remove(Request $request, UnitOnWall $unitOnWall)
    {
        $request->validate([
            'remove_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Free up serial number
            if ($unitOnWall->serial_number_id) {
                $serialNumber = SerialNumber::find($unitOnWall->serial_number_id);
                $serialNumber->update([
                    'unit_status' => 'available',
                    'transfer_status' => 'in_warehouse',
                ]);
            }

            $unitOnWall->update([
                'remove_date' => $request->remove_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return back()->with('success', 'Unit berhasil dilepas dari dinding.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getRoomsByBuilding(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
        ]);

        $rooms = MasterRoom::where('building_id', $request->building_id)
            ->where('status', 'occupied')
            ->get();

        return response()->json($rooms);
    }

    public function getRentalsByCustomer(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $rentals = MasterRental::where('is_active', true)->get();

        return response()->json($rentals);
    }

    public function getSerialNumbersByProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:master_products,id',
        ]);

        $serialNumbers = SerialNumber::where('product_id', $request->product_id)
            ->where('transfer_status', 'installed')
            ->where('unit_status', 'available')
            ->get();

        return response()->json($serialNumbers);
    }

    public function getUnitsByRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:master_rooms,id',
        ]);

        $units = UnitOnWall::where('room_id', $request->room_id)
            ->with(['rental', 'product', 'serialNumber'])
            ->get();

        return response()->json($units);
    }

    public function calculateServiceInterval(UnitOnWall $unitOnWall)
    {
        if (!$unitOnWall->last_service_date) {
            return response()->json([
                'days_since_install' => $unitOnWall->install_date->diffInDays(now()),
                'service_needed' => true,
                'message' => 'Belum pernah diservice sejak instalasi.',
            ]);
        }

        $daysSinceLastService = $unitOnWall->last_service_date->diffInDays(now());
        $rental = $unitOnWall->rental;
        
        // Calculate service frequency based on rental service frequency
        $serviceFrequency = $this->parseServiceFrequency($rental->service_frequency);
        
        $serviceNeeded = $daysSinceLastService >= $serviceFrequency;

        return response()->json([
            'days_since_last_service' => $daysSinceLastService,
            'service_frequency' => $serviceFrequency,
            'service_needed' => $serviceNeeded,
            'message' => $serviceNeeded ? 'Service diperlukan.' : 'Belum waktunya service.',
        ]);
    }

    private function parseServiceFrequency($frequency)
    {
        // Parse service frequency string to days
        // Example: "30 days", "1 month", "2 weeks"
        $frequency = strtolower($frequency);
        
        if (strpos($frequency, 'day') !== false) {
            return (int) preg_replace('/[^0-9]/', '', $frequency);
        } elseif (strpos($frequency, 'week') !== false) {
            return (int) preg_replace('/[^0-9]/', '', $frequency) * 7;
        } elseif (strpos($frequency, 'month') !== false) {
            return (int) preg_replace('/[^0-9]/', '', $frequency) * 30;
        }
        
        return 30; // Default to 30 days
    }
}
