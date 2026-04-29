<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MasterCorporate;
use App\Models\Customer;
use App\Models\MasterRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DataTables;

class MasterCorporateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Group by Code for the list view
        $query = MasterCorporate::query()
            ->select('code', 'customer_id', 'created_by')
            ->selectRaw('MAX(created_at) as created_at')
            ->selectRaw('MAX(id) as id')
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw('SUM(CASE WHEN status = "' . MasterCorporate::STATUS_APPROVED . '" THEN 1 ELSE 0 END) as approved_count')
            ->selectRaw('SUM(CASE WHEN status = "' . MasterCorporate::STATUS_REJECTED . '" THEN 1 ELSE 0 END) as rejected_count')
            ->selectRaw('SUM(CASE WHEN status = "' . MasterCorporate::STATUS_DRAFT . '" THEN 1 ELSE 0 END) as draft_count')
            ->selectRaw('SUM(CASE WHEN status = "' . MasterCorporate::STATUS_WAITING_APPROVAL . '" THEN 1 ELSE 0 END) as waiting_count')
            ->with(['customer', 'createdBy', 'items.updatedBy', 'items.approvedBy']);

        // Filter by Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $status = $request->status;
            // Use subquery to select groups that have at least one item with the given status
            $query->whereIn('code', function($q) use ($status) {
                $q->select('code')
                  ->from((new MasterCorporate())->getTable())
                  ->where('status', $status)
                  ->whereNull('deleted_at');
            });
        }

        $masterCorporates = $query->groupBy('code', 'customer_id', 'created_by')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Stats
        // Stats
        $stats = [
            'total_groups' => MasterCorporate::distinct('code')->count(),
            'total_items' => MasterCorporate::count(),
            'total_customers' => MasterCorporate::distinct('customer_id')->count(),
            'total_rentals' => MasterCorporate::distinct('master_rental_id')->count(),
        ];

        $customers = \App\Models\Customer::orderBy('name')->get(); // For filter dropdown

        return view('marketing.master-corporates.index', compact('masterCorporates', 'stats', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $rentals = MasterRental::active()->orderBy('rental_name')->get();
        return view('marketing.master-corporates.create', compact('customers', 'rentals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'rentals' => 'required|array',
            'rentals.*.master_rental_id' => 'required|exists:master_rentals,id',
            'rentals.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $code = MasterCorporate::generateCode();

            foreach ($request->rentals as $rental) {
                MasterCorporate::create([
                    'code' => $code,
                    'customer_id' => $request->customer_id,
                    'master_rental_id' => $rental['master_rental_id'],
                    'price' => $rental['price'],
                    'status' => MasterCorporate::STATUS_DRAFT,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->route('marketing.master-corporates.index')
                ->with('success', 'Master Corporate created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error storing Master Corporate: ' . $e->getMessage());
            return back()->with('error', 'Failed to create Master Corporate: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource group.
     */
    public function show($id)
    {
        // Try to find by ID first
        $item = MasterCorporate::find($id);

        if (!$item) {
             // Fallback: Check if it's a code
            $items = MasterCorporate::where('code', $id)
                ->with(['masterRental', 'approvedBy', 'createdBy'])
                ->get();
            
            if ($items->isEmpty()) {
                abort(404);
            }
        } else {
             $items = MasterCorporate::where('code', $item->code)
                ->with(['masterRental', 'approvedBy', 'createdBy'])
                ->get();
        }

        $firstItem = $items->first();
        $code = $firstItem->code;
        $customer = $firstItem->customer;

        return view('marketing.master-corporates.show', compact('items', 'customer', 'code', 'firstItem'));
    }

    /**
     * Submit all items in a group for approval.
     */
    public function submitGroup($code)
    {
        try {
            DB::beginTransaction();
            
            MasterCorporate::where('code', $code)
                ->where('status', MasterCorporate::STATUS_DRAFT)
                ->update([
                    'status' => MasterCorporate::STATUS_WAITING_APPROVAL,
                    'updated_by' => auth()->id()
                ]);

            DB::commit();
            return back()->with('success', 'Submitted for approval successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error submitting: ' . $e->getMessage());
        }
    }

    /**
     * Approve selected items
     */
    public function approve(Request $request, $code)
    {
        if (!auth()->user()->canApprove('master-corporates')) {
            return back()->with('error', 'Unauthorized access. You do not have permission to approve master corporates.');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:master_corporates,id'
        ]);

        try {
            DB::beginTransaction();

            MasterCorporate::whereIn('id', $request->ids)
                ->where('code', $code)
                ->update([
                    'status' => MasterCorporate::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'updated_by' => auth()->id()
                ]);

            DB::commit();
            return back()->with('success', 'Selected items approved successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error approving: ' . $e->getMessage());
        }
    }

    /**
     * Reject selected items
     */
    public function reject(Request $request, $code)
    {
        if (!auth()->user()->canApprove('master-corporates')) {
            return back()->with('error', 'Unauthorized access. You do not have permission to approve master corporates.');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:master_corporates,id',
            'note' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            MasterCorporate::whereIn('id', $request->ids)
                ->where('code', $code)
                ->update([
                    'status' => MasterCorporate::STATUS_REJECTED,
                    'approval_notes' => $request->note,
                    'approved_by' => auth()->id(), // Still track who rejected
                    'approved_at' => now(),
                    'updated_by' => auth()->id()
                ]);

            DB::commit();
            return back()->with('success', 'Selected items rejected successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error rejecting: ' . $e->getMessage());
        }
    }

    /**
     * Unpost (Validation Revert)
     */
    public function unpost($code)
    {
        if (!auth()->user()->canApprove('master-corporates')) {
             return back()->with('error', 'Unauthorized access. You do not have permission to unpost master corporates.');
        }

        try {
            DB::beginTransaction();

            $updated = MasterCorporate::where('code', $code)
                ->where('status', MasterCorporate::STATUS_APPROVED)
                ->update([
                    'status' => MasterCorporate::STATUS_DRAFT,
                    'approved_by' => null,
                    'approved_at' => null,
                    'updated_by' => auth()->id()
                ]);

            if ($updated === 0) {
                 return back()->with('warning', 'No approved items found to unpost.');
            }

            DB::commit();
            return back()->with('success', 'Master Corporate unposted successfully. Status reverted to Draft.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error unposting: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterCorporate $masterCorporate)
    {
        if ($masterCorporate->status == MasterCorporate::STATUS_APPROVED) {
            return back()->with('error', 'Cannot edit approved Master Corporate.');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $rentals = MasterRental::active()->orderBy('rental_name')->get();
        return view('marketing.master-corporates.edit', compact('masterCorporate', 'customers', 'rentals'));
    }

    /**
     * Edit Group (All items under a code)
     */
    public function editGroup($code)
    {
        $items = MasterCorporate::where('code', $code)->get();
        
        if ($items->isEmpty()) {
            return redirect()->route('marketing.master-corporates.index')->with('error', 'Group not found.');
        }

        // Check if any item is approved (prevent editing if so, unless we want to allow editing draft items in a mixed group? 
        // For simplicity and safety, block if ANY is approved, user must Unpost first.
        if ($items->where('status', MasterCorporate::STATUS_APPROVED)->count() > 0) {
             return redirect()->route('marketing.master-corporates.show', $code)->with('error', 'Cannot edit. Some items are approved. Please Unpost first.');
        }

        $customer = $items->first()->customer;
        $rentals = MasterRental::orderBy('rental_name')->get();

        return view('marketing.master-corporates.edit-group', compact('items', 'customer', 'rentals', 'code'));
    }

    /**
     * Update Group
     */
    public function updateGroup(Request $request, $code)
    {
        $request->validate([
            'rentals' => 'required|array|min:1',
            'rentals.*.master_rental_id' => 'required|exists:master_rentals,id',
            'rentals.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $submittedIds = [];
            $items = MasterCorporate::where('code', $code)->get();
            $firstItem = $items->first();

            foreach ($request->rentals as $rentalData) {
                if (isset($rentalData['id'])) {
                    // Update existing
                    $item = MasterCorporate::find($rentalData['id']);
                    if ($item && $item->code === $code) {
                        $item->update([
                            'master_rental_id' => $rentalData['master_rental_id'],
                            'price' => $rentalData['price'],
                            'updated_by' => auth()->id() // Track updater
                        ]);
                        $submittedIds[] = $item->id;
                    }
                } else {
                    // Create new
                    $newItem = MasterCorporate::create([
                        'code' => $code, // Keep same code
                        'customer_id' => $firstItem->customer_id, // Keep same customer
                        'master_rental_id' => $rentalData['master_rental_id'],
                        'price' => $rentalData['price'],
                        'status' => MasterCorporate::STATUS_DRAFT,
                        'created_by' => auth()->id(), // Track creator of new item
                    ]);
                    $submittedIds[] = $newItem->id;
                }
            }

            // Delete removed items
            MasterCorporate::where('code', $code)
                ->whereNotIn('id', $submittedIds)
                ->delete();

            DB::commit();

            return redirect()->route('marketing.master-corporates.show', $code)
                ->with('success', 'Master Corporate updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error updating: ' . $e->getMessage())->withInput();
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterCorporate $masterCorporate)
    {
        if ($masterCorporate->status == MasterCorporate::STATUS_APPROVED) {
             return response()->json(['error' => 'Cannot delete approved Master Corporate.'], 403);
        }

        try {
            $masterCorporate->delete();
            return response()->json(['success' => 'Master Corporate deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete Master Corporate'], 500);
        }
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroyGroup(\Illuminate\Http\Request $request, $code)
    {
        if ($code === 'bulk') {
            $codes = explode(',', $request->input('codes'));
            if (empty($codes)) {
                 return back()->with('error', 'No items selected for deletion.');
            }

             // Check if any item in the selected groups is approved
            $hasApproved = MasterCorporate::whereIn('code', $codes)
                ->where('status', MasterCorporate::STATUS_APPROVED)
                ->exists();

            if ($hasApproved) {
                return back()->with('error', 'Cannot delete selected submissions because one or more contain approved items.');
            }

            try {
                DB::beginTransaction();
                MasterCorporate::whereIn('code', $codes)->delete();
                DB::commit();
                
                return redirect()->route('marketing.master-corporates.index')
                    ->with('success', 'Selected submissions deleted successfully.');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'Failed to delete submissions: ' . $e->getMessage());
            }
        }

        // Check if any item in the group is approved
        $hasApproved = MasterCorporate::where('code', $code)
            ->where('status', MasterCorporate::STATUS_APPROVED)
            ->exists();

        if ($hasApproved) {
            return back()->with('error', 'Cannot delete submission because it contains approved items.');
        }

        try {
            DB::beginTransaction();
            MasterCorporate::where('code', $code)->delete();
            DB::commit();
            
            return redirect()->route('marketing.master-corporates.index')
                ->with('success', 'Submission deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to delete submission: ' . $e->getMessage());
        }
    }

    // Approval Workflow Methods


}
