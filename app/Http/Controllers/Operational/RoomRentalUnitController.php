<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\RoomRentalUnit;
use App\Models\Customer;
use App\Models\Building;
use App\Models\MasterRoom;
use App\Models\MasterRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoomRentalUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomRentalUnit::with(['customer', 'building', 'room', 'rental', 'createdBy', 'updatedBy']);

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

        // Filter by reference number
        if ($request->filled('reference_no')) {
            $query->where('reference_no', 'like', '%' . $request->reference_no . '%');
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('expected_install_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('expected_install_date', '<=', $request->end_date);
        }

        $rentalUnits = $query->orderBy('expected_install_date', 'desc')->paginate(15);

        // Data for modal dropdowns
        $customers = Customer::where('is_active', true)->get();
        $buildings = Building::whereNotNull('nama_gedung')->orWhereNotNull('name')->get();
        $rooms = MasterRoom::all();
        $rentals = MasterRental::where('is_active', true)->get();

        return view('operational.room-rental-units.index', compact('rentalUnits', 'customers', 'buildings', 'rooms', 'rentals'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $buildings = Building::whereNotNull('nama_gedung')->orWhereNotNull('name')->get();
        $rooms = MasterRoom::all();
        $rentals = MasterRental::where('is_active', true)->get();
        
        return view('operational.room-rental-units.create', compact('customers', 'buildings', 'rooms', 'rentals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'required|exists:master_rooms,id',
            'rental_id' => 'required|exists:master_rentals,id',
            'reference_no' => 'required|string|max:100',
            'expected_install_date' => 'required|date',
            'install_date' => 'nullable|date|after_or_equal:expected_install_date',
            'remove_date' => 'nullable|date|after:expected_install_date',
            'last_service_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $rentalUnit = RoomRentalUnit::create([
                'customer_id' => $request->customer_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'rental_id' => $request->rental_id,
                'reference_no' => $request->reference_no,
                'expected_install_date' => $request->expected_install_date,
                'install_date' => $request->install_date,
                'remove_date' => $request->remove_date,
                'last_service_date' => $request->last_service_date,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Update room status to occupied if install date is set
            if ($request->install_date) {
                $room = MasterRoom::find($request->room_id);
                $room->update(['status' => 'occupied']);
            }

            DB::commit();

            return redirect()->route('operational.room-rental-units.show', $rentalUnit)
                ->with('success', 'Room Rental Unit berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(RoomRentalUnit $roomRentalUnit)
    {
        $roomRentalUnit->load(['customer', 'building', 'room', 'rental']);
        
        return view('operational.room-rental-units.show', compact('roomRentalUnit'));
    }

    public function edit(RoomRentalUnit $roomRentalUnit)
    {
        $customers = Customer::where('is_active', true)->get();
        $buildings = Building::whereNotNull('nama_gedung')->orWhereNotNull('name')->get();
        $rooms = MasterRoom::all();
        $rentals = MasterRental::where('is_active', true)->get();
        
        return view('operational.room-rental-units.edit', compact('roomRentalUnit', 'customers', 'buildings', 'rooms', 'rentals'));
    }

    public function update(Request $request, RoomRentalUnit $roomRentalUnit)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'required|exists:master_rooms,id',
            'rental_id' => 'required|exists:master_rentals,id',
            'reference_no' => 'required|string|max:100',
            'expected_install_date' => 'required|date',
            'install_date' => 'nullable|date|after_or_equal:expected_install_date',
            'remove_date' => 'nullable|date|after:expected_install_date',
            'last_service_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $oldRoomId = $roomRentalUnit->room_id;
            $oldInstallDate = $roomRentalUnit->install_date;

            $roomRentalUnit->update([
                'customer_id' => $request->customer_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'rental_id' => $request->rental_id,
                'reference_no' => $request->reference_no,
                'expected_install_date' => $request->expected_install_date,
                'install_date' => $request->install_date,
                'remove_date' => $request->remove_date,
                'last_service_date' => $request->last_service_date,
                'notes' => $request->notes,
            ]);

            // Update room statuses
            if ($oldRoomId != $request->room_id) {
                // Free up old room
                if ($oldRoomId) {
                    $oldRoom = MasterRoom::find($oldRoomId);
                    $oldRoom->update(['status' => 'available']);
                }
            }

            // Update new room status
            if ($request->install_date) {
                $newRoom = MasterRoom::find($request->room_id);
                $newRoom->update(['status' => 'occupied']);
            }

            DB::commit();

            return redirect()->route('operational.room-rental-units.show', $roomRentalUnit)
                ->with('success', 'Room Rental Unit berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(RoomRentalUnit $roomRentalUnit)
    {
        try {
            // Free up the room
            if ($roomRentalUnit->room_id) {
                $room = MasterRoom::find($roomRentalUnit->room_id);
                $room->update(['status' => 'available']);
            }

            $roomRentalUnit->delete();
            return redirect()->route('operational.room-rental-units.index')
                ->with('success', 'Room Rental Unit berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function install(Request $request, RoomRentalUnit $roomRentalUnit)
    {
        $request->validate([
            'install_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $roomRentalUnit->update([
                'install_date' => $request->install_date,
                'notes' => $request->notes,
            ]);

            // Update room status
            $room = MasterRoom::find($roomRentalUnit->room_id);
            $room->update(['status' => 'occupied']);

            DB::commit();

            return back()->with('success', 'Unit berhasil dipasang.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function remove(Request $request, RoomRentalUnit $roomRentalUnit)
    {
        $request->validate([
            'remove_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $roomRentalUnit->update([
                'remove_date' => $request->remove_date,
                'notes' => $request->notes,
            ]);

            // Update room status
            $room = MasterRoom::find($roomRentalUnit->room_id);
            $room->update(['status' => 'available']);

            DB::commit();

            return back()->with('success', 'Unit berhasil dilepas.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function service(Request $request, RoomRentalUnit $roomRentalUnit)
    {
        $request->validate([
            'service_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            $roomRentalUnit->update([
                'last_service_date' => $request->service_date,
                'notes' => $request->notes,
            ]);

            return back()->with('success', 'Service berhasil dicatat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getRoomsByBuilding(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
        ]);

        $rooms = MasterRoom::where('building_id', $request->building_id)
            ->where('status', 'available')
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
}
