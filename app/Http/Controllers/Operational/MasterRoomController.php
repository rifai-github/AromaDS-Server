<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\MasterRoom;
use App\Models\Building;
use App\Models\Customer;
use App\Models\MasterOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Traits\ColumnFilterTrait;

class MasterRoomController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        $query = MasterRoom::with(['building', 'customer', 'creator', 'updater']);

        // Apply per-column filters
        try {
            // Capture flat structure filters
            $customFilters = [];
            if ($request->has('room_name')) $customFilters['room_name'] = $request->room_name;
            if ($request->has('room_type')) $customFilters['room_type'] = $request->room_type;
            if ($request->has('room_floor')) $customFilters['room_floor'] = $request->room_floor;
            if ($request->has('room_qty')) $customFilters['room_qty'] = $request->room_qty;
            if ($request->has('room_temperature')) $customFilters['room_temperature'] = $request->room_temperature;
            if ($request->has('room_intensity')) $customFilters['room_intensity'] = $request->room_intensity;
            if ($request->has('room_installation_type')) $customFilters['room_installation_type'] = $request->room_installation_type;
            if ($request->has('room_length')) $customFilters['room_length'] = $request->room_length;
            if ($request->has('room_width')) $customFilters['room_width'] = $request->room_width;
            if ($request->has('room_height')) $customFilters['room_height'] = $request->room_height;
            if ($request->has('is_active')) $customFilters['is_active'] = $request->is_active;
            if ($request->has('created_at')) $customFilters['created_at'] = $request->created_at;

            // Skip AutoFilter for manually handled columns to avoid conflicts
            if (!empty($customFilters)) {
                $request->merge([
                    '_skip_auto_filter' => array_merge(
                        $request->input('_skip_auto_filter', []),
                        array_fill_keys(array_keys($customFilters), true)
                    )
                ]);
            }

            $this->applyColumnFilters($query, 'masterRoomsTable', [
                // 0 => checkbox
                1 => ['column' => 'room_name'],
                'room_name' => ['column' => 'room_name'],
                
                2 => ['column' => 'room_type'],
                'room_type' => ['column' => 'room_type'],
                
                3 => ['column' => 'room_floor'],
                'room_floor' => ['column' => 'room_floor'],
                
                4 => ['column' => 'room_qty'],
                'room_qty' => ['column' => 'room_qty'],
                
                5 => ['column' => 'room_temperature'],
                'room_temperature' => ['column' => 'room_temperature'],
                
                6 => ['column' => 'room_intensity'],
                'room_intensity' => ['column' => 'room_intensity'],
                
                7 => ['column' => 'room_installation_type'],
                'room_installation_type' => ['column' => 'room_installation_type'],
                
                8 => ['column' => 'room_length'],
                'room_length' => ['column' => 'room_length'],
                
                9 => ['column' => 'room_width'],
                'room_width' => ['column' => 'room_width'],
                
                10 => ['column' => 'room_height'],
                'room_height' => ['column' => 'room_height'],
                
                11 => ['column' => 'is_active', 'boolean' => true],
                'is_active' => ['column' => 'is_active', 'boolean' => true],
                
                12 => ['column' => 'created_at', 'type' => 'date'],
                'created_at' => ['column' => 'created_at', 'type' => 'date'],
            ], $customFilters);
        } catch (\Exception $e) {
            \Log::error('Error applying column filters in MasterRoomController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $rooms = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $rooms
            ]);
        }

        $buildings = Building::all();
        
        // Get dropdown data from MasterOption (same as wizard)
        $roomTypes = MasterOption::where('name', 'Room Type')->first()?->optionDetails ?? collect();
        $floors = MasterOption::where('name', 'Floor')->first()?->optionDetails ?? collect();
        $intensities = MasterOption::where('name', 'Scent Intensity')->first()?->optionDetails ?? collect();
        $installationTypes = MasterOption::where('name', 'Installation Type')->first()?->optionDetails ?? collect();
        
        return view('operational.master-rooms.index', compact(
            'rooms', 
            'buildings', 
            'roomTypes', 
            'floors', 
            'intensities', 
            'installationTypes'
        ));
    }

    public function create()
    {
        $buildings = Building::all();
        $customers = Customer::all();
        
        return view('operational.master-rooms.create', compact('buildings', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_name' => 'required|string|max:255',
            'room_type' => 'required|string|max:100',
            'room_floor' => 'required|string|max:50',
            'room_qty' => 'required|integer|min:1',
            'room_temperature' => 'required|numeric|min:0',
            'room_intensity' => 'required|string|max:50',
            'room_installation_type' => 'required|string|max:100',
            'room_length' => 'required|numeric|min:0',
            'room_width' => 'required|numeric|min:0',
            'room_height' => 'required|numeric|min:0',
            'room_remark' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $room = MasterRoom::create([
                'building_id' => $request->building_id ?? null,
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'room_floor' => $request->room_floor,
                'room_qty' => $request->room_qty,
                'room_temperature' => $request->room_temperature,
                'room_intensity' => $request->room_intensity,
                'room_installation_type' => $request->room_installation_type,
                'room_length' => $request->room_length,
                'room_width' => $request->room_width,
                'room_height' => $request->room_height,
                'room_remark' => $request->room_remark,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $room->load(['building', 'customer']),
                    'message' => 'Room created successfully'
                ], 201);
            }

            return redirect()->route('operational.master-rooms.show', $room)
                ->with('success', 'Master Room berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creating room: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(MasterRoom $masterRoom)
    {
        // Load standard relations for both AJAX and View
        $masterRoom->load(['building', 'customer', 'creator', 'updater']);
        
        // Fallback for Customer if null (MOM14 Point 1)
        if (!$masterRoom->customer_id && $masterRoom->building_id) {
             // Try to get first active customer from building
             $fallbackCustomer = $masterRoom->building->customers()
                ->wherePivot('is_active', true)
                ->first();
             if ($fallbackCustomer) {
                 $masterRoom->setRelation('customer', $fallbackCustomer);
             }
        }

        // If it's an AJAX request (likely from modal system in index)
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $masterRoom
            ]);
        }

        // For direct navigation (show.blade.php)
        // Load rental units related to this room from multiple sources:
        
        // 1. From RoomRentalUnit (Manual entries)
        $roomRentalUnits = \App\Models\RoomRentalUnit::where('building_id', $masterRoom->building_id)
            ->where('room_name', $masterRoom->room_name)
            ->with(['updater'])
            ->get();

        // 2. From UnitOnWall (Actual installed units)
        $unitsOnWall = \App\Models\UnitOnWall::where('room_id', $masterRoom->id)
            ->with(['rental', 'product', 'updatedBy'])
            ->get();

        // 3. From ContractRoom (Committed rentals in contracts)
        $contractRooms = \App\Models\ContractRoom::where('room_id', $masterRoom->id)
            ->with(['contract.customer', 'contract.updater', 'room.building'])
            ->get();

        // 4. From QuotationRoom (Proposed rentals - especially Install Free)
        $quotationRooms = \App\Models\QuotationRoom::where('room_id', $masterRoom->id)
            ->whereHas('quotation', function($q) {
                $q->whereNotIn('status', ['cancelled', 'rejected', 'expired']);
            })
            ->with(['quotation.customer', 'quotationRentals.masterRental', 'updater'])
            ->get();

        // Helper: Find actual completed job dates for this room
        // Includes service_first, service_routine, install, install_free, etc.
        $completedJobs = \App\Models\JobScheduleRoom::where('room_id', $masterRoom->id)
            ->where('status', 'completed')
            ->with(['jobSchedule'])
            ->get();

        $getActualDate = function($typePrefix) use ($completedJobs) {
            return $completedJobs->filter(function($jr) use ($typePrefix) {
                return str_contains(strtolower($jr->jobSchedule->type ?? ''), strtolower($typePrefix));
            })->sortByDesc('completed_at')->first()?->completed_at;
        };

        // Transform into a unified collection for display
        $rentalUnits = collect();

        // Add Installed Units (The current reality)
        foreach ($unitsOnWall as $item) {
            $rentalUnits->push((object)[
                'company_name' => $item->customer->name ?? $item->company_name ?? '-',
                'building_name' => $item->building->nama_gedung ?? '-',
                'room_name' => $item->room_name ?? $masterRoom->room_name,
                'rental_name' => $item->rental->rental_name ?? $item->rental_name ?? '-',
                'reference_number' => $item->serial_number ?: 'Installed Unit',
                'expected_install_date' => null,
                'install_date' => $item->install_date,
                'remove_date' => $item->status === 'removed' ? $item->updated_at : null,
                'last_service_date' => $item->last_service_date,
                'remarks' => $item->notes,
                'updated_at' => $item->updated_at,
                'updater_name' => $item->updatedBy->name ?? '-'
            ]);
        }

        // Add Contract Rentals (The commitment)
        foreach ($contractRooms as $item) {
            // Check if there's actual history for this commitment
            $actualInstall = $getActualDate('install');
            $actualLastService = $getActualDate('service');

            $rentalUnits->push((object)[
                'company_name' => $item->contract->customer->name ?? '-',
                'building_name' => $item->room->building->nama_gedung ?? $item->building->nama_gedung ?? '-',
                'room_name' => $masterRoom->room_name,
                'rental_name' => $item->rental_product->rental_name ?? 'Pending Assignment',
                'reference_number' => $item->contract->contract_number,
                'expected_install_date' => $item->contract->install_date,
                'install_date' => $actualInstall,
                'remove_date' => $getActualDate('remove'),
                'last_service_date' => $actualLastService,
                'remarks' => 'From Contract: ' . ($item->contract->contract_number),
                'updated_at' => $item->updated_at,
                'updater_name' => $item->contract->updater->name ?? '-'
            ]);
        }

        // Add Quotation Rentals (The proposal)
        foreach ($quotationRooms as $qr) {
            foreach ($qr->quotationRentals as $rental) {
                $rentalUnits->push((object)[
                    'company_name' => $qr->quotation->customer->name ?? $qr->quotation->company_name ?? '-',
                    'building_name' => $masterRoom->building->nama_gedung ?? '-',
                    'room_name' => $masterRoom->room_name,
                    'rental_name' => $rental->masterRental->rental_name ?? '-',
                    'reference_number' => $qr->quotation->quotation_number,
                    'expected_install_date' => null,
                    'install_date' => $getActualDate('install'), // Might have been installed during trial
                    'remove_date' => null,
                    'last_service_date' => $getActualDate('service'),
                    'remarks' => 'From Quotation: ' . $qr->quotation->quotation_number . ($qr->quotation->quotation_type ? ' (' . $qr->quotation->quotation_type . ')' : ''),
                    'updated_at' => $qr->updated_at,
                    'updater_name' => $qr->updater->name ?? '-'
                ]);
            }
        }

        // Add Legacy/Manual entries
        foreach ($roomRentalUnits as $item) {
            $rentalUnits->push((object)[
                'company_name' => $item->company_name,
                'building_name' => $item->building->nama_gedung ?? '-',
                'room_name' => $item->room_name,
                'rental_name' => $item->rental_name,
                'reference_number' => $item->reference_number,
                'expected_install_date' => $item->expected_install_date,
                'install_date' => $item->install_date,
                'remove_date' => $item->remove_date,
                'last_service_date' => $item->last_service_date,
                'remarks' => $item->remarks,
                'updated_at' => $item->updated_at,
                'updater_name' => $item->updater->name ?? '-'
            ]);
        }

        // Sort by updated_at descending
        $rentalUnits = $rentalUnits->sortByDesc('updated_at');

        // Fetch Master Options for dropdowns (MOM14 Point 3)
        $roomTypes = \App\Models\MasterOption::where('name', 'Room Type')->first()?->optionDetails()->where('is_active', true)->get() ?? collect();
        $floors = \App\Models\MasterOption::where('name', 'Floor')->first()?->optionDetails()->where('is_active', true)->get() ?? collect();
        $installationTypes = \App\Models\MasterOption::where('name', 'Installation Type')->first()?->optionDetails()->where('is_active', true)->get() ?? collect();

        return view('operational.master-rooms.show', compact('masterRoom', 'rentalUnits', 'roomTypes', 'floors', 'installationTypes'));
    }

    public function edit(MasterRoom $masterRoom)
    {
        $buildings = Building::all();
        $customers = Customer::all();
        
        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'masterRoom' => $masterRoom->load(['building', 'customer']),
                    'buildings' => $buildings,
                    'customers' => $customers
                ]
            ]);
        }
        
        // For non-AJAX requests, redirect to index with error message
        return redirect()->route('operational.master-rooms.index')
            ->with('error', 'Please use the modal system to edit master rooms.');
    }

    public function update(Request $request, MasterRoom $masterRoom)
    {
        $request->validate([
            'room_name' => 'required|string|max:255',
            'room_type' => 'nullable|string|max:100',
            'room_floor' => 'nullable|string|max:50',
            'room_qty' => 'nullable|integer|min:1',
            'room_temperature' => 'nullable|numeric|min:0',
            'room_intensity' => 'nullable|string|max:50',
            'room_installation_type' => 'nullable|string|max:100',
            'room_length' => 'nullable|numeric|min:0',
            'room_width' => 'nullable|numeric|min:0',
            'room_height' => 'nullable|numeric|min:0',
            'room_remark' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        try {
            DB::beginTransaction();

            $masterRoom->update([
                'building_id' => $request->building_id ?? $masterRoom->building_id,
                'customer_id' => $request->customer_id ?? $masterRoom->customer_id,
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'room_floor' => $request->room_floor,
                'room_qty' => $request->room_qty,
                'room_temperature' => $request->room_temperature,
                'room_intensity' => $request->room_intensity,
                'room_installation_type' => $request->room_installation_type,
                'room_length' => $request->room_length,
                'room_width' => $request->room_width,
                'room_height' => $request->room_height,
                'room_remark' => $request->room_remark,
                'is_active' => $request->is_active ?? true,
                'updated_by' => Auth::id(),
            ]);

            // SYNC: Update corresponding survey details
            $this->syncToSurveyDetails($masterRoom);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $masterRoom,
                    'message' => 'Room updated successfully'
                ]);
            }

            return redirect()->route('operational.master-rooms.show', $masterRoom)
                ->with('success', 'Master Room berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update room: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(MasterRoom $masterRoom)
    {
        try {
            // Check if room is used by any contracts
            $hasContracts = false;
            try {
                $hasContracts = $masterRoom->contractRooms()->exists();
            } catch (\Exception $e) {
                // If contract_rooms table doesn't exist or has different structure, skip this check
                \Log::warning('Contract rooms check failed: ' . $e->getMessage());
            }
            
            if ($hasContracts) {
                throw new \Exception('Tidak dapat menghapus ruangan yang sudah digunakan dalam kontrak.');
            }

            // SYNC: Soft delete corresponding survey detail with same ID (if exists)
            // Only delete survey details that were created from wizard (same ID)
            // Note: SurveyDetail doesn't use soft delete, so we use hard delete
            $deletedSurveyDetails = \App\Models\SurveyDetail::where('id', $masterRoom->id)->delete();
            Log::info("Hard deleted {$deletedSurveyDetails} survey detail(s) with ID {$masterRoom->id} (SurveyDetail doesn't use soft delete)");

            $masterRoom->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Room deleted successfully'
                ]);
            }
            
            return redirect()->route('operational.master-rooms.index')
                ->with('success', 'Master Room berhasil dihapus.');
        } catch (\Exception $e) {
            \Log::error('Error deleting room: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error deleting room: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Sync master room data to corresponding survey details
     */
    private function syncToSurveyDetails(MasterRoom $masterRoom)
    {
        try {
            // ONLY sync survey details that are explicitly linked via room_id
            $surveyDetails = \App\Models\SurveyDetail::where('room_id', $masterRoom->id)->get();

            foreach ($surveyDetails as $detail) {
                // Update the survey detail with master room data
                $specifications = json_decode($detail->specifications ?? '{}', true);
                
                // Update specifications with master room data
                $specifications['floor'] = $masterRoom->room_floor;
                $specifications['intensity'] = $masterRoom->room_intensity;
                $specifications['installation_type'] = $masterRoom->room_installation_type;
                $specifications['qty'] = $masterRoom->room_qty;
                $specifications['length'] = $masterRoom->room_length;
                $specifications['width'] = $masterRoom->room_width;
                $specifications['height'] = $masterRoom->room_height;
                $specifications['temperature'] = $masterRoom->room_temperature;
                $specifications['remark'] = $masterRoom->room_remark;
                $specifications['area'] = $masterRoom->room_length * $masterRoom->room_width;

                $detail->update([
                    'room_name' => $masterRoom->room_name,
                    'room_type' => $masterRoom->room_type,
                    'room_area' => $masterRoom->room_length * $masterRoom->room_width,
                    'quantity_needed' => $masterRoom->room_qty,
                    'specifications' => json_encode($specifications),
                    'updated_by' => Auth::id()
                ]);

                Log::info("Synced master room {$masterRoom->id} to survey detail {$detail->id}");
            }

            Log::info("Synced master room {$masterRoom->id} to " . $surveyDetails->count() . " survey details");
        } catch (\Exception $e) {
            Log::error("Failed to sync master room to survey details: " . $e->getMessage());
            // Don't throw exception to avoid breaking the main update
        }
    }

    public function getRoomsByBuilding(Request $request)
    {
        $request->validate([
            'building_id' => 'required|exists:buildings,id',
        ]);

        $rooms = MasterRoom::where('building_id', $request->building_id)
            ->where('is_active', true)
            ->get();

        return response()->json($rooms);
    }

    public function getRoomsByBuildingId($buildingId)
    {
        $rooms = MasterRoom::where('building_id', $buildingId)
            ->where('is_active', true)
            ->get();

        return response()->json($rooms);
    }

    public function getRoomsByCustomer(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $rooms = MasterRoom::where('is_active', true)
            ->get();

        return response()->json($rooms);
    }

    public function updateStatus(Request $request, MasterRoom $masterRoom)
    {
        $request->validate([
            'is_active' => 'required|boolean',
            'room_remark' => 'nullable|string',
        ]);

        try {
            $masterRoom->update([
                'is_active' => $request->is_active,
                'room_remark' => $request->room_remark,
            ]);

            return back()->with('success', 'Status ruangan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function calculateArea(MasterRoom $masterRoom)
    {
        $area = $masterRoom->room_width * $masterRoom->room_length;
        $volume = $masterRoom->room_width * $masterRoom->room_length * $masterRoom->room_height;

        return response()->json([
            'area' => $area,
            'volume' => $volume,
            'unit' => 'm²',
            'volume_unit' => 'm³',
        ]);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'room_type' => 'required|string|max:100',
            'room_floor' => 'required|string|max:50',
            'room_intensity' => 'required|string|max:50',
            'room_installation_type' => 'required|string|max:100',
            'room_length' => 'required|numeric|min:0',
            'room_width' => 'required|numeric|min:0',
            'room_height' => 'required|numeric|min:0',
            'room_temperature' => 'required|numeric|min:0',
            'room_qty' => 'required|integer|min:1',
            'room_prefix' => 'required|string|max:50',
            'start_number' => 'required|integer|min:1',
            'count' => 'required|integer|min:1|max:50',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;
            $startNumber = $request->start_number;

            for ($i = 0; $i < $request->count; $i++) {
                $roomName = $request->room_prefix . ' ' . ($startNumber + $i);
                
                // Check if room name already exists
                $exists = MasterRoom::where('room_name', $roomName)->exists();
                
                if (!$exists) {
                    MasterRoom::create([
                        'building_id' => $request->building_id ?? null,
                        'room_name' => $roomName,
                        'room_type' => $request->room_type,
                        'room_floor' => $request->room_floor,
                        'room_qty' => $request->room_qty,
                        'room_temperature' => $request->room_temperature,
                        'room_intensity' => $request->room_intensity,
                        'room_installation_type' => $request->room_installation_type,
                        'room_length' => $request->room_length,
                        'room_width' => $request->room_width,
                        'room_height' => $request->room_height,
                        'room_remark' => 'Bulk created',
                        'is_active' => true,
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} ruangan.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:master_rooms,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            foreach ($request->ids as $id) {
                $room = MasterRoom::find($id);
                
                // Check if room is used by any contracts
                $hasContracts = false;
                try {
                    $hasContracts = $room->contractRooms()->exists();
                } catch (\Exception $e) {
                    // If contract_rooms table doesn't exist or has different structure, skip this check
                    \Log::warning('Contract rooms check failed: ' . $e->getMessage());
                }
                
                if (!$hasContracts) {
                    $room->delete();
                    $deletedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$deletedCount} rooms",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting rooms: ' . $e->getMessage()
            ], 500);
        }
    }
}
