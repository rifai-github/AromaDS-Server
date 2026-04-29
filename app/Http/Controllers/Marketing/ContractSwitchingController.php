<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContractSwitching;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ContractSwitchingController extends Controller
{
    /**
     * Display a listing of contract switchings.
     */
    public function index(Request $request)
    {
        $query = ContractSwitching::with([
            'oldContract.customer',
            'oldCustomer',
            'newCustomer',
            'newContract',
            'initiatedBy',
            'approvedBy',
            'executedBy'
        ])->latest();

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('switching_number', 'like', "%{$search}%")
                  ->orWhereHas('oldContract', fn($q) => $q->where('contract_number', 'like', "%{$search}%"))
                  ->orWhereHas('oldCustomer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('newCustomer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'waiting_for_approval') {
                $status = 'pending_approval';
            }
            $query->where('status', $status);
        }

        $assigned = $query->paginate(15)->withQueryString(); // Use 'assigned' to match view variable

        // Stats for dashboard
        $allData = ContractSwitching::all();
        $stats = [
            'draft' => $allData->where('status', ContractSwitching::STATUS_DRAFT)->count(),
            'pending' => $allData->where('status', ContractSwitching::STATUS_PENDING_APPROVAL)->count(),
            'approved' => $allData->where('status', ContractSwitching::STATUS_APPROVED)->count(),
            'completed' => $allData->where('status', ContractSwitching::STATUS_COMPLETED)->count(),
            'total' => $allData->count()
        ];

        return view('marketing.contract-switchings.index', compact('assigned', 'stats'));
    }

    /**
     * Show the form for creating a new contract switching.
     */
    public function create()
    {
        return view('marketing.contract-switchings.create');
    }

    /**
     * Store a newly created contract switching.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_contract_id' => 'required|exists:contracts,id',
            'new_customer_id' => 'required|exists:customers,id',
            'switching_reason' => 'required|string',
            'switching_description' => 'nullable|string',
            'switching_notes' => 'nullable|string',
            'continue_period' => 'boolean',
            'continue_top' => 'boolean',
            'reset_dates' => 'boolean',
            'continue_from_period' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get old contract and customer
            $oldContract = Contract::findOrFail($request->old_contract_id);

            // Validate: new customer must be different from old customer
            if ($oldContract->customer_id == $request->new_customer_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'New customer must be different from current customer'
                ], 422);
            }

            $switching = ContractSwitching::create([
                'switching_number' => ContractSwitching::generateSwitchingNumber(),
                'old_contract_id' => $oldContract->id,
                'old_customer_id' => $oldContract->customer_id,
                'new_customer_id' => $request->new_customer_id,
                'switching_reason' => $request->switching_reason,
                'switching_description' => $request->switching_description,
                'switching_notes' => $request->switching_notes,
                'continue_period' => $request->continue_period ?? true,
                'continue_top' => $request->continue_top ?? true,
                'reset_dates' => $request->reset_dates ?? false,
                'continue_from_period' => $request->continue_from_period,
                'status' => ContractSwitching::STATUS_DRAFT,
                'initiated_by' => auth()->id(),
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Contract switching created successfully',
                'data' => $switching->load(['oldContract', 'oldCustomer', 'newCustomer'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating contract switching: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create contract switching: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified contract switching.
     */
    public function show(ContractSwitching $contractSwitching)
    {
        $contractSwitching->load([
            'oldContract.customer',
            'oldCustomer',
            'newCustomer',
            'newContract',
            'initiatedBy',
            'approvedBy',
            'rejectedBy',
            'executedBy'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $contractSwitching
        ]);
    }

    /**
     * Update the specified contract switching.
     */
    public function update(Request $request, ContractSwitching $contractSwitching)
    {
        // Only draft status can be updated
        if ($contractSwitching->status !== ContractSwitching::STATUS_DRAFT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft switching can be updated'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'old_contract_id' => 'required|exists:contracts,id',
            'new_customer_id' => 'required|exists:customers,id',
            'switching_reason' => 'required|string',
            'switching_description' => 'nullable|string',
            'switching_notes' => 'nullable|string',
            'continue_period' => 'boolean',
            'continue_top' => 'boolean',
            'reset_dates' => 'boolean',
            'continue_from_period' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldContract = Contract::findOrFail($request->old_contract_id);

            // Validate: new customer must be different from old customer
            if ($oldContract->customer_id == $request->new_customer_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'New customer must be different from current customer'
                ], 422);
            }

            $contractSwitching->update([
                'old_contract_id' => $oldContract->id,
                'old_customer_id' => $oldContract->customer_id,
                'new_customer_id' => $request->new_customer_id,
                'switching_reason' => $request->switching_reason,
                'switching_description' => $request->switching_description,
                'switching_notes' => $request->switching_notes,
                'continue_period' => $request->continue_period ?? true,
                'continue_top' => $request->continue_top ?? true,
                'reset_dates' => $request->reset_dates ?? false,
                'continue_from_period' => $request->continue_from_period,
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Contract switching updated successfully',
                'data' => $contractSwitching->load(['oldContract', 'oldCustomer', 'newCustomer'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating contract switching: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update contract switching: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified contract switching.
     */
    public function destroy(ContractSwitching $contractSwitching)
    {
        // Only draft status can be deleted
        if ($contractSwitching->status !== ContractSwitching::STATUS_DRAFT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft switching can be deleted'
            ], 422);
        }

        try {
            $contractSwitching->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Contract switching deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting contract switching: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete contract switching'
            ], 500);
        }
    }

    /**
     * Submit switching for approval
     */
    public function submitForApproval(ContractSwitching $contractSwitching)
    {
        try {
            $contractSwitching->submitForApproval();

            return response()->json([
                'status' => 'success',
                'message' => 'Switching submitted for approval'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Approve switching
     */
    public function approve(Request $request, ContractSwitching $contractSwitching)
    {
        try {
            $contractSwitching->approve(
                auth()->id(),
                $request->approval_notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Switching approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reject switching
     */
    public function reject(Request $request, ContractSwitching $contractSwitching)
    {
        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        try {
            $contractSwitching->reject(
                auth()->id(),
                $request->rejection_reason
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Switching rejected'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel switching
     */
    public function cancel(ContractSwitching $contractSwitching)
    {
        try {
            $contractSwitching->cancel();

            return response()->json([
                'status' => 'success',
                'message' => 'Switching cancelled'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Execute switching - Transfer customer PT ABC → PT DGH
     */
    public function execute(ContractSwitching $contractSwitching)
    {
        try {
            $newContract = $contractSwitching->execute(auth()->id());

            return response()->json([
                'status' => 'success',
                'message' => 'Switching executed successfully! New contract created.',
                'data' => [
                    'switching' => $contractSwitching->fresh(),
                    'new_contract' => $newContract
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error executing contract switching: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to execute switching: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active contracts for switching
     */
    public function getActiveContracts(Request $request)
    {
        $columns = collect(['id', 'contract_number', 'customer_id', 'start_date', 'end_date'])
            ->filter(fn ($column) => Schema::hasColumn('contracts', $column))
            ->values()
            ->all();

        $contracts = Contract::withoutGlobalScope('autoFilter')
            ->where('status', 'active')
            ->with('customer')
            ->orderBy('contract_number')
            ->get($columns);

        return response()->json([
            'status' => 'success',
            'data' => $contracts
        ]);
    }

    /**
     * Get customers (for new customer selection)
     */
    public function getCustomers(Request $request)
    {
        $customers = Customer::withoutGlobalScope('autoFilter')
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('status', 'active');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'customer_code', 'email', 'phone']);

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }
}
