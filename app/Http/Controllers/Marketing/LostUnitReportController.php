<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\LostUnitReport;
use App\Models\Contract;
use App\Models\ContractRental;
use App\Models\ContractRoom;
use App\Models\Customer;
use App\Models\User;
use App\Models\JobAdvice;
use App\Models\MasterRental;
use App\Models\MasterRoom;
use App\Models\RentalPrice;
use App\Models\SerialNumber;
use App\Models\UnitOnWall;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

use App\Http\Traits\AccessControlFilterTrait;

class LostUnitReportController extends Controller
{
    use AccessControlFilterTrait;

    public function index(Request $request)
    {
        $this->mergeTableHeaderFilters($request);

        $query = LostUnitReport::with(['contract', 'contract.customer', 'masterRental', 'room', 'items', 'items.room', 'items.masterRental', 'reporter', 'approver', 'updater', 'creator', 'invoice', 'jobAdvice.jobSchedules']);

        // Filter by report number
        if ($request->filled('report_number')) {
            $query->where('report_number', 'like', '%' . $request->report_number . '%');
        }

        // Filter by contract number
        if ($request->filled('contract_number')) {
            $query->whereHas('contract', function ($q) use ($request) {
                $q->where('contract_number', 'like', '%' . $request->contract_number . '%');
            });
        }

        // Filter by company name
        if ($request->filled('company_name')) {
            $query->whereHas('contract.customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->company_name . '%');
            });
        }

        // Filter by lost unit price
        if ($request->filled('lost_unit_price')) {
            $query->where('lost_unit_price', 'like', '%' . $request->lost_unit_price . '%');
        }

        // Filter by rental name
        if ($request->filled('rental_name')) {
            $query->where(function($q) use ($request) {
                $q->where('rental_name', 'like', '%' . $request->rental_name . '%')
                  ->orWhereHas('masterRental', function($q2) use ($request) {
                      $q2->where('rental_name', 'like', '%' . $request->rental_name . '%');
                  })
                  ->orWhereHas('items.masterRental', function($q3) use ($request) {
                      $q3->where('rental_name', 'like', '%' . $request->rental_name . '%');
                  });
            });
        }

        // Filter by room name
        if ($request->filled('room_name')) {
            $query->where(function($q) use ($request) {
                $q->where('room_name', 'like', '%' . $request->room_name . '%')
                  ->orWhereHas('room', function($q2) use ($request) {
                      $q2->where('room_name', 'like', '%' . $request->room_name . '%');
                  })
                  ->orWhereHas('items.room', function($q3) use ($request) {
                      $q3->where('room_name', 'like', '%' . $request->room_name . '%');
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', 'like', '%' . $request->status . '%');
        }

        // Filter by reporter name
        if ($request->filled('reporter_name')) { // Note: input converted by PHP 
            $query->whereHas('reporter', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->reporter_name . '%');
            });
        }

        // Filter by creator name
        if ($request->filled('creator_name')) {
            $query->whereHas('creator', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->creator_name . '%');
            });
        }

        // Filter by updater name
        if ($request->filled('updater_name')) {
            $query->whereHas('updater', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->updater_name . '%');
            });
        }

        // Filter by date ranges / exact dates from headers
        if ($request->filled('created_at')) {
            $this->applyTextDateFilter($query, 'created_at', $request->created_at);
        }
        if ($request->filled('updated_at')) {
            $this->applyTextDateFilter($query, 'updated_at', $request->updated_at);
        }

        // Note: original start_date and end_date kept for generic request scenarios
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginateStd(25)->withQueryString();

        return view('marketing.lost-unit-reports.index', compact('reports'));
    }

    private function mergeTableHeaderFilters(Request $request): void
    {
        $filters = $request->input('filter', []);

        if (!is_array($filters) || empty($filters)) {
            return;
        }

        $normalizedFilters = [];

        foreach ($filters as $key => $value) {
            if (!is_string($key) || blank($value)) {
                continue;
            }

            $normalizedKey = str_replace(['.', '__'], '_', $key);
            $normalizedFilters[$normalizedKey] = $value;
        }

        if (!empty($normalizedFilters)) {
            $request->merge($normalizedFilters);
        }
    }

    private function applyTextDateFilter(Builder $query, string $column, string $term): void
    {
        $term = trim($term);
        $qualifiedColumn = 'lost_unit_reports.' . $column;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $term)) {
            $query->whereDate($qualifiedColumn, $term);
            return;
        }

        $query->where(function ($q) use ($qualifiedColumn, $term) {
            $q->whereRaw("DATE_FORMAT({$qualifiedColumn}, '%Y-%m-%d') LIKE ?", ["%{$term}%"])
              ->orWhereRaw("DATE_FORMAT({$qualifiedColumn}, '%d %b %Y') LIKE ?", ["%{$term}%"])
              ->orWhereRaw("DATE_FORMAT({$qualifiedColumn}, '%d %M %Y') LIKE ?", ["%{$term}%"])
              ->orWhereRaw("DATE_FORMAT({$qualifiedColumn}, '%H:%i') LIKE ?", ["%{$term}%"]);
        });
    }

    public function create(Request $request)
    {
        // Filter contracts based on requirements:
        // 1. Hierarchy Access (Branch/Personal)
        // 2. Active Status
        // 3. Has BA Date (Job started)
        // 4. No Remove Job (Not returned)
        
        $user = Auth::user();
        
        // Optimize: Select only necessary columns
        $query = Contract::select('id', 'contract_number', 'customer_id', 'marketing_id', 'created_by')
            ->with(['customer:id,name'])
            ->where('contract_status', 'active');
            
        // Apply Access Control (Hierarchy)
        // We filter by marketing_id matching accessible users
        // For contracts, usually created_by is admin/sales_admin, but marketing_id is the owner
        // NOTE: Pass null for branchField and warehouseField since contracts table doesn't have these columns
        $query = $this->applyAccessControlFilter($query, $user, 'created_by', 'marketing_id', null, null, null);
        
        // Filter: Must have BA Date (at least one job started)
        $query->whereHas('jobSchedules', function($q) {
            $q->whereNotNull('ba_date');
        });
        
        // Filter: Must NOT have Remove Job (Unit not returned)
        $query->whereDoesntHave('jobSchedules', function($q) {
            $q->whereIn('job_schedules.type', ['remove', 'remove_free']);
            // Optional: Check status of remove job? Usually existence is enough warrant to hide from Lost Unit
        });
        
        $contracts = $query->get();
        
        // Return JSON for AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'contracts' => $contracts,
                    // 'users' => $users, // Removed for optimization, not needed in dropdown
                ]
            ]);
        }

        $users = User::all();
        
        return view('marketing.lost-unit-reports.create', compact('contracts', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'remark' => 'required|string|max:500',
            'bap_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'charge_customer' => 'nullable|boolean',
            'charge_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.room_id' => 'required|exists:master_rooms,id',
            'items.*.master_rental_id' => 'required|exists:master_rentals,id',
        ]);

        try {
            DB::beginTransaction();

            // Get contract and customer info
            $contract = Contract::with(['customer'])->findOrFail($request->contract_id);
            $masterRental = MasterRental::find($request->master_rental_id);
            
            // Get building info from room if provided, otherwise from contract's first room
            $buildingId = null;
            // Get building info from items (assume all items in same building/contract usually, take first)
            $buildingId = null;
            if ($request->has('items') && count($request->items) > 0) {
                 $firstItemRoomId = $request->items[0]['room_id'];
                 $room = MasterRoom::find($firstItemRoomId);
                 $buildingId = $room ? $room->building_id : null;
            } elseif ($request->room_id) {
                // Legacy fallback
                 $room = MasterRoom::find($request->room_id);
                 $buildingId = $room ? $room->building_id : null;
            }
            
            if (!$buildingId) {
                // Fallback to contract's first room
                $firstRoom = ContractRoom::where('contract_id', $contract->id)->with('room')->first();
                $buildingId = ($firstRoom && $firstRoom->room) ? $firstRoom->room->building_id : null;
            }

            // Get branch from building
            $branchId = null;
            if ($buildingId) {
                $building = \App\Models\Building::find($buildingId);
                if ($building && $building->city_id) {
                    $branch = \App\Models\Branch::where('city_id', $building->city_id)->where('is_active', true)->first();
                    if (!$branch && $building->province_id) {
                        $branch = \App\Models\Branch::where('province_id', $building->province_id)->where('is_active', true)->first();
                    }
                    $branchId = $branch ? $branch->id : null;
                }
            }

            // Generate report number using DocumentNumberService
            $documentNumberService = new DocumentNumberService();
            $reportNumber = $documentNumberService->generate(
                'lost_unit_report',
                null,
                $buildingId,
                $contract->id
            );

            // Create report
            $report = LostUnitReport::create([
                'report_number' => $reportNumber,
                'contract_id' => $request->contract_id,
                'contract_number' => $contract->contract_number,
                'company_name' => $contract->customer->name,
                // Handle backward/forward compatibility or nullable fields
                'master_rental_id' => null, // Multi-item support: null on parent
                'room_id' => null, // Multi-item support: null on parent
                'building_id' => $buildingId,
                'branch_id' => $branchId,
                'rental_name' => 'Multi-Item', // Generalized name
                'room_name' => 'Multi-Room', // Generalized name
                'remark' => $request->remark,
                'status' => 'draft', // Start as draft
                'report_by' => Auth::id(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $totalLostUnitPrice = 0;
            
            // Process Items
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    // Calculate price for each item based on rental and branch
                    $itemPrice = $this->calculateItemPrice($item['master_rental_id'], $branchId);
                    
                    \App\Models\LostUnitReportItem::create([
                        'lost_unit_report_id' => $report->id,
                        'room_id' => $item['room_id'],
                        'master_rental_id' => $item['master_rental_id'],
                        'price' => $itemPrice
                    ]);
                    
                    $totalLostUnitPrice += $itemPrice;
                }
            } else {
                 // Fallback for single item request (if any legacy calls remain, though UI will force array)
                 // Or if user just sends one room/rental pair via old API
                 if ($request->room_id && $request->master_rental_id) {
                     $itemPrice = $this->calculateItemPrice($request->master_rental_id, $branchId);
                     \App\Models\LostUnitReportItem::create([
                        'lost_unit_report_id' => $report->id,
                        'room_id' => $request->room_id,
                        'master_rental_id' => $request->master_rental_id,
                        'price' => $itemPrice
                    ]);
                    $totalLostUnitPrice += $itemPrice;
                 }
            }

            // Update parent total price
            $report->update([
                'lost_unit_price' => $totalLostUnitPrice,
                'original_price' => $totalLostUnitPrice,
                'is_price_manual' => false,
                'bap_file' => $this->storeBapFile($request),
                'charge_customer' => $request->boolean('charge_customer', true),
                'charge_amount' => $request->boolean('charge_customer', true)
                    ? ($request->charge_amount ?? $totalLostUnitPrice)
                    : 0,
            ]);



            DB::commit();

            // Check if request expects JSON response
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Unit Hilang berhasil dibuat dengan status Draft.',
                    'data' => $report->load(['contract', 'contract.customer', 'masterRental', 'room']),
                    'redirect' => route('marketing.lost-unit-reports.show', $report->id)
                ]);
            }

            return redirect()->route('marketing.lost-unit-reports.show', $report->id)
                ->with('success', 'Laporan Unit Hilang berhasil dibuat dengan status Draft.');
        } catch (\Exception $e) {
            DB::rollback();
            
            \Log::error('Failed to create Lost Unit Report: ' . $e->getMessage());
            
            // Check if request expects JSON response
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Calculate item price based on master rental and branch
     */
    private function calculateItemPrice($masterRentalId, $branchId)
    {
        if (!$masterRentalId) return 0;
        
        $masterRental = MasterRental::find($masterRentalId);
        if (!$masterRental) return 0;
        
        if ($branchId) {
            $rentalPrice = RentalPrice::where('master_rental_id', $masterRentalId)
                ->where('branch_id', $branchId)
                ->first();
            
            if ($rentalPrice && $rentalPrice->lost_unit_price > 0) {
                return $rentalPrice->lost_unit_price;
            }
        }
        
        return $masterRental->lost_unit_price ?? 0;
    }

    public function show(LostUnitReport $lostUnitReport)
    {
        $lostUnitReport->load([
            'contract.customer', 
            'masterRental', 
            'room', 
            'building',
            'branch',
            'invoice',
            'reporter', 
            'approver', 
            'finalizer',
            'creator',
            'updater', 
            'jobAdvice',
            'items.room.building',
            'items.masterRental'
        ]);
        
        return view('marketing.lost-unit-reports.show', [
            'report' => $lostUnitReport
        ]);
    }

    public function edit(LostUnitReport $lostUnitReport)
    {
        // Only allow edit if status is draft
        if ($lostUnitReport->status !== 'draft') {
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya report dengan status Draft yang dapat diedit.'
                ], 403);
            }
            return back()->with('error', 'Hanya report dengan status Draft yang dapat diedit.');
        }

        $lostUnitReport->load(['contract.customer', 'masterRental', 'room', 'reporter', 'approver', 'updater']);
        
        // Always return JSON for AJAX (auto-save system)
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $lostUnitReport
            ]);
        }
        
        // For non-AJAX requests, redirect to show page (no separate edit page - use auto-save)
        return redirect()->route('marketing.lost-unit-reports.show', $lostUnitReport)
            ->with('info', 'Edit dilakukan langsung di halaman detail dengan sistem auto-save.');
    }

    public function update(Request $request, LostUnitReport $lostUnitReport)
    {
        // Only allow update if status is draft
        if ($lostUnitReport->status !== 'draft') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya report dengan status Draft yang dapat diedit.'
                ], 403);
            }
            return back()->with('error', 'Hanya report dengan status Draft yang dapat diedit.');
        }

        $request->validate([
            'remark' => 'nullable|string|max:500',
            'lost_unit_price' => 'nullable|numeric|min:0',
            'bap_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'charge_customer' => 'nullable|boolean',
            'charge_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'remark' => $request->remark,
                'updated_by' => Auth::id(),
            ];

            if ($request->hasFile('bap_file')) {
                if ($lostUnitReport->bap_file) {
                    Storage::disk('public')->delete($lostUnitReport->bap_file);
                }

                $updateData['bap_file'] = $this->storeBapFile($request);
            }

            if ($request->has('charge_customer')) {
                $chargeCustomer = $request->boolean('charge_customer');
                $updateData['charge_customer'] = $chargeCustomer;
                $updateData['charge_amount'] = $chargeCustomer
                    ? ($request->charge_amount ?? $lostUnitReport->lost_unit_price)
                    : 0;
            }

            // Check if price is manually changed
            if ($request->has('lost_unit_price')) {
                $newPrice = $request->lost_unit_price;
                $originalPrice = $lostUnitReport->original_price;
                
                $updateData['lost_unit_price'] = $newPrice;
                $updateData['is_price_manual'] = ($newPrice != $originalPrice);
            }

            $lostUnitReport->update($updateData);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Unit Hilang berhasil diperbarui.',
                    'data' => $lostUnitReport->fresh()
                ]);
            }

            return redirect()->route('marketing.lost-unit-reports.show', $lostUnitReport)
                ->with('success', 'Laporan Unit Hilang berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(LostUnitReport $lostUnitReport)
    {
        try {
            $lostUnitReport->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Unit Hilang berhasil dihapus.'
                ]);
            }
            
            return redirect()->route('marketing.lost-unit-reports.index')
                ->with('success', 'Laporan Unit Hilang berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function storeBapFile(Request $request): ?string
    {
        if (! $request->hasFile('bap_file')) {
            return null;
        }

        return $request->file('bap_file')->store('lost-unit-reports/bap', 'public');
    }

    private function retireLostUnits(LostUnitReport $lostUnitReport): void
    {
        $lostUnitReport->loadMissing(['items', 'contract.customer']);

        foreach ($lostUnitReport->items as $item) {
            $unitQuery = UnitOnWall::where('room_id', $item->room_id)
                ->where('rental_id', $item->master_rental_id)
                ->where('status', 'active');

            if ($lostUnitReport->building_id) {
                $unitQuery->where('building_id', $lostUnitReport->building_id);
            }

            if ($lostUnitReport->contract?->customer_id) {
                $unitQuery->where('customer_id', $lostUnitReport->contract->customer_id);
            }

            // One room can hold units from several contracts at once, all on the same rental
            // (QA room 460: 8 active units across four contracts). Without this, approving a
            // Lost Unit Report retires units belonging to somebody else's contract and marks
            // their serial numbers lost. Same rule as the Remove job fix in 8ce6d11.
            $unitQuery->scopedToContracts([$lostUnitReport->contract_id]);

            $units = $unitQuery->with('serialNumber')->get();

            foreach ($units as $unit) {
                $unit->update([
                    'status' => 'removed',
                    'notes' => trim(($unit->notes ? $unit->notes . "\n" : '') . "Dilaporkan hilang pada {$lostUnitReport->report_number}."),
                    'updated_by' => Auth::id(),
                ]);

                if ($unit->serialNumber) {
                    $unit->serialNumber->update([
                        // 'lost', not 'retired' + damaged. A missing unit is not a broken one,
                        // and recording it as damaged made lost units impossible to tell apart
                        // from units that came back broken - exactly what a loss report needs
                        // to answer (QA, 28 Aug 2026). Condition is left as last known: nobody
                        // inspected the unit, it is gone.
                        'status' => SerialNumber::STATUS_LOST,
                        'location_type' => 'customer',
                        'notes' => trim(($unit->serialNumber->notes ? $unit->serialNumber->notes . "\n" : '') . "Dilaporkan hilang pada {$lostUnitReport->report_number}."),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:lost_unit_reports,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = LostUnitReport::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully hidden {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error hiding records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update individual item price
     */
    public function updateItemPrice(Request $request, $itemId)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $item = \App\Models\LostUnitReportItem::findOrFail($itemId);
            $report = $item->report;

            // Only allow update if report is draft
            if ($report->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya report dengan status Draft yang dapat diedit.'
                ], 403);
            }

            DB::beginTransaction();

            // Update item price
            $item->update(['price' => $request->price]);

            // Recalculate total
            $total = $report->items()->sum('price');
            $report->update([
                'lost_unit_price' => $total,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Price updated successfully',
                'total' => $total,
                'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update price: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalize report (draft -> submitted)
     */
    public function finalize(Request $request, LostUnitReport $lostUnitReport)
    {
        if ($lostUnitReport->status !== 'draft') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya report dengan status Draft yang dapat di-finalize.'
                ], 400);
            }
            return back()->with('error', 'Hanya report dengan status Draft yang dapat di-finalize.');
        }

        if (! $lostUnitReport->bap_file) {
            $message = 'BAP wajib diupload sebelum laporan unit hilang dapat di-finalize.';
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if ($lostUnitReport->charge_customer && (float) $lostUnitReport->charge_amount <= 0) {
            $message = 'Nominal charge wajib diisi jika customer dikenakan charge.';
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        try {
            $lostUnitReport->update([
                'status' => 'submitted',
                'finalized_at' => now(),
                'finalized_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Unit Hilang berhasil di-finalize dan menunggu approval.'
                ]);
            }

            return back()->with('success', 'Laporan Unit Hilang berhasil di-finalize dan menunggu approval.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Approve report (submitted -> approved) and auto-generate invoice
     */
    public function approve(LostUnitReport $lostUnitReport, Request $request)
    {
        // Authorization check
        $user = Auth::user();
        if (!$user->canApprove('lost_unit_reports')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk menyetujui laporan ini. Pastikan role Anda memiliki permission "Approve" untuk Lost Unit Reports.'
                ], 403);
            }
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui laporan ini.');
        }

        if ($lostUnitReport->status !== 'submitted') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya report dengan status Waiting for Approval yang dapat di-approve.'
                ], 400);
            }
            return back()->with('error', 'Hanya report dengan status Waiting for Approval yang dapat di-approve.');
        }

        try {
            DB::beginTransaction();

            // Update status to approved
            $lostUnitReport->update([
                'status' => 'approved',
                'approve_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->retireLostUnits($lostUnitReport);

            // Auto-generate invoice (only if not on hold)
            $contract = $lostUnitReport->contract;
            $invoice = null;
            $isOnHold = $contract && $contract->hold_invoice;
            $shouldCharge = $lostUnitReport->charge_customer && (float) $lostUnitReport->charge_amount > 0;

            if (!$isOnHold && $shouldCharge) {
                $invoice = $this->generateLostUnitInvoice($lostUnitReport);

                if ($invoice) {
                    $lostUnitReport->update(['invoice_id' => $invoice->id]);
                }
            } elseif ($isOnHold) {
                \Log::info("Invoice generation skipped for LUR {$lostUnitReport->report_number} because contract {$contract->contract_number} is on Hold Invoice.");
            } else {
                \Log::info("Invoice generation skipped for LUR {$lostUnitReport->report_number} because customer is not charged (charge_customer=false or charge_amount=0).");
            }

            // NEW: Auto-generate Job Advice + Job Schedule untuk pemasangan pengganti
            $jobAdvice = $this->createReplacementJobAdvice($lostUnitReport);

            DB::commit();

            if ($isOnHold) {
                $successMessage = 'Laporan Unit Hilang berhasil di-approve dan Job Advice telah dibuat. Pembuatan invoice ditunda (Hold Invoice aktif).';
            } elseif ($invoice) {
                $successMessage = 'Laporan Unit Hilang berhasil di-approve. Invoice dan Job Advice telah dibuat.';
            } else {
                $successMessage = 'Laporan Unit Hilang berhasil di-approve dan Job Advice telah dibuat. Invoice tidak dibuat karena customer tidak dikenakan charge.';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $successMessage,
                    'invoice_id' => $invoice ? $invoice->id : null,
                    'job_advice_id' => $jobAdvice ? $jobAdvice->id : null,
                    'hold_invoice' => $isOnHold
                ]);
            }

            return back()->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Reject report
     */
    public function reject(Request $request, LostUnitReport $lostUnitReport)
    {
        // Authorization check
        $user = Auth::user();
        if (!$user->canApprove('lost_unit_reports')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk menolak laporan ini. Pastikan role Anda memiliki permission "Approve" untuk Lost Unit Reports.'
                ], 403);
            }
            return back()->with('error', 'Anda tidak memiliki akses untuk menolak laporan ini.');
        }

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        try {
            $lostUnitReport->update([
                'status' => 'rejected',
                'remark' => $request->rejection_reason,
                'updated_by' => Auth::id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Unit Hilang berhasil ditolak.'
                ]);
            }

            return back()->with('success', 'Laporan Unit Hilang berhasil ditolak.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Unpost report (return to draft)
     */
    public function unpost(Request $request, LostUnitReport $lostUnitReport)
    {
        // Check if can unpost
        if ($lostUnitReport->status === 'submitted') {
            // Can unpost from submitted
        } elseif ($lostUnitReport->status === 'approved') {
            // Can only unpost if no invoice
            if ($lostUnitReport->hasInvoice()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Report tidak dapat di-unpost karena sudah memiliki Invoice. Hapus Invoice terlebih dahulu.'
                    ], 400);
                }
                return back()->with('error', 'Report tidak dapat di-unpost karena sudah memiliki Invoice. Hapus Invoice terlebih dahulu.');
            }
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Report dengan status ini tidak dapat di-unpost.'
                ], 400);
            }
            return back()->with('error', 'Report dengan status ini tidak dapat di-unpost.');
        }

        try {
            $lostUnitReport->update([
                'status' => 'draft',
                'finalized_at' => null,
                'finalized_by' => null,
                'approve_by' => null,
                'approved_at' => null,
                'updated_by' => Auth::id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan Unit Hilang berhasil di-unpost ke status Draft.'
                ]);
            }

            return back()->with('success', 'Laporan Unit Hilang berhasil di-unpost ke status Draft.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Get rentals by contract
     */
    public function getRentalsByContract(Contract $contract)
    {
        // Get rentals from contract_rentals
        $rentals = ContractRental::with('masterRental')
            ->where('contract_id', $contract->id)
            ->get()
            ->map(function ($detail) {
                return [
                    'id' => $detail->master_rental_id,
                    'name' => $detail->masterRental->name ?? $detail->masterRental->rental_name ?? 'Unknown Rental',
                    'rental_type' => $detail->masterRental->rental_type ?? null,
                ];
            })
            ->unique('id')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $rentals
        ]);
    }

    /**
     * Get rentals by room and contract
     */
    public function getRentalsByRoom(Contract $contract, MasterRoom $room)
    {
        // Get rentals from contract_rentals for this specific room
        $rentals = ContractRental::with('masterRental')
            ->where('contract_id', $contract->id)
            ->where('room_id', $room->id)
            ->get();

        // If no room-specific rentals, try to load rentals that have room_id = NULL
        if ($rentals->isEmpty()) {
            $rentals = ContractRental::with('masterRental')
                ->where('contract_id', $contract->id)
                ->whereNull('room_id')
                ->get();
        }

        // If still empty and contract only has one rental, use that
        if ($rentals->isEmpty()) {
            $allRentals = ContractRental::with('masterRental')
                ->where('contract_id', $contract->id)
                ->get();
            if ($allRentals->count() === 1) {
                $rentals = $allRentals;
            }
        }

        $data = $rentals->map(function ($detail) {
            return [
                'id' => $detail->master_rental_id,
                'name' => $detail->masterRental->name ?? $detail->masterRental->rental_name ?? 'Unknown Rental',
                'rental_type' => $detail->masterRental->rental_type ?? null,
            ];
        })->unique('id')->values();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Get rooms by contract
     */
    public function getRoomsByContract(Contract $contract)
    {
        // Get rooms from contract_rooms yang sudah ada unitnya (installed)
        $rooms = ContractRoom::with(['room.building'])
            ->where('contract_id', $contract->id)
            ->whereHas('room.unitOnWalls', function($query) use ($contract) {
                $query->where('status', 'active')
                      ->where('customer_id', $contract->customer_id);
            })
            ->get()
            ->map(function ($contractRoom) {
                $roomName = $contractRoom->room->room_name ?? $contractRoom->room_name ?? 'Unknown Room';
                $buildingName = $contractRoom->room->building->building_name ?? null;
                
                if ($buildingName) {
                    $roomName .= " ({$buildingName})";
                }
                
                return [
                    'id' => $contractRoom->room_id,
                    'name' => $roomName,
                ];
            })
            ->unique('id')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }


    /**
     * Get lost unit price for a rental
     */
    public function getLostUnitPrice(Request $request)
    {
        $request->validate([
            'master_rental_id' => 'required|exists:master_rentals,id',
            'building_id' => 'nullable|exists:buildings,id',
        ]);

        $masterRental = MasterRental::find($request->master_rental_id);
        if (!$masterRental) {
            return response()->json([
                'status' => 'error',
                'message' => 'Master Rental not found',
                'price' => 0,
                'formatted_price' => 'Rp 0'
            ], 404);
        }

        $price = $masterRental->lost_unit_price ?? 0;
        \Log::info("Found default lost unit price for master rental {$masterRental->id}: {$price}");

        // Try to get branch-based price
        $branch = null;
        
        // Strategy 1: Check via Contract ID (Preferred)
        if ($request->filled('contract_id')) {
            // customers don't have direct branch relationship, so we don't load it
            $contract = Contract::with('branch', 'customer')->find($request->contract_id);
            if ($contract) {
                // Try to get branch from contract's branch_id (if exists)
                if ($contract->branch_id) {
                    $branch = \App\Models\Branch::find($contract->branch_id);
                }
                
                // If not found, try from contract rooms (first room's building -> branch)
                if (!$branch) {
                    $firstRoom = ContractRoom::where('contract_id', $contract->id)->with('room.building')->first();
                    if ($firstRoom && $firstRoom->room && $firstRoom->room->building) {
                        $building = $firstRoom->room->building;
                        if ($building->city_id) {
                            $branch = \App\Models\Branch::where('city_id', $building->city_id)->where('is_active', true)->first();
                        }
                        if (!$branch && $building->province_id) {
                            $branch = \App\Models\Branch::where('province_id', $building->province_id)->where('is_active', true)->first();
                        }
                    }
                }

                // If still not found, try customer's branch (less reliable but fallback)
                // However, Contract's location is more accurate for Lost Unit.
            }
        }

        // Strategy 2: Check via Building ID (Fallback/Legacy)
        if (!$branch && $request->filled('building_id')) {
            $building = \App\Models\Building::find($request->building_id);
            if ($building) {
                if ($building->city_id) {
                    $branch = \App\Models\Branch::where('city_id', $building->city_id)->where('is_active', true)->first();
                }
                if (!$branch && $building->province_id) {
                    $branch = \App\Models\Branch::where('province_id', $building->province_id)->where('is_active', true)->first();
                }
            }
        }

        if ($branch) {
            $rentalPrice = RentalPrice::where('master_rental_id', $request->master_rental_id)
                ->where('branch_id', $branch->id)
                ->first();
            
            if ($rentalPrice && $rentalPrice->lost_unit_price > 0) {
                $price = $rentalPrice->lost_unit_price;
                \Log::info("Found branch-based lost unit price for branch {$branch->id}: {$price}");
            }
        }

        return response()->json([
            'status' => 'success',
            'price' => (float)$price,
            'formatted_price' => 'Rp ' . number_format($price, 0, ',', '.')
        ]);
    }

    /**
     * Generate Invoice Penggantian untuk Lost Unit
     */
    private function generateLostUnitInvoice(LostUnitReport $report)
    {
        try {
            $contract = $report->contract;
            $unitPrice = (float) ($report->charge_amount ?? $report->lost_unit_price);
            
            // Auto-generate invoice number using DocumentNumberService
            $documentNumberService = app(\App\Services\DocumentNumberService::class);
            $invoiceNumber = $documentNumberService->generate(
                'invoice',
                null,
                null,
                $contract->id
            );
            
            // PPN applicability comes from the customer's tax code, not a boolean flag.
            $invoiceDate = now();
            $taxResolver = app(\App\Services\Finance\InvoiceTaxResolver::class);
            $taxContext = $taxResolver->resolve($contract->customer, $contract->ppn_code, $invoiceDate);
            $taxObligation = $taxContext['applies_ppn'];
            $taxAmount = $taxResolver->taxAmount((float) $unitPrice, $taxContext);
            $grandTotal = round($unitPrice + $taxAmount, 2);

            // Helper to get Billing Group
            $billingGroup = $contract->billingGroup;

            // Create invoice for unit replacement
            $invoice = \App\Models\Finance\Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $contract->customer_id,
                'billing_group_id' => $billingGroup->id ?? null, // Added
                'contract_number' => $contract->contract_number,
                'po_number' => $contract->po_number,
                'billing_address' => $billingGroup->pic_address ?? $contract->customer->address ?? '',
                'pic_finance' => $billingGroup->pic_name ?? '',
                'email' => $billingGroup->pic_email ?? $contract->customer->email ?? '',
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays(30),
                'subtotal' => $unitPrice,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'total_amount' => $grandTotal,
                'tax_setting_id' => $taxContext['default_vat_setting']?->id,
                'tax_code' => $taxContext['tax_code'],
                'kirim' => $billingGroup->invoice_type ?? 'manual',
                'invoice_status' => \App\Models\Finance\Invoice::STATUS_DRAFT,
                'status' => \App\Models\Finance\Invoice::STATUS_DRAFT,
                'payment_terms' => '30 days',
                'notes' => "Invoice penggantian unit hilang berdasarkan Lost Unit Report: {$report->report_number}",
                'reference_number' => $report->report_number,
                'tax_obligation' => $taxObligation,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Create rental details for RENTAL/DETAIL tabs, one row per lost-unit item.
            // Do NOT also create a summary InvoiceDetail row here: calculateInvoiceSubtotal()
            // and the invoice Detail tab sum invoiceDetails + invoiceRentalDetails together,
            // so adding both a summary row and per-item rows for the same charge double-counts it.
            foreach ($report->items as $item) {
                \App\Models\Finance\InvoiceRentalDetail::create([
                    'invoice_id' => $invoice->id,
                    'master_rental_id' => $item->master_rental_id,
                    'job_no' => $report->report_number,
                    'building_name' => $item->room->building->building_name ?? $report->building->building_name ?? '',
                    'room_name' => $item->room->room_name ?? $item->room_name ?? '',
                    'rental_name' => $item->masterRental->rental_name ?? $item->rental_name ?? 'Service',
                    'quantity' => 1,
                    'unit_price' => $item->price,
                    'total_price' => $item->price,
                    'created_by' => Auth::id(),
                ]);
            }

            \Log::info("Lost Unit Invoice auto-generated: {$invoiceNumber} for Report: {$report->report_number}");
            
            return $invoice;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-generate Lost Unit Invoice for Report {$report->report_number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create Job Advice for replacement installation
     */
    private function createReplacementJobAdvice(LostUnitReport $report)
    {
        try {
            $contract = $report->contract;
            
            // Generate Job Advice number
            $documentNumberService = app(\App\Services\DocumentNumberService::class);
            $jobAdviceNumber = $documentNumberService->generate(
                'job_advice',
                null,
                $report->building_id,
                $contract->id
            );

            // Create Job Advice
            $jobAdvice = \App\Models\JobAdvice::create([
                'job_advice_number' => $jobAdviceNumber,
                'type' => 'install', // Install pengganti
                'contract_id' => $contract->id,
                'customer_id' => $contract->customer_id,
                'company_name' => $contract->customer->name, // FIX: Dari customer name
                'reference_number' => $report->report_number,
                'remark' => "Pemasangan pengganti unit hilang berdasarkan Lost Unit Report: {$report->report_number}",
                'status' => 'approved', // FIX: Langsung approved
                'approved_by' => Auth::id(), // FIX: Set approver
                'date_approval' => now(), // FIX: Set approval date
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Create Job Advice Rooms dari Lost Unit Report Items
            \Log::info("Creating Job Advice Rooms for Report: {$report->report_number}, Items count: " . $report->items->count());
            
            foreach ($report->items as $item) {
                $rental = $item->masterRental;
                \Log::info("Processing item - Room ID: {$item->room_id}, Rental ID: {$item->master_rental_id}, Rental Type: {$rental->rental_type}");
                
                // Tentukan job types berdasarkan rental type
                $jobTypes = $this->determineJobTypes($rental);
                \Log::info("Job types determined: " . json_encode($jobTypes));

                $contractRoomId = $this->getContractRoomId($contract->id, $item->room_id);
                \Log::info("Contract Room ID found: " . ($contractRoomId ?? 'NULL'));
                
                $jobAdviceRoom = \App\Models\JobAdviceRoom::create([
                    'job_advice_id' => $jobAdvice->id,
                    'contract_room_id' => $contractRoomId,
                    'room_name' => $item->room->room_name,
                    'rental_product_id' => $rental->id,
                    'rental_name' => $rental->rental_name, // FIX: Add rental_name
                    'status' => 'scheduled',
                    'created_by' => Auth::id(),
                ]);
                
                \Log::info("JobAdviceRoom created with ID: {$jobAdviceRoom->id}");

                // Create Job Schedules sesuai job types
                \Log::info("Creating Job Schedules for job types: " . json_encode($jobTypes));
                foreach ($jobTypes as $jobType) {
                    $jobSchedule = \App\Models\JobSchedule::create([
                        'job_number' => null, // Penomoran ditunda sampai tahap penugasan/assignment
                        'job_advice_id' => $jobAdvice->id,
                        'type' => $jobType,
                        'building_id' => $report->building_id,
                        'building_name' => $report->building->building_name,
                        'room_id' => $item->room_id,
                        'company_name' => $contract->customer->name,
                        'schedule_date' => now(), // Tanggal report di-approve
                        'expected_date' => now()->addDays(7), // Expected completion in 7 days
                        // Always null: a replacement job carries no service period of its own.
                        'period' => null,
                        'status' => 'new_job',
                        'internal_notes' => "Auto-generated from Lost Unit Report: {$report->report_number} | Job Advice: {$jobAdvice->job_advice_number}",
                        'reference_number' => $jobAdvice->job_advice_number,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    // Create Job Schedule Room
                    \App\Models\JobScheduleRoom::create([
                        'job_schedule_id' => $jobSchedule->id,
                        'job_advice_room_id' => $jobAdviceRoom->id,
                        'room_name' => $item->room->room_name,
                        'room_id' => $item->room_id,
                        'status' => 'pending',
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            \Log::info("Job Advice auto-created for Lost Unit Report: {$report->report_number}, Job Advice: {$jobAdviceNumber}");
            
            return $jobAdvice;
            
        } catch (\Exception $e) {
            \Log::error("Failed to create Job Advice for Lost Unit Report {$report->report_number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Determine job types based on rental type
     */
    private function determineJobTypes($rental)
    {
        // A replacement only needs the unit put back on the wall. The contract's periodic
        // services keep running on their own schedule and will service whatever unit is in
        // the room, so raising a fresh service here would visit - and bill - the customer
        // twice in the same period. It used to create a `service_first` stamped period 1,
        // which on a contract already at period 5 collided with the real first period.
        // Client decision 28 Aug 2026: "service penggantinya ikut service berjalan aja".
        $jobTypes = [];

        switch ($rental->rental_type) {
            case 'unit_only':
            case 'unit_refill':
                $jobTypes = ['install'];
                break;

            case 'refill_only':
                // A refill-only rental has no unit, so there is nothing to replace.
                \Log::warning("Lost Unit Report: rental {$rental->id} is refill-only, no replacement job created.");
                $jobTypes = [];
                break;

            default:
                // Unknown rental type: assume it carries a unit, same as before.
                $jobTypes = ['install'];
                break;
        }
        
        return $jobTypes;
    }

    /**
     * Get contract room ID
     */
    private function getContractRoomId($contractId, $roomId)
    {
        $contractRoom = \App\Models\ContractRoom::where('contract_id', $contractId)
            ->where('room_id', $roomId)
            ->first();
        
        return $contractRoom ? $contractRoom->id : null;
    }
}
