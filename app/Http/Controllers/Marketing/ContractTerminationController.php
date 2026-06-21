<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContractTermination;
use App\Models\Contract;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractTerminationController extends Controller
{
    protected $documentNumberService;

    public function __construct(DocumentNumberService $documentNumberService)
    {
        $this->documentNumberService = $documentNumberService;
    }

    private function shouldCreateRemoveJobForContractRoom(\App\Models\ContractRoom $contractRoom): bool
    {
        $rentalType = strtolower(trim((string) ($contractRoom->rental_product?->rental_type ?? '')));

        if ($rentalType === 'refill_only') {
            return false;
        }

        return true;
    }

    private function findBlockingUnfinishedJobForTermination(Contract $contract): ?JobSchedule
    {
        return JobSchedule::where('contract_number', $contract->contract_number)
            ->whereNotIn('status', ['completed', 'done_job', 'cancelled', 'terminated', 'suspend', 'dpf'])
            ->get()
            ->first(fn (JobSchedule $job) => $this->jobBlocksContractTermination($job));
    }

    private function jobBlocksContractTermination(JobSchedule $job): bool
    {
        $type = strtolower(trim(str_replace('-', '_', (string) $job->type)));
        $status = strtolower(trim((string) $job->status));

        $stoppableServiceTypes = [
            'service',
            'service_first',
            'service first',
            'service_routine',
            'service routine',
            'csr',
            'customer_service_report',
            'customer service report',
            'check',
        ];

        $notStartedStatuses = [
            'new_job',
            'scheduled',
            'assign_team',
            'assign_material',
            'barang_dipersiapkan',
            'barang_siap_diambil',
        ];

        return ! (
            in_array($type, $stoppableServiceTypes, true)
            && in_array($status, $notStartedStatuses, true)
        );
    }

    /**
     * Find the first removal job that still blocks rollback of a contract termination.
     * Unpost is only allowed when all generated remove jobs are back to a fresh state.
     */
    private function findBlockingRemoveJob(ContractTermination $contractTermination): ?JobSchedule
    {
        $contract = $contractTermination->contract;

        if (!$contract) {
            return null;
        }

        return JobSchedule::where('contract_number', $contract->contract_number)
            ->whereIn('type', ['remove', 'remove_free', 'remove free'])
            ->where(function ($query) use ($contractTermination) {
                $query->where('reference_number', $contractTermination->termination_number)
                    ->orWhere('notes', 'like', '%' . $contractTermination->termination_number . '%');
            })
            ->where(function ($query) {
                $query->whereNotIn('status', ['new_job', 'scheduled', 'assigned', 'prepared', 'issued'])
                    ->orWhereNotNull('assigned_technician_id')
                    ->orWhereNotNull('ba_date')
                    ->orWhereHas('jobAssignSchedules', function ($assignmentQuery) {
                        $assignmentQuery->where('status', '!=', 'cancelled');
                    })
                    ->orWhereHas('jobScheduleRoomAssignments', function ($roomAssignmentQuery) {
                        $roomAssignmentQuery->where('status', '!=', 'cancelled')
                            ->whereNull('deleted_at');
                    });
            })
            ->first();
    }

    /**
     * Display a listing of contract terminations
     */
    public function index(Request $request)
    {
        $query = ContractTermination::with([
            'contract.customer',
            'requestedBy',
            'approvedBy',
            'createdBy',
            'updatedBy'
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('termination_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('contract', function ($q2) use ($search) {
                        $q2->where('contract_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($q3) use ($search) {
                                $q3->where('name', 'like', "%{$search}%");
                                
                            });
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->status;
            // Map waiting_for_approval to pending_approval for consistency with other modules
            if ($status === 'waiting_for_approval') {
                $status = 'pending_approval';
            }
            $query->where('status', $status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $terminations = $query->orderBy('created_at', 'desc')->paginateStd(25)->withQueryString();

        // Validasi Manual Cleanup: Cek apakah pengerjaan sudah berjalan
        foreach ($terminations as $t) {
            $t->is_unpostable = true;
            $t->unpostable_reason = '';

            if ($t->status === 'approved' && $t->contract) {
                $dirtyJob = $this->findBlockingRemoveJob($t);

                if ($dirtyJob) {
                    $t->is_unpostable = false;
                    $t->unpostable_reason = 'Job Remove (' . ($dirtyJob->job_number ?? 'tanpa nomor') . ') belum kembali ke New Job. Silakan unpost BA/job remove lalu unassign team sampai statusnya kembali New Job.';
                }
            }
        }

        // Statistics
        $statistics = [
            'total' => ContractTermination::count(),
            'draft' => ContractTermination::where('status', 'draft')->count(),
            'pending_approval' => ContractTermination::where('status', 'pending_approval')->count(),
            'approved' => ContractTermination::where('status', 'approved')->count(),
            'rejected' => ContractTermination::where('status', 'rejected')->count(),
        ];

        return view('marketing.contract-terminations.index', compact('terminations', 'statistics'));
    }

    /**
     * Get data for create modal
     */
    public function create()
    {
        // Get active contracts
        $contracts = Contract::with('customer')
            ->where('contract_status', 'active')
            ->whereHas('jobSchedules', function($q) {
                $q->whereNotNull('ba_date');
            })
            ->orderBy('contract_number')
            ->get();

        // Get termination reasons from master options
        $masterOption = MasterOption::where('name', 'Termination Reason')->first();
        $reasons = [];
        if ($masterOption) {
            $reasons = OptionDetail::where('master_option_id', $masterOption->id)
                ->where('is_active', true)
                ->orderBy('option_name')
                ->get();
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'contracts' => $contracts,
                    'reasons' => $reasons,
                    'current_user' => Auth::user(),
                ]
            ]);
        }

        return view('marketing.contract-terminations.create', compact('contracts', 'reasons'));
    }

    /**
     * Store a newly created contract termination
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'reason' => 'required|string',
            'penalty_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $contract = Contract::findOrFail($request->contract_id);

            // MOM14: Validation for unfinished jobs before creating Contract Termination.
            // Future service/check schedules may be intentionally stopped by this termination.
            $unfinishedJob = $this->findBlockingUnfinishedJobForTermination($contract);

            if ($unfinishedJob) {
                $errorMsg = "maaf job advice untuk referensi no {$contract->contract_number} tidak dapat di buat karena masih ada pekerjaan bernomor {$unfinishedJob->job_number} yang belum selesai";
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMsg
                    ], 422);
                }
                
                return back()->with('error', $errorMsg);
            }
            // Generate termination number
            $terminationNumber = $this->documentNumberService->generate(
                'contract_termination',
                null,
                null,
                $contract->id
            );

            $termination = ContractTermination::create([
                'termination_number' => $terminationNumber,
                'contract_id' => $contract->id,
                'customer_id' => $contract->customer_id, // Auto-fill from contract
                'reason' => $request->reason,
                'penalty_amount' => $request->penalty_amount,
                'notes' => $request->notes,
                'status' => 'draft',
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Contract termination created successfully.',
                    'data' => $termination->load('contract.customer')
                ]);
            }

            return redirect()->route('marketing.contract-terminations.show', $termination)
                ->with('success', 'Contract termination created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create contract termination: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create contract termination: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to create contract termination: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified contract termination
     */
    public function show(ContractTermination $contractTermination)
    {
        $contractTermination->load([
            'contract.customer',
            'contract.billingGroups.buildings',
            'requestedBy',
            'approvedBy',
            'createdBy',
            'updatedBy'
        ]);

        // Validasi Manual Cleanup Status
        $contractTermination->is_unpostable = true;
        $contractTermination->unpostable_reason = '';

        if ($contractTermination->status === 'approved' && $contractTermination->contract) {
            $dirtyJob = $this->findBlockingRemoveJob($contractTermination);

            if ($dirtyJob) {
                $contractTermination->is_unpostable = false;
                $contractTermination->unpostable_reason = 'Job Remove (' . ($dirtyJob->job_number ?? 'tanpa nomor') . ') belum kembali ke New Job. Silakan unpost BA/job remove lalu unassign team sampai statusnya kembali New Job di menu Job Schedule.';
            }
        }

        return view('marketing.contract-terminations.show', compact('contractTermination'));
    }

    /**
     * Update the specified contract termination (only if draft)
     */
    public function update(Request $request, ContractTermination $contractTermination)
    {
        // Only allow update if status is draft
        if ($contractTermination->status !== 'draft') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Can only update termination in draft status.'
                ], 400);
            }
            return back()->with('error', 'Can only update termination in draft status.');
        }

        $request->validate([
            'reason' => 'required|string',
            'penalty_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $contractTermination->update([
                'reason' => $request->reason,
                'penalty_amount' => $request->penalty_amount,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Contract termination updated successfully.',
                    'data' => $contractTermination
                ]);
            }

            return redirect()->route('marketing.contract-terminations.show', $contractTermination)
                ->with('success', 'Contract termination updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update contract termination: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update contract termination: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update contract termination: ' . $e->getMessage());
        }
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(ContractTermination $contractTermination)
    {
        if ($contractTermination->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only submit draft termination for approval.'
            ], 400);
        }

        try {
            $contractTermination->update([
                'status' => 'pending_approval',
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Contract termination submitted for approval.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit contract termination: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit contract termination: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve contract termination
     */
    public function approve(Request $request, ContractTermination $contractTermination)
    {
        // Check permission using canApprove (checks permission checkbox first)
        $user = Auth::user();
        
        if (!$user->canApprove('contract_terminations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk approve Contract Termination. Pastikan role Anda memiliki permission "Approve" untuk Contract Terminations.'
            ], 403);
        }

        if ($contractTermination->status !== 'pending_approval') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only approve termination in pending approval status.'
            ], 400);
        }

        $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update termination status
            $contractTermination->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes,
                'updated_by' => Auth::id(),
            ]);

            // Update contract status to terminated
            $contract = $contractTermination->contract;
            $contract->update([
                'status' => 'terminated',
                'contract_status' => 'terminated',
                'updated_by' => Auth::id(),
            ]);

            // Get all JobAdvice IDs for this contract 
            $jobAdviceIds = \App\Models\JobAdvice::where('contract_id', $contract->id)->pluck('id');
            
            // Terminate all pending/in-progress job schedules
            $terminatedJobCount = 0;
            if ($jobAdviceIds->isNotEmpty()) {
                $terminatedJobCount = JobSchedule::whereIn('job_advice_id', $jobAdviceIds)
                    ->whereNotIn('status', ['completed', 'cancelled', 'done_job', 'terminated'])
                    ->update([
                        'status' => 'terminated',
                        'notes' => 'Terminated due to contract termination: ' . $contractTermination->termination_number,
                        'updated_by' => Auth::id(),
                    ]);
            }

            // Also terminate jobs linked directly to contract (if any)
            $directTerminatedCount = JobSchedule::where('contract_number', $contract->contract_number)
                ->whereNotIn('status', ['completed', 'cancelled', 'done_job', 'terminated'])
                ->update([
                    'status' => 'terminated',
                    'notes' => 'Terminated due to contract termination: ' . $contractTermination->termination_number,
                    'updated_by' => Auth::id(),
                ]);
            
            $terminatedJobCount += $directTerminatedCount;

            // Auto-generate Job Schedule type 'remove' only for rooms that have a physical unit.
            $contractRooms = \App\Models\ContractRoom::where('contract_id', $contract->id)->get();
            $removeJobsCreated = 0;
            $refillOnlyRoomsSkipped = 0;
            
            // Get building ID from contract
            $buildingId = $contract->building_id;
            if (!$buildingId && $contract->quotation && $contract->quotation->survey) {
                $buildingId = $contract->quotation->survey->building_id;
            }
            
            foreach ($contractRooms as $contractRoom) {
                if (! $this->shouldCreateRemoveJobForContractRoom($contractRoom)) {
                    $refillOnlyRoomsSkipped++;
                    Log::info("Skipping remove job for refill-only contract room during termination", [
                        'termination_number' => $contractTermination->termination_number,
                        'contract_id' => $contract->id,
                        'contract_room_id' => $contractRoom->id,
                        'room_id' => $contractRoom->room_id,
                    ]);
                    continue;
                }

                // Generate job number for remove
                $jobNumber = $this->documentNumberService->generate(
                    'remove',
                    null,
                    $buildingId,
                    $contract->id
                );

                // Link to the JobAdvice that originally installed this room so the
                // job is visible in the mobile technician app, which requires
                // jobAdvice to render customer/room data (see JobController::getTodayJobs).
                $jobAdviceRoom = \App\Models\JobAdviceRoom::where('contract_room_id', $contractRoom->id)
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->first();

                // Create remove job schedule
                $removeJob = JobSchedule::create([
                    'job_advice_id' => $jobAdviceRoom->job_advice_id ?? null,
                    'building_id' => $buildingId,
                    'room_id' => $contractRoom->room_id,
                    'room_name' => $contractRoom->room_name ?? $contractRoom->room->room_name ?? null,
                    'job_number' => $jobNumber,
                    'schedule_date' => now()->addDays(3)->toDateString(),
                    'expected_date' => now()->addDays(3)->toDateString(),
                    'type' => 'remove',
                    'status' => 'new_job',
                    'reference_number' => $contractTermination->termination_number,
                    'company_name' => $contract->customer->name ?? null,
                    'notes' => 'Auto-generated for contract termination: ' . $contractTermination->termination_number,
                    'contract_number' => $contract->contract_number,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                if ($jobAdviceRoom) {
                    $jobAdviceRoom->update(['remove_job_schedule_id' => $removeJob->id]);
                }

                $removeJobsCreated++;
            }

            DB::commit();

            Log::info("Contract termination approved: {$contractTermination->termination_number} by user {$user->name}. Terminated {$terminatedJobCount} jobs, created {$removeJobsCreated} remove jobs, skipped {$refillOnlyRoomsSkipped} refill-only rooms.");

            return response()->json([
                'status' => 'success',
                'message' => "Contract termination approved successfully. {$terminatedJobCount} job(s) terminated, {$removeJobsCreated} remove job(s) created." . ($refillOnlyRoomsSkipped > 0 ? " {$refillOnlyRoomsSkipped} refill-only room(s) skipped for remove job." : "")
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to approve contract termination: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve contract termination: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject contract termination
     */
    public function reject(Request $request, ContractTermination $contractTermination)
    {
        // Check permission using canApprove (checks permission checkbox first)
        $user = Auth::user();
        
        if (!$user->canApprove('contract_terminations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk reject Contract Termination. Pastikan role Anda memiliki permission "Approve" untuk Contract Terminations.'
            ], 403);
        }

        if ($contractTermination->status !== 'pending_approval') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only reject termination in pending approval status.'
            ], 400);
        }

        $request->validate([
            'approval_notes' => 'required|string',
        ]);

        try {
            $contractTermination->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes,
                'updated_by' => Auth::id(),
            ]);

            Log::info("Contract termination rejected: {$contractTermination->termination_number} by user {$user->name}");

            return response()->json([
                'status' => 'success',
                'message' => 'Contract termination rejected.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject contract termination: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject contract termination: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete contract termination (only if draft)
     */
    public function destroy(ContractTermination $contractTermination)
    {
        if ($contractTermination->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only delete termination in draft status.'
            ], 400);
        }

        try {
            $contractTermination->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Contract termination deleted successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete contract termination: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete contract termination: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unpost/Rollback Approved Contract Termination
     */
    public function unpost(Request $request, ContractTermination $contractTermination)
    {
        $user = Auth::user();
        
        // Cek permission unpost (sesuai config master roles)
        if (!$user->canApprove('contract_terminations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk unpost Contract Termination.'
            ], 403);
        }

        if ($contractTermination->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya transaksi yang sudah Approved/Posted yang bisa diunpost.'
            ], 400);
        }

        // VALIDASI MANUAL CLEANUP (Sesuai permintaan USER)
        $contract = $contractTermination->contract;
        if ($contract) {
            $dirtyJob = $this->findBlockingRemoveJob($contractTermination);

            if ($dirtyJob) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal Unpost. Job Remove (' . ($dirtyJob->job_number ?? 'tanpa nomor') . ') belum kembali ke New Job. Silakan unpost BA/job remove lalu unassign team sampai statusnya kembali New Job di menu Job Schedule.'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // 1. Kembalikan status kontrak ke active
            if ($contract) {
                $contract->update([
                    'status' => 'active',
                    'contract_status' => 'active',
                    'updated_by' => Auth::id()
                ]);
            }

            // 2. Kembalikan status terminasi ke draft
            // Sesuai permintaan USER: Sistem TIDAK melakukan pembersihan job otomatis lagi.
            $contractTermination->update([
                'status' => 'draft',
                'approved_by' => null,
                'approved_at' => null,
                'approval_notes' => $contractTermination->approval_notes . "\n[Unposted at " . now() . " by " . $user->name . "]",
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            Log::info("Contract termination unposted: {$contractTermination->termination_number} by user {$user->name}. Manual cleanup verified.");

            return response()->json([
                'status' => 'success',
                'message' => 'Contract Termination berhasil di-unpost. Status kembali menjadi Draft dan Kontrak kembali Active.'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to unpost contract termination: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan unpost: ' . $e->getMessage()
            ], 500);
        }
    }
}
