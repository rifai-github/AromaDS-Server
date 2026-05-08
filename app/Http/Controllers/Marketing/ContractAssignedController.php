<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Contract;
use App\Models\ContractAssigned;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContractAssignedController extends Controller
{
    use AccessControlFilterTrait;

    public function index(Request $request)
    {
        $query = ContractAssigned::with([
            'oldContract.customer',
            'oldMarketing',
            'newMarketing',
            'initiatedBy',
            'approvedBy',
            'executedBy',
        ]);

        $query = $this->applyContractAssignedAccessFilter($query);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('switching_number', 'like', "%{$search}%")
                    ->orWhereHas('oldContract', function ($q2) use ($search) {
                        $q2->where('contract_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('oldMarketing', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('newMarketing', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('oldContract.customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'waiting_for_approval') {
                $status = 'pending_approval';
            }
            $query->where('status', $status);
        }

        $assigned = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Calculate stats
        $statsQuery = $this->applyContractAssignedAccessFilter(ContractAssigned::query());
        $stats = [
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending_approval')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
            'total' => (clone $statsQuery)->count(),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'data' => $assigned, 'stats' => $stats]);
        }

        $marketingUsers = $this->buildMarketingUserCollection()->values();
        $contractsByMarketing = $this->buildTransferableContractCollection()
            ->groupBy(fn ($contract) => (string) ($contract['marketing_id'] ?? ''))
            ->map(fn ($group) => $group->values())
            ->toArray();

        return view('marketing.contract-assigned.index', compact('assigned', 'stats', 'marketingUsers', 'contractsByMarketing'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_contract_id' => 'required|exists:contracts,id',
            'new_marketing_id' => 'required|exists:users,id',
            'switching_reason' => 'required|string|max:500',
            'switching_description' => 'nullable|string|max:2000',
            'switching_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $oldContract = Contract::findOrFail($request->old_contract_id);
            $this->ensureCanUseContractForAssignment($oldContract);

            DB::beginTransaction();

            // Validation: cannot switch to same marketing
            if ($oldContract->marketing_id == $request->new_marketing_id) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'New marketing must be different from current marketing',
                ], 422);
            }

            $assigned = ContractAssigned::create([
                'switching_number' => ContractAssigned::generateSwitchingNumber(),
                'old_contract_id' => $oldContract->id,
                'old_marketing_id' => $oldContract->marketing_id,
                'new_marketing_id' => $request->new_marketing_id,
                'switching_reason' => $request->switching_reason,
                'switching_description' => $request->switching_description,
                'switching_notes' => $request->switching_notes,
                'status' => ContractAssigned::STATUS_DRAFT,
                'initiated_by' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            Log::info("Contract Assigned created: {$assigned->switching_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Contract assigned created successfully',
                'data' => $assigned->load(['oldContract', 'oldMarketing', 'newMarketing']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating contract assigned: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create contract assigned: '.$e->getMessage(),
            ], 500);
        }
    }

    public function show(ContractAssigned $contractAssigned)
    {
        $this->authorizeContractAssignedAccess($contractAssigned);

        $contractAssigned->load([
            'oldContract.customer',
            'oldMarketing',
            'newMarketing',
            'initiatedBy',
            'approvedBy',
            'executedBy',
        ]);

        return response()->json(['status' => 'success', 'data' => $contractAssigned]);
    }

    public function submitForApproval(ContractAssigned $contractAssigned)
    {
        $this->authorizeContractAssignedAccess($contractAssigned);

        try {
            $contractAssigned->submitForApproval();
            Log::info("Contract Assigned submitted: {$contractAssigned->switching_number}");

            return response()->json(['status' => 'success', 'message' => 'Assignment submitted for approval']);
        } catch (\Exception $e) {
            Log::error('Error submitting assignment: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function approve(Request $request, ContractAssigned $contractAssigned)
    {
        $this->authorizeContractAssignedAccess($contractAssigned);

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $contractAssigned->approve(Auth::id(), $request->approval_notes);
            Log::info("Contract Assigned approved: {$contractAssigned->switching_number}");

            return response()->json(['status' => 'success', 'message' => 'Assignment approved successfully']);
        } catch (\Exception $e) {
            Log::error('Error approving assignment: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, ContractAssigned $contractAssigned)
    {
        $this->authorizeContractAssignedAccess($contractAssigned);

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $contractAssigned->reject(Auth::id(), $request->rejection_reason);
            Log::info("Contract Assigned rejected: {$contractAssigned->switching_number}");

            return response()->json(['status' => 'success', 'message' => 'Assignment rejected']);
        } catch (\Exception $e) {
            Log::error('Error rejecting assignment: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel(ContractAssigned $contractAssigned)
    {
        $this->authorizeContractAssignedAccess($contractAssigned);

        try {
            $contractAssigned->cancel();
            Log::info("Contract Assigned cancelled: {$contractAssigned->switching_number}");

            return response()->json(['status' => 'success', 'message' => 'Assignment cancelled']);
        } catch (\Exception $e) {
            Log::error('Error cancelling assignment: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Execute contract assignment - Transfer marketing responsibility
     */
    public function execute(ContractAssigned $contractAssigned)
    {
        $this->authorizeContractAssignedAccess($contractAssigned);

        try {
            $contract = $contractAssigned->execute(
                Auth::id(),
                true,
                'Auto-approved during direct execute'
            );

            Log::info("Contract Assigned executed: {$contractAssigned->switching_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Contract marketing assigned successfully',
                'data' => [
                    'assigned' => $contractAssigned->fresh(),
                    'contract' => $contract,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error executing assignment: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get active contracts for dropdown
     */
    public function getContracts(Request $request)
    {
        $contracts = $this->buildTransferableContractCollection($request->input('marketing_id'))->values();

        return response()->json(['status' => 'success', 'data' => $contracts]);
    }

    /**
     * Get marketing users for dropdown
     * Filter by: Marketing Department OR Marketing/Sales Role
     */
    public function getMarketingUsers(Request $request)
    {
        $marketingUsers = $this->buildMarketingUserCollection()->values();

        return response()->json(['status' => 'success', 'data' => $marketingUsers]);
    }

    protected function buildTransferableContractCollection($marketingId = null)
    {
        $activeStatuses = ['active', 'approved', 'aktif', 'Active', 'Approved', 'Aktif'];
        $accessibleMarketingIds = $this->getContractAssignedAccessibleUserIds();

        $query = Contract::with(['customer', 'marketing'])
            ->whereNull('deleted_at')
            ->where(function ($q) use ($activeStatuses) {
                $q->whereIn('contract_status', $activeStatuses)
                    ->orWhereIn('status', $activeStatuses);
            });

        if ($accessibleMarketingIds !== null) {
            $query->whereIn('marketing_id', $accessibleMarketingIds);
        }

        if (! empty($marketingId)) {
            $query->where('marketing_id', $marketingId);
        }

        return $query->orderBy('contract_number', 'desc')
            ->get()
            ->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer' => [
                        'name' => $contract->customer->name ?? '-',
                    ],
                    'customer_name' => $contract->customer->name ?? '-',
                    'current_marketing' => $contract->marketing->name ?? '-',
                    'marketing_id' => $contract->marketing_id,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'status' => $contract->contract_status ?: $contract->status ?: 'active',
                ];
            });
    }

    protected function buildMarketingUserCollection()
    {
        $accessibleMarketingIds = $this->getContractAssignedAccessibleUserIds();

        $query = User::with(['department', 'roles'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereHas('department', function ($q) {
                    $q->where('name', 'LIKE', '%marketing%')
                        ->orWhere('name', 'LIKE', '%sales%');
                })
                    ->orWhere('department_name', 'LIKE', '%marketing%')
                    ->orWhere('department_name', 'LIKE', '%sales%')
                    ->orWhere('position_name', 'LIKE', '%marketing%')
                    ->orWhere('position_name', 'LIKE', '%sales%')
                    ->orWhereHas('roles', function ($q) {
                        $q->where('name', 'LIKE', '%marketing%')
                            ->orWhere('name', 'LIKE', '%sales%');
                    })
                    ->orWhere('roles', 'LIKE', '%marketing%')
                    ->orWhere('roles', 'LIKE', '%sales%');
            });

        if ($accessibleMarketingIds !== null) {
            $query->whereIn('id', $accessibleMarketingIds);
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'position_name', 'department_name'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'position_name' => $user->position_name,
                    'department_name' => $user->department_name,
                ];
            });
    }

    protected function applyContractAssignedAccessFilter($query, ?User $user = null)
    {
        $accessibleUserIds = $this->getContractAssignedAccessibleUserIds($user);

        if ($accessibleUserIds === null) {
            return $query;
        }

        return $query->where(function ($q) use ($accessibleUserIds) {
            $q->whereIn('created_by', $accessibleUserIds)
                ->orWhereIn('initiated_by', $accessibleUserIds)
                ->orWhereIn('old_marketing_id', $accessibleUserIds)
                ->orWhereIn('new_marketing_id', $accessibleUserIds);
        });
    }

    protected function getContractAssignedAccessibleUserIds(?User $user = null): ?array
    {
        $user ??= Auth::user();

        if (! $user) {
            return [];
        }

        if ($user->hasRoleStartingWith('Management')) {
            return null;
        }

        $hasCompanyAccess = $user->accessLevels()
            ->where('access_type', 'company')
            ->where('is_active', true)
            ->exists();

        if ($hasCompanyAccess) {
            return null;
        }

        return $this->getAccessibleUserIds($user);
    }

    protected function authorizeContractAssignedAccess(ContractAssigned $contractAssigned): void
    {
        $canAccess = $this->applyContractAssignedAccessFilter(
            ContractAssigned::whereKey($contractAssigned->getKey())
        )->exists();

        abort_unless($canAccess, 403, 'You do not have access to this contract assignment.');
    }

    protected function ensureCanUseContractForAssignment(Contract $contract): void
    {
        $accessibleMarketingIds = $this->getContractAssignedAccessibleUserIds();

        if ($accessibleMarketingIds === null) {
            return;
        }

        abort_unless(
            in_array((int) $contract->marketing_id, array_map('intval', $accessibleMarketingIds), true),
            403,
            'You do not have access to this contract.'
        );
    }
}
