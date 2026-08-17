<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\Building;
use App\Models\Team;
use App\Models\Customer;
use App\Models\MaterialIssue;
use App\Models\JobAssignMaterialIssue;
use App\Models\MasterProduct;
use App\Models\Warehouse;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobAssignScheduleController extends Controller
{
    use AccessControlFilterTrait;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = JobAssignSchedule::with([
            'jobSchedule.jobAdvice.customer',
            'jobSchedule.jobAdvice.contract.quotation.quotationRooms.aromaProduct', // Load quotation with quotationRooms and aromaProduct for aroma/variant
            'jobSchedule.jobAdvice.rooms.contractRoom.room', // Load rooms for rental & room data
            'jobSchedule.jobAdvice.rooms.rentalProduct', // Load rental product
            'jobSchedule.building',
            'jobSchedule.room', // Load room
            'team',
            'assignedBy',
            'createdBy',
            'updatedBy'
        ]);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // Filter by created_by and also by jobSchedule.jobAdvice.created_by/requested_by
        $user = Auth::user();
        if (!$user->hasRoleStartingWith('Management')) {
            $accessibleUserIds = $this->getAccessibleUserIds($user);
            
            // Get teams where user is leader or member
            $userTeamIds = \DB::table('teams')
                ->where('team_head_id', $user->id)
                ->pluck('id')
                ->merge(
                    \DB::table('team_members')
                        ->where('user_id', $user->id)
                        ->pluck('team_id')
                )
                ->unique()
                ->toArray();
            
            $query->where(function($q) use ($accessibleUserIds, $userTeamIds) {
                $q->whereIn('created_by', $accessibleUserIds)
                  ->orWhereHas('jobSchedule.jobAdvice', function($subQ) use ($accessibleUserIds) {
                      $subQ->whereIn('created_by', $accessibleUserIds)
                           ->orWhereIn('request_by', $accessibleUserIds);
                  })
                  // Include job assigns for user's teams
                  ->orWhereIn('team_id', $userTeamIds);
            });
        }

        // Filter by job number
        if ($request->filled('job_number')) {
            $query->whereHas('jobSchedule.jobAdvice', function($q) use ($request) {
                $q->where('job_advice_number', 'like', "%{$request->job_number}%");
            });
        }

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->whereHas('jobSchedule.jobAdvice.customer', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->customer_name}%");
            });
        }

        // Filter by building
        if ($request->filled('building_name')) {
            $query->whereHas('jobSchedule.building', function($q) use ($request) {
                $q->where('nama_gedung', 'like', "%{$request->building_name}%");
            });
        }

        // Filter by team
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Filter by team code
        if ($request->filled('team_code')) {
            $query->whereHas('team', function($q) use ($request) {
                $q->where('team_code', $request->team_code);
            });
        }

        // Filter by date from (schedule_date)
        if ($request->filled('date_from')) {
            $query->whereHas('jobSchedule', function($q) use ($request) {
                $q->where('schedule_date', '>=', $request->date_from);
            });
        }

        // Filter by date to (schedule_date)
        if ($request->filled('date_to')) {
            $query->whereHas('jobSchedule', function($q) use ($request) {
                $q->where('schedule_date', '<=', $request->date_to);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned date range
        if ($request->filled('assigned_date_from')) {
            $query->where('assigned_date', '>=', $request->assigned_date_from);
        }

        if ($request->filled('assigned_date_to')) {
            $query->where('assigned_date', '<=', $request->assigned_date_to);
        }

        $schedules = $query->orderBy('created_at', 'desc')->paginateStd(25);
        $teams = $this->getJobAssignScheduleTeams();
        $jobSchedules = $this->getSchedulableJobSchedules();
        $statistics = $this->getJobAssignScheduleStatistics();

        return view('operational.job-assign-schedules.index', compact('schedules', 'teams', 'jobSchedules', 'statistics'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $jobSchedules = $this->getSchedulableJobSchedules();
        $teams = $this->getJobAssignScheduleTeams(false);
        $buildings = $this->getJobAssignScheduleBuildings();
        $rooms = collect();

        // Check if request expects JSON response (for modal system)
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'jobSchedules' => $jobSchedules,
                    'teams' => $teams,
                    'buildings' => $buildings
                ]
            ]);
        }

        return view('operational.job-assign-schedules.create', compact('jobSchedules', 'teams', 'buildings', 'rooms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'job_schedule_id' => 'required|exists:job_schedules,id',
            'team_id' => 'required|exists:teams,id',
            'assigned_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Check if job schedule is already assigned
            $existingAssignment = JobAssignSchedule::where('job_schedule_id', $request->job_schedule_id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existingAssignment) {
                if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This job schedule is already assigned to a team.'
                    ], 422);
                }
                return back()->with('error', 'This job schedule is already assigned to a team.');
            }

            // MOM14 Point 7: Validation before Assign Team
            $jobSchedule = JobSchedule::with('jobScheduleRooms')->find($request->job_schedule_id);
            $roomIds = $jobSchedule->jobScheduleRooms->pluck('room_id')->filter()->unique();

            if ($roomIds->isNotEmpty()) {
                // Check for other configuration jobs in the same rooms
                $unfinishedConfigJob = JobSchedule::whereIn('room_id', $roomIds)
                    ->where('id', '!=', $jobSchedule->id)
                    ->where(function($q) {
                        $q->where('type', 'like', 'remove%')
                          ->orWhere('type', 'like', 'change%');
                    })
                    ->whereNotIn('status', ['completed', 'done_job', 'cancelled', 'terminated', 'suspend', 'dpf'])
                    ->first();

                if ($unfinishedConfigJob) {
                    $errorMsg = "maaf penugasan tim tidak dapat dilakukan karena di ruangan {$unfinishedConfigJob->room_name} masih ada pekerjaan konfigurasi yang belum selesai (Nomor: {$unfinishedConfigJob->job_number})";
                    
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $errorMsg
                        ], 422);
                    }
                    
                    return back()->with('error', $errorMsg);
                }

                // MOM14 Point 7: Also check for pending Contract Termination or already Terminated Contract
                $contractNumber = $jobSchedule->contract_number;
                if ($contractNumber) {
                    $contract = \App\Models\Contract::where('contract_number', $contractNumber)->first();
                    
                    if ($contract) {
                        // Check if contract is already terminated
                        if ($contract->status === 'terminated') {
                            $errorMsg = "maaf penugasan tim tidak dapat dilakukan karena kontrak {$contractNumber} sudah terterminasi.";
                            
                            if ($request->expectsJson() || $request->ajax()) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => $errorMsg
                                ], 422);
                            }
                            
                            return back()->with('error', $errorMsg);
                        }

                        // Check for pending termination request
                        $pendingTermination = \App\Models\ContractTermination::where('contract_id', $contract->id)
                            ->whereIn('status', ['draft', 'waiting_for_approval', 'approved'])
                            ->first();

                        if ($pendingTermination) {
                            $errorMsg = "maaf penugasan tim tidak dapat dilakukan karena kontrak {$contractNumber} sedang dalam proses terminasi.";
                            
                            if ($request->expectsJson() || $request->ajax()) {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => $errorMsg
                                ], 422);
                            }
                            
                            return back()->with('error', $errorMsg);
                        }
                    }
                }
            }

            $schedule = JobAssignSchedule::create([
                'job_schedule_id' => $request->job_schedule_id,
                'team_id' => $request->team_id,
                'assigned_by' => Auth::id(),
                'assigned_date' => $request->assigned_date,
                'status' => 'assigned',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // STUDY CASE B2: Auto-create JobScheduleRoomAssignment for all rooms (global assignment)
            $jobSchedule = JobSchedule::find($request->job_schedule_id);
            if ($jobSchedule) {
                app(\App\Services\Warehouse\InventoryIssuingService::class)
                    ->syncRoomAssignmentsForJobSchedule(
                        $jobSchedule,
                        (int) $request->team_id,
                        (int) $schedule->id,
                        $request->assigned_date
                    );

                // MOM9: Auto-update job schedule status to "assign_team" when team is assigned
                // Status flow: scheduled/new_job → assign_team → assign_material → barang_dipersiapkan → etc.
                // Only update status if it's still in initial states
                if (in_array($jobSchedule->status, ['scheduled', 'new_job'])) {
                    $updateData = [
                        'status' => 'assign_team',
                        'updated_by' => Auth::id()
                    ];
                    
                    // MOM13: Auto-fill assign_date ketika status berubah ke assign_team
                    if (!$jobSchedule->assign_date) {
                        $updateData['assign_date'] = now()->toDateString();
                    }
                    
                    $jobSchedule->update($updateData);
                } else {
                }
            }

            // AUTO-CREATE MATERIAL ISSUES FROM RENTAL COMPONENTS
            // Skip for remove job - no material needed, just remove units from Unit On Wall
            $jobSchedule = JobSchedule::find($request->job_schedule_id);
            $skipMaterialIssue = false;
            
            // Remove job: Skip material issue creation (units are from Unit On Wall, no material needed)
            // Include remove, remove_free, remove free to handle all remove job types consistently
            if ($jobSchedule && in_array(strtolower($jobSchedule->type), ['remove', 'remove_free', 'remove free'])) {
                $skipMaterialIssue = true;
            }
            
            // MOM13: Service pertama tetap butuh material issue untuk tracking
            // Status tidak di-skip ke "barang_diambil" - harus melalui flow normal:
            // assign_team -> assign_material -> barang_dipersiapkan -> barang_diambil (setelah ambil di inventory issuing)
            if ($jobSchedule && $jobSchedule->type === 'service' && $jobSchedule->period == 1) {
                // Don't skip material issue - first service still needs refill/aroma/cleaner etc.
                // Status stays at 'assign_team' until material is prepared and taken
            }
            
            if (!$skipMaterialIssue) {
                try {
                    $this->autoCreateMaterialIssuesFromRental($schedule);
                } catch (\Exception $e) {
                    \Log::error("❌ Failed to auto-create material issues for Job Assign Schedule {$schedule->id}: " . $e->getMessage());
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                    // Don't rollback transaction, just log the error
                    // Material issues can be created manually later
                }
            }

            DB::commit();

            // MOM: Bidirectional Sync - Update related Inventory Issuing records
            try {
                $service = new \App\Services\Warehouse\InventoryIssuingService();
                $service->syncTeamFromJobSchedule($schedule->job_schedule_id, $request->team_id);
            } catch (\Exception $e) {
                \Log::error("Bidirectional Sync Error (Store): " . $e->getMessage());
            }

            // Check if request expects JSON response (API call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job assignment schedule created successfully.',
                    'data' => $schedule->load(['jobSchedule.jobAdvice.customer', 'team', 'assignedBy'])
                ]);
            }

            return redirect()->route('operational.job-assign-schedules.index')
                ->with('success', 'Job assignment schedule created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Check if request expects JSON response (API call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create job assignment schedule: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create job assignment schedule: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(JobAssignSchedule $jobAssignSchedule)
    {
        try {
            $jobAssignSchedule->load([
                'jobSchedule.jobAdvice.customer',
                'jobSchedule.building',
                'jobSchedule.room',
                'team',
                'assignedBy',
                'createdBy',
                'updatedBy',
                'jobAssignMaterialIssues.materialIssue.product',
                'jobAssignMaterialIssues.materialIssue.team'
            ]);
        } catch (\Exception $e) {
            \Log::error('JobAssignSchedule show error: ' . $e->getMessage(), [
                'job_assign_schedule_id' => $jobAssignSchedule->id,
                'exception' => $e
            ]);
            
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to load job assignment schedule: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to load job assignment schedule: ' . $e->getMessage());
        }

        $statistics = [
            'material_issues_count' => $jobAssignSchedule->jobAssignMaterialIssues->count(),
            'related_assignments' => JobAssignSchedule::where('team_id', $jobAssignSchedule->team_id)
                ->where('id', '!=', $jobAssignSchedule->id)
                ->count(),
            'team_total_assignments' => JobAssignSchedule::where('team_id', $jobAssignSchedule->team_id)->count(),
        ];

        // Check if request expects JSON response (for modal system)
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $jobAssignSchedule,
                'statistics' => $statistics
            ]);
        }

        // For regular page view, pass both variable names for compatibility
        return view('operational.job-assign-schedules.show', compact('jobAssignSchedule', 'statistics'))
            ->with('jobAssignment', $jobAssignSchedule);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JobAssignSchedule $jobAssignSchedule)
    {
        $jobAssignSchedule->load(['jobSchedule.jobAdvice.customer', 'jobSchedule.building', 'team']);
        
        $jobSchedules = $this->getSchedulableJobSchedules($jobAssignSchedule->job_schedule_id);
        $teams = $this->getJobAssignScheduleTeams(false);
        $buildings = $this->getJobAssignScheduleBuildings();
        $rooms = collect();

        return view('operational.job-assign-schedules.edit', compact('jobAssignSchedule', 'jobSchedules', 'teams', 'buildings', 'rooms'))
            ->with('jobAssignment', $jobAssignSchedule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobAssignSchedule $jobAssignSchedule)
    {
        $request->validate([
            'job_schedule_id' => 'required|exists:job_schedules,id',
            'team_id' => 'required|exists:teams,id',
            'assigned_date' => 'required|date',
            'status' => 'required|in:assigned,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Check if job schedule is already assigned to another team
            if ($request->job_schedule_id != $jobAssignSchedule->job_schedule_id) {
                $existingAssignment = JobAssignSchedule::where('job_schedule_id', $request->job_schedule_id)
                    ->where('status', '!=', 'cancelled')
                    ->where('id', '!=', $jobAssignSchedule->id)
                    ->first();

                if ($existingAssignment) {
                    if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'This job schedule is already assigned to another team.'
                        ], 422);
                    }
                    return back()->with('error', 'This job schedule is already assigned to another team.');
                }
            }

            $oldTeamId = $jobAssignSchedule->team_id;
            
            $jobAssignSchedule->update([
                'job_schedule_id' => $request->job_schedule_id,
                'team_id' => $request->team_id,
                'assigned_date' => $request->assigned_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            // STUDY CASE B2: Update global room assignments if team_id changed
            if ($oldTeamId != $request->team_id) {
                $jobSchedule = $jobAssignSchedule->jobSchedule;
                if ($jobSchedule) {
                    app(\App\Services\Warehouse\InventoryIssuingService::class)
                        ->syncRoomAssignmentsForJobSchedule(
                            $jobSchedule,
                            (int) $request->team_id,
                            (int) $jobAssignSchedule->id,
                            $request->assigned_date
                        );
                }
            }

            DB::commit();

            // MOM: Bidirectional Sync - Update related Inventory Issuing records
            try {
                $service = new \App\Services\Warehouse\InventoryIssuingService();
                $service->syncTeamFromJobSchedule($jobAssignSchedule->job_schedule_id, $request->team_id);
            } catch (\Exception $e) {
                \Log::error("Bidirectional Sync Error (Update): " . $e->getMessage());
            }

            // Check if request expects JSON response (API call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job assignment schedule updated successfully.',
                    'data' => $jobAssignSchedule->load(['jobSchedule.jobAdvice.customer', 'team', 'assignedBy'])
                ]);
            }

            return redirect()->route('operational.job-assign-schedules.show', $jobAssignSchedule)
                ->with('success', 'Job assignment schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Check if request expects JSON response (API call)
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update job assignment schedule: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update job assignment schedule: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobAssignSchedule $jobAssignSchedule)
    {
        if ($jobAssignSchedule->status === 'completed') {
            return back()->with('error', 'Cannot delete completed job assignment.');
        }

        try {
            DB::beginTransaction();

            // STUDY CASE B2: Delete global room assignments (non-custom ones)
            $jobSchedule = $jobAssignSchedule->jobSchedule;
            if ($jobSchedule) {
                $deletedCount = \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)
                    ->where('job_assign_schedule_id', $jobAssignSchedule->id)
                    ->where('is_custom', false)
                    ->delete();
            }

            // Update job schedule status back to scheduled if it was in assignment-related status
            // Status yang terkait dengan assignment: assign_team, assign_material, in_progress (legacy)
            if ($jobAssignSchedule->jobSchedule) {
                $currentStatus = $jobAssignSchedule->jobSchedule->status;
                if (in_array($currentStatus, ['assign_team', 'assign_material', 'in_progress'])) {
                    $jobAssignSchedule->jobSchedule->update(['status' => 'scheduled']);
                }
            }

            $jobAssignSchedule->delete();

            DB::commit();

            // MOM: Bidirectional Sync - Clear team in Inventory Issuing after assignment deleted
            try {
                if (isset($jobSchedule) && $jobSchedule) {
                    $service = new \App\Services\Warehouse\InventoryIssuingService();
                    $service->syncTeamFromJobSchedule($jobSchedule->id, null);
                }
            } catch (\Exception $e) {
                \Log::error("Bidirectional Sync Error (Destroy): " . $e->getMessage());
            }

            return redirect()->route('operational.job-assign-schedules.index')
                ->with('success', 'Job assignment schedule deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to delete job assignment schedule: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete job assignment schedules.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:job_assign_schedules,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->ids as $id) {
                $jobAssignSchedule = JobAssignSchedule::find($id);
                
                if (!$jobAssignSchedule) {
                    $errors[] = "Job assignment with ID {$id} not found.";
                    continue;
                }

                if ($jobAssignSchedule->status === 'completed') {
                    $errors[] = "Cannot delete completed job assignment ID {$id}.";
                    continue;
                }

                // Update job schedule status back to scheduled if it was in assignment-related status
                // Status yang terkait dengan assignment: assign_team, assign_material, in_progress (legacy)
                if ($jobAssignSchedule->jobSchedule) {
                    $currentStatus = $jobAssignSchedule->jobSchedule->status;
                    if (in_array($currentStatus, ['assign_team', 'assign_material', 'in_progress'])) {
                        $jobAssignSchedule->jobSchedule->update(['status' => 'scheduled']);
                    }
                }

                $jobAssignSchedule->delete();
                $deletedCount++;
            }

            DB::commit();

            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$deletedCount} job assignment(s).",
                    'count' => $deletedCount,
                    'errors' => $errors
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No job assignments were deleted.',
                    'errors' => $errors
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start the job assignment.
     */
    public function start(JobAssignSchedule $jobAssignSchedule)
    {
        if (!$jobAssignSchedule->canStart()) {
            return back()->with('error', 'Job assignment cannot be started in current status.');
        }

        try {
            DB::beginTransaction();

            $jobAssignSchedule->start();
            $jobAssignSchedule->update(['updated_by' => Auth::id()]);

            DB::commit();

            return back()->with('success', 'Job assignment started successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to start job assignment: ' . $e->getMessage());
        }
    }

    /**
     * Complete the job assignment.
     */
    public function complete(JobAssignSchedule $jobAssignSchedule)
    {
        if (!$jobAssignSchedule->canComplete()) {
            return back()->with('error', 'Job assignment cannot be completed in current status.');
        }

        try {
            DB::beginTransaction();

            $jobAssignSchedule->complete();
            $jobAssignSchedule->update(['updated_by' => Auth::id()]);

            // Update job schedule status to completed
            // STUDY CASE B1: Check if all rooms are completed before marking job schedule as completed
            if ($jobAssignSchedule->jobSchedule) {
                $jobSchedule = $jobAssignSchedule->jobSchedule;
                
                // STUDY CASE B1: Check if all rooms are completed
                if ($jobSchedule->areAllRoomsCompleted()) {
                    $updateData = [
                        'status' => 'completed',
                        'updated_by' => Auth::id()
                    ];
                    
                    // MOM13: Auto-fill ba_date dan ba_number ketika status berubah ke completed
                    if (!$jobSchedule->ba_date) {
                        $updateData['ba_date'] = now()->toDateString();
                    }
                    if (!$jobSchedule->ba_number) {
                        $updateData['ba_number'] = $this->generateBANumber($jobSchedule);
                    }
                    
                    $jobSchedule->update($updateData);
                } else {
                    // Not all rooms completed, keep status as in_progress
                    if ($jobSchedule->status !== 'in_progress') {
                        $jobSchedule->update([
                            'status' => 'in_progress',
                            'updated_by' => Auth::id()
                        ]);
                    }
                }
            }

            DB::commit();

            return back()->with('success', 'Job assignment completed successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to complete job assignment: ' . $e->getMessage());
        }
    }

    /**
     * Cancel the job assignment.
     */
    public function cancel(JobAssignSchedule $jobAssignSchedule)
    {
        if (!$jobAssignSchedule->canCancel()) {
            return back()->with('error', 'Job assignment cannot be cancelled in current status.');
        }

        try {
            DB::beginTransaction();

            $jobAssignSchedule->cancel();
            $jobAssignSchedule->update(['updated_by' => Auth::id()]);

            // Update job schedule status back to scheduled
            if ($jobAssignSchedule->jobSchedule) {
                $jobAssignSchedule->jobSchedule->update(['status' => 'scheduled']);
            }

            DB::commit();

            // MOM: Bidirectional Sync - Clear team in Inventory Issuing after assignment cancelled
            try {
                if ($jobAssignSchedule->job_schedule_id) {
                    $service = new \App\Services\Warehouse\InventoryIssuingService();
                    $service->syncTeamFromJobSchedule($jobAssignSchedule->job_schedule_id, null);
                }
            } catch (\Exception $e) {
                \Log::error("Bidirectional Sync Error (Cancel): " . $e->getMessage());
            }

            return back()->with('success', 'Job assignment cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to cancel job assignment: ' . $e->getMessage());
        }
    }

    /**
     * Get schedules by date range for API.
     */
    public function getSchedulesByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $schedules = JobAssignSchedule::whereBetween('assigned_date', [$request->start_date, $request->end_date])
            ->with([
                'jobSchedule.jobAdvice.customer',
                'jobSchedule.building',
                'team',
                'assignedBy'
            ])
            ->orderBy('assigned_date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $schedules,
        ]);
    }

    /**
     * Get schedules by customer for API.
     */
    public function getSchedulesByCustomer(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string',
        ]);

        $schedules = JobAssignSchedule::whereHas('jobSchedule.jobAdvice.customer', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->customer_name}%");
            })
            ->with([
                'jobSchedule.jobAdvice.customer',
                'jobSchedule.building',
                'team',
                'assignedBy'
            ])
            ->orderBy('assigned_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $schedules,
        ]);
    }

    /**
     * Get schedule statistics for API.
     */
    public function getScheduleStatistics()
    {
        $statistics = [
            'total' => JobAssignSchedule::count(),
            'assigned' => JobAssignSchedule::where('status', 'assigned')->count(),
            'in_progress' => JobAssignSchedule::where('status', 'in_progress')->count(),
            'completed' => JobAssignSchedule::where('status', 'completed')->count(),
            'cancelled' => JobAssignSchedule::where('status', 'cancelled')->count(),
            'today' => JobAssignSchedule::whereDate('assigned_date', today())->count(),
            'this_week' => JobAssignSchedule::whereBetween('assigned_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => JobAssignSchedule::whereMonth('assigned_date', now()->month)->count(),
            'assignments_by_status' => JobAssignSchedule::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            'assignments_by_team' => JobAssignSchedule::select('teams.team_name', DB::raw('count(*) as count'))
                ->join('teams', 'job_assign_schedules.team_id', '=', 'teams.id')
                ->groupBy('teams.team_name')
                ->get(),
            'recent_assignments' => JobAssignSchedule::with([
                'jobSchedule.jobAdvice.customer',
                'jobSchedule.building',
                'team',
                'assignedBy'
            ])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    /**
     * Search schedules for API.
     */
    public function searchSchedules(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $schedules = JobAssignSchedule::where(function ($q) use ($request) {
                $q->whereHas('jobSchedule.jobAdvice', function($subQ) use ($request) {
                    $subQ->where('job_advice_number', 'like', "%{$request->search}%");
                })
                ->orWhereHas('jobSchedule.jobAdvice.customer', function($subQ) use ($request) {
                    $subQ->where('name', 'like', "%{$request->search}%");
                })
                ->orWhereHas('jobSchedule.building', function($subQ) use ($request) {
                    $subQ->where('nama_gedung', 'like', "%{$request->search}%");
                })
                ->orWhereHas('team', function($subQ) use ($request) {
                    $subQ->where('team_name', 'like', "%{$request->search}%");
                });
            })
            ->with([
                'jobSchedule.jobAdvice.customer',
                'jobSchedule.building',
                'team',
                'assignedBy'
            ])
            ->orderBy('assigned_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $schedules,
        ]);
    }

    /**
     * Auto-create Material Issues from Rental Components
     * Called after Job Assign Schedule is created
     */
    private function autoCreateMaterialIssuesFromRental(JobAssignSchedule $jobAssignSchedule)
    {
        try {
            
            // Load relationships
            // MOM9: Load quotation relationship if job advice is from quotation
            $jobAssignSchedule->load([
                'jobSchedule.jobAdvice.rooms.rentalProduct.rentalComponents.preferredProducts.productCategory',
                'jobSchedule.jobAdvice.rooms.rentalProduct.rentalComponents.preferredProducts.productType',
                'jobSchedule.jobAdvice.rooms.rentalProduct.rentalComponents.allowedProducts.productCategory',
                'jobSchedule.jobAdvice.rooms.rentalProduct.rentalComponents.allowedProducts.productType',
                'jobSchedule.jobAdvice.contract.quotation.quotationRooms.aromaProduct.productCategory',
                'jobSchedule.jobAdvice.contract.quotation.quotationRooms.aromaProduct.productType',
                'jobSchedule.jobAdvice.quotation.quotationRooms.aromaProduct.productCategory',
                'jobSchedule.jobAdvice.quotation.quotationRooms.aromaProduct.productType',
                'jobSchedule.jobAdvice.rooms.contractRoom.room',
                'jobSchedule.jobAdvice.rooms.quotationRoom.room',
                'jobSchedule.jobScheduleRooms.jobAdviceRoom.rentalProduct',
                'jobSchedule.jobScheduleRooms.rentals.jobAdviceRoom.rentalProduct',
                'jobSchedule.building',
                'team'
            ]);

            $jobSchedule = $jobAssignSchedule->jobSchedule;
            if (!$jobSchedule) {
                \Log::warning("❌ Cannot auto-create material issues: Job Schedule not found for Job Assign Schedule {$jobAssignSchedule->id}");
                return;
            }
            
            if (!$jobSchedule->jobAdvice) {
                \Log::warning("❌ Cannot auto-create material issues: Job Advice not found for Job Schedule {$jobSchedule->id}");
                return;
            }

            $jobAdvice = $jobSchedule->jobAdvice;
            
            // Determine material filter based on job type
            // Install job (IR): only need units (is_unit = true)
            // Install job (IR): only need unit materials (is_unit = true) - unit only
            // Service job (CSR): only need non-unit materials (is_unit = false) - refill, cleaner, etc.
            // Install Free (IF): need ALL materials (both unit and non-unit) - no filtering
            // Other job types: take all materials (no filter)
            
            // IMPORTANT: Job Schedule type is stored as 'install' for both IR and IF
            // We need to check Job Advice type to differentiate between IR (install) and IF (install_free)
            $jobType = strtolower($jobSchedule->type ?? '');
            
            // FIX: Skip material issue creation for Remove jobs (RV/RF)
            // Remove jobs take items FROM customer, so no warehouse issue needed
            if (in_array($jobType, ['remove', 'remove_free', 'remove free'])) {
                return;
            }

            $jobAdviceType = strtolower($jobAdvice->type ?? '');
            $isInstallFree = ($jobAdviceType === 'install_free' || $jobAdviceType === 'install free');
            
            $needUnits = ($jobType === 'install' && !$isInstallFree); // Only regular install (IR) needs units only, NOT install_free
            $needNonUnits = ($jobType === 'service' || $jobType === 'servis'); // Service jobs need non-units only
            
            // FIX: For "Change Rental", we need BOTH the new unit AND its non-unit components (aroma, cleaner, etc.)
            // So we treat it like Install Free (no filter)
            // MOM13: Use robust check for change rental (str_contains) and trim whitespace
            $normalizedJaType = strtolower(trim($jobAdviceType));
            $isChangeRental = str_contains($normalizedJaType, 'change');
            
            // For install_free OR change_rental: filterByUnitType = false, so all materials (units + non-units) will be included
            // For install (IR): filterByUnitType = true, needUnits = true, so only units will be included
            // For service: filterByUnitType = true, needNonUnits = true, so only non-units will be included
            $filterByUnitType = ($needUnits || $needNonUnits) && !$isChangeRental; // Only filter for install IR (units only) or service (non-units only), but NOT for change rental
            
            $building = $jobSchedule->building;
            $team = $jobAssignSchedule->team;


            // Resolve warehouse from the job's branch scope only; never use the first
            // active warehouse because that can mix stock/SN across branches.
            $warehouse = null;
            if ($building && $building->branch_id) {
                $warehouse = Warehouse::where('branch_id', $building->branch_id)
                    ->where('is_active', true)
                    ->first();
            }
            
            if (!$warehouse && $building && $building->city_id) {
                $branch = \App\Models\Branch::where('city_id', $building->city_id)
                    ->where('is_active', true)
                    ->first();

                if ($branch) {
                    $warehouse = Warehouse::where('branch_id', $branch->id)
                        ->where('is_active', true)
                        ->first();
                }
            }

            if (!$warehouse && $building) {
                $provinceId = $building->province_id ?: ($building->city?->province_id ?? null);

                if ($provinceId) {
                    $branch = \App\Models\Branch::where('province_id', $provinceId)
                        ->where('is_active', true)
                        ->first();

                    if ($branch) {
                        $warehouse = Warehouse::where('branch_id', $branch->id)
                            ->where('is_active', true)
                            ->first();
                    }
                }
            }

            if (!$warehouse && $team?->branch_office) {
                $warehouse = Warehouse::where('branch_id', $team->branch_office)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$warehouse) {
                \Log::warning("❌ Cannot auto-create material issues: No warehouse found for Job Assign Schedule {$jobAssignSchedule->id}");
                return;
            }

            // Use only rooms/rentals linked to this JobSchedule. A Job Advice can
            // contain several room schedules; taking all JA rooms here duplicates
            // material issue lines for every sibling schedule.
            $rooms = $jobSchedule->jobScheduleRooms
                ->flatMap(function ($jobScheduleRoom) {
                    $pivotRooms = $jobScheduleRoom->rentals
                        ->pluck('jobAdviceRoom')
                        ->filter();

                    if ($pivotRooms->isNotEmpty()) {
                        return $pivotRooms;
                    }

                    return $jobScheduleRoom->jobAdviceRoom ? collect([$jobScheduleRoom->jobAdviceRoom]) : collect();
                })
                ->unique('id')
                ->values();

            if ($rooms->isEmpty()) {
                $rooms = $jobAdvice->rooms;
            }
            
            // MOM9: For Install Free from Quotation, rooms might be empty, but we still create material issue
            // Material issue will be created as draft and can be filled later
            if ($rooms->isEmpty() && !$jobAdvice->quotation_id) {
                \Log::warning("❌ No rooms found for Job Advice {$jobAdvice->job_advice_number} (not from quotation). Skipping auto-create material issues.");
                return;
            }
            
            // MOM9: For quotation-based job advice without rooms, still create material issue
            if ($rooms->isEmpty() && $jobAdvice->quotation_id) {
            }
            
            // Log each room details
            foreach ($rooms as $index => $jaRoom) {
            }

            // Check if Material Issue already exists for this Job Assign Schedule
            $existingMaterialIssues = JobAssignMaterialIssue::where('job_assign_schedule_id', $jobAssignSchedule->id)
                ->count();
            
            if ($existingMaterialIssues > 0) {
                return;
            }

            // Prefer the schedule rooms created by Job Advice generation. Creating
            // one row for each rental here would split multi-rental rooms again.
            $jobScheduleRooms = $jobSchedule->jobScheduleRooms->all();
            if (empty($jobScheduleRooms)) {
                foreach ($rooms as $jaRoom) {
                
                $jobScheduleRoom = \App\Models\JobScheduleRoom::firstOrCreate(
                    [
                        'job_schedule_id' => $jobSchedule->id,
                        'job_advice_room_id' => $jaRoom->id,
                    ],
                    [
                        'room_name' => $jaRoom->room_name,
                        'room_id' => $jaRoom->contractRoom?->room_id ?? $jaRoom->quotationRoom?->room_id ?? null,
                        'status' => 'pending',
                        'material_return_status' => 'not_required',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );
                
                if ($jobScheduleRoom) {
                    $jobScheduleRooms[] = $jobScheduleRoom;
                } else {
                    \Log::warning("⚠️ Failed to create/get JobScheduleRoom for JA Room {$jaRoom->id}. Skipping.");
                }
                }
            }

            if (empty($jobScheduleRooms)) {
                \Log::warning("⚠️ No valid JobScheduleRooms created. Cannot create Material Issue.");
                return;
            }

            // Generate unique issue number for Material Issue (1 per Job Schedule)
            $issueNumber = null;
            $maxRetries = 10;
            $retry = 0;
            
            do {
                try {
                    $issueNumber = $this->generateUniqueIssueNumber($warehouse->id);
                    
                    // Double-check if issue number already exists
                    $exists = MaterialIssue::withoutTrashed()
                        ->where('issue_number', $issueNumber)
                        ->exists();
                    
                    if (!$exists) {
                        break; // Found unique number
                    }
                    
                    $retry++;
                    \Log::warning("Issue number {$issueNumber} already exists. Retrying... (Attempt {$retry}/{$maxRetries})");
                    usleep(200000); // 0.2 second delay
                    
                } catch (\Exception $e) {
                    \Log::error("Failed to generate unique issue number: " . $e->getMessage());
                    $retry++;
                    if ($retry >= $maxRetries) {
                        \Log::error("Failed to generate unique issue number after {$maxRetries} attempts.");
                        return;
                    }
                    usleep(200000); // 0.2 second delay
                }
            } while ($retry < $maxRetries);
            
            if (!$issueNumber) {
                \Log::error("Failed to generate unique issue number. Cannot create Material Issue.");
                return;
            }

            // Get quotation for aroma products
            $quotation = null;
            if ($jobAdvice->quotation_id) {
                $quotation = $jobAdvice->quotation;
            } elseif ($jobAdvice->contract && $jobAdvice->contract->quotation) {
                $quotation = $jobAdvice->contract->quotation;
            }

            // Collect all material issue items from all rooms
            $materialIssueItems = [];
            $firstProduct = null;
            $totalQuantity = 0;

            foreach ($rooms as $jaRoom) {
                
                $rental = $jaRoom->rentalProduct;
                if (!$rental) {
                    \Log::warning("⚠️ Rental product not found for JA Room {$jaRoom->id}. Skipping.");
                    continue;
                }

                // Get aroma product for this room from quotation
                $aromaProduct = null;
                if ($quotation) {
                    $quotationRoom = null;
                    if ($jaRoom->quotation_room_id) {
                        $quotationRoom = $quotation->quotationRooms->where('id', $jaRoom->quotation_room_id)->first();
                    }
                    if ($quotationRoom && $quotationRoom->aromaProduct) {
                        $aromaProduct = $quotationRoom->aromaProduct;
                        // Load productCategory to check is_unit flag
                        if (!$aromaProduct->relationLoaded('productCategory')) {
                            $aromaProduct->load('productCategory');
                        }
                        // Still load productType for backward compatibility
                        if (!$aromaProduct->relationLoaded('productType')) {
                            $aromaProduct->load('productType');
                        }
                    }
                }
                
                // IMPORTANT: Use rental_details (sinkron dengan UI Master Rental) bukan rental_components
                // rental_details adalah yang sudah di-set dengan Material List yang dipilih user
                // rental_components adalah template/komponen dasar, bukan produk yang sudah dipilih
                
                // Load rentalDetails with productType using direct query to ensure is_unit flag is loaded correctly
                $rentalDetails = \App\Models\RentalDetail::where('master_rental_id', $rental->id)
                    ->whereNull('deleted_at')
                    ->with([
                        'productCategory' => function($q) {
                            $q->select('id', 'name', 'is_unit'); // Explicitly select is_unit
                        },
                        'productType' => function($q) {
                            $q->select('id', 'name', 'is_unit'); // Explicitly select is_unit
                        },
                        'masterProduct.productCategory' => function($q) {
                            $q->select('id', 'name', 'is_unit');
                        },
                        'masterProduct.productType' => function($q) {
                            $q->select('id', 'name', 'is_unit');
                        },
                        'allowedProducts.productCategory' => function($q) {
                            $q->select('id', 'name', 'is_unit');
                        },
                        'allowedProducts.productType' => function($q) {
                            $q->select('id', 'name', 'is_unit');
                        }
                    ])
                    ->orderBy('product_type_id')
                    ->orderBy('id')
                    ->get();
                
                
                // Log all rental details with their is_unit status for debugging
                foreach ($rentalDetails as $idx => $detail) {
                    $productCategoryName = $detail->productCategory->name ?? $detail->productType->name ?? 'N/A';
                    $isUnitFromRelation = ($detail->productCategory && ($detail->productCategory->is_unit == 1 || $detail->productCategory->is_unit === true)) || 
                                          ($detail->productType && ($detail->productType->is_unit == 1 || $detail->productType->is_unit === true));
                }
                
                // XFreqService interval: for recurring service jobs, only include a
                // material on the services where it is due (every Nth service). No-op
                // unless the kill-switch is on and this rental has XFreqService set.
                $serviceIntervalActive = $jobSchedule->serviceIntervalFilteringActive($rentalDetails);
                $serviceSequenceNumber = $serviceIntervalActive ? $jobSchedule->getServiceSequenceNumber() : null;

                foreach ($rentalDetails as $detail) {
                    if ($serviceIntervalActive && !$detail->isDueAtServiceSequence($serviceSequenceNumber)) {
                        continue;
                    }

                    $product = null;
                    $productTypeName = $detail->productType->name ?? null;
                    $productTypeId = $detail->product_type_id;
                    
                    // A multiplier of 0 means "due once, at install only" (see
                    // RentalDetail::isDueAtServiceSequence) regardless of whether the
                    // material is a physical unit or a consumable/spare-part. Without this
                    // exemption, a non-unit item with multiplier 0 (e.g. a spare-part filter
                    // meant to ship once with the install) passes the frequency check above
                    // but is then silently dropped by the is_unit filter below on every job,
                    // since it's only ever "due" on the one job type (install) that filter excludes it from.
                    $isPermanentNonUnitDueAtInstall = $jobType === 'install'
                        && (int) ($detail->service_frequency_multiplier ?? -1) === 0;

                    // FILTER: Check is_unit flag based on job type (only for install and service jobs)
                    // Install job: only include units (is_unit = true)
                    // Service job: only include non-units (is_unit = false) - refill, cleaner, etc.
                    // Other job types: include all materials (no filter)
                    if ($filterByUnitType && !$isPermanentNonUnitDueAtInstall) {
                        // Check is_unit from productCategory relationship first
                        $detailProductCategory = $detail->productCategory;
                        $detailProductType = $detail->productType;
                        $detailIsUnit = false; // Default to false
                        
                        // Priority 1: Check ProductCategory relationship
                        if ($detailProductCategory && isset($detailProductCategory->is_unit)) {
                            $detailIsUnit = ($detailProductCategory->is_unit == 1 || $detailProductCategory->is_unit === true);
                            $productTypeName = $detailProductCategory->name;
                        } 
                        // Priority 2: Check ProductType relationship (Legacy)
                        elseif ($detailProductType && isset($detailProductType->is_unit)) {
                            $detailIsUnit = ($detailProductType->is_unit == 1 || $detailProductType->is_unit === true);
                            $productTypeName = $detailProductType->name;
                        }
                        // Priority 3: Check database directly (Categories first)
                        else {
                            \Log::warning("  ⚠️ Detail has no Category/Type or is_unit not set. Checking database directly...");
                            
                            $productCategoryFromDB = null;
                            if ($detail->product_category_id) {
                                $productCategoryFromDB = \DB::table('product_categories')
                                    ->where('id', $detail->product_category_id)
                                    ->select('id', 'name', 'is_unit')
                                    ->first();
                            }

                            if ($productCategoryFromDB) {
                                $detailIsUnit = ($productCategoryFromDB->is_unit == 1 || $productCategoryFromDB->is_unit === true);
                                $productTypeName = $productCategoryFromDB->name;
                            } else {
                                $productTypeFromDB = \DB::table('product_types')
                                    ->where('id', $productTypeId)
                                    ->select('id', 'name', 'is_unit')
                                    ->first();
                                
                                if ($productTypeFromDB) {
                                    $detailIsUnit = ($productTypeFromDB->is_unit == 1 || $productTypeFromDB->is_unit === true);
                                    $productTypeName = $productTypeFromDB->name;
                                } else {
                                    \Log::warning("  ❌ Category/Type not found in database. Skipping detail.");
                                    continue;
                                }
                            }
                        }
                        
                        // Skip if job type doesn't match material type
                        if ($needUnits && !$detailIsUnit) {
                            continue;
                        }
                        
                        if ($needNonUnits && $detailIsUnit) {
                            continue;
                        }
                        
                    } else {
                    }
                    
                    // PRIORITY 1: For aroma/refill/variant product type, use aromaProduct from quotation FIRST
                    $isAromaType = $productTypeName && (
                        stripos($productTypeName, 'aroma') !== false || 
                        stripos($productTypeName, 'refill') !== false ||
                        stripos($productTypeName, 'variant') !== false
                    );
                    
                    if ($isAromaType && $aromaProduct) {
                        // PRIORITY: Aroma dari Quotation (yang dipilih di quotation) > Aroma dari Master Rental
                        // But verify is_unit matches job type requirement
                        if ($filterByUnitType) {
                            $aromaProductIsUnit = $aromaProduct->productType && $aromaProduct->productType->is_unit;
                            if ($needUnits && !$aromaProductIsUnit) {
                                $product = null; // Don't use aromaProduct, try other sources
                            } elseif ($needNonUnits && $aromaProductIsUnit) {
                                $product = null; // Don't use aromaProduct, try other sources
                            } else {
                                $product = $aromaProduct;
                            }
                        } else {
                            $product = $aromaProduct;
                        }
                    }
                    
                    // Only try other sources if product not set yet
                    if (!$product && $detail->masterProduct) {
                        // PRIORITY 2: Use product from rental_detail (yang sudah dipilih di Material List)
                        // BUT: Validate is_unit matches job type requirement
                        $candidateProduct = $detail->masterProduct;
                        
                        // Load productCategory if not already loaded
                        if (!$candidateProduct->relationLoaded('productCategory')) {
                            $candidateProduct->load('productCategory');
                        }
                        // Still load productType for backward compatibility
                        if (!$candidateProduct->relationLoaded('productType')) {
                            $candidateProduct->load('productType');
                        }
                        
                        // Early validation: Check if product's is_unit matches job type requirement
                        if ($filterByUnitType) {
                            // Normalize is_unit check
                            $candidateProductIsUnit = false;
                            
                            if ($candidateProduct->productCategory && isset($candidateProduct->productCategory->is_unit)) {
                                $candidateProductIsUnit = ($candidateProduct->productCategory->is_unit == 1 || $candidateProduct->productCategory->is_unit === true);
                            } elseif ($candidateProduct->productType && isset($candidateProduct->productType->is_unit)) {
                                $candidateProductIsUnit = ($candidateProduct->productType->is_unit == 1 || $candidateProduct->productType->is_unit === true);
                            } else {
                                // Double-check from database if relationship not loaded
                                \Log::warning("  ⚠️ Product '{$candidateProduct->name}' has no Category/Type or is_unit. Checking database directly...");
                                
                                $productTypeCheck = \DB::table('master_products as mp')
                                    ->join('product_categories as pc', 'mp.product_category_id', '=', 'pc.id')
                                    ->where('mp.id', $candidateProduct->id)
                                    ->select('pc.is_unit', 'pc.name as product_category_name')
                                    ->first();
                                
                                if ($productTypeCheck) {
                                    $candidateProductIsUnit = ($productTypeCheck->is_unit == 1 || $productTypeCheck->is_unit === true);
                                } else {
                                    \Log::warning("  ❌ Product '{$candidateProduct->name}' (ID: {$candidateProduct->id}) not found in database. Will try other sources.");
                                    $product = null; // Will fall through to PRIORITY 3 (allowedProducts)
                                }
                            }
                            
                            if ($needUnits && !$candidateProductIsUnit) {
                                \Log::warning("  ⚠️ Detail '{$productTypeName}': Product '{$candidateProduct->name}' from rental_detail is not a unit (is_unit: NO), but Install job requires units. Will try other sources.");
                                $product = null; // Don't use this product, try other sources
                            } elseif ($needNonUnits && $candidateProductIsUnit) {
                                \Log::warning("  ⚠️ Detail '{$productTypeName}': Product '{$candidateProduct->name}' from rental_detail is a unit (is_unit: YES), but Service job requires non-units. Will try other sources.");
                                $product = null; // Don't use this product, try other sources
                            } else {
                                $product = $candidateProduct;
                            }
                        } else {
                            // No filter - use as is
                            $product = $candidateProduct;
                        }
                    }
                    
                    // If product still not set after PRIORITY 2, try PRIORITY 3
                    if (!$product) {
                        // PRIORITY 3: If no product in rental_detail, check Material List (allowedProducts)
                        // Get first selected product from Material List that matches job type requirement
                        $selectedProducts = $detail->allowedProducts()
                            ->wherePivot('is_selected', true)
                            ->with('productType') // Load productType to check is_unit
                            ->get();
                        
                        if ($selectedProducts->count() > 0) {
                            // Filter by is_unit if needed
                            if ($filterByUnitType) {
                                $filteredProducts = $selectedProducts->filter(function($p) use ($needUnits, $needNonUnits) {
                                    // Normalize is_unit check (can be 1/0 or true/false)
                                    $pIsUnit = false;
                                    if ($p->productType && isset($p->productType->is_unit)) {
                                        $pIsUnit = ($p->productType->is_unit == 1 || $p->productType->is_unit === true);
                                    }
                                    if ($needUnits) return $pIsUnit;
                                    if ($needNonUnits) return !$pIsUnit;
                                    return true;
                                });
                                
                                if ($filteredProducts->count() > 0) {
                                    $product = $filteredProducts->first();
                                }
                            } else {
                                $product = $selectedProducts->first();
                            }
                        }
                        
                        if (!$product) {
                            // Fallback: Get first product from Material List (even if not selected) that matches job type
                            $allAllowedProducts = $detail->allowedProducts()
                                ->with('productType') // Load productType to check is_unit
                                ->get();
                            
                            if ($allAllowedProducts->count() > 0) {
                                if ($filterByUnitType) {
                                    $filteredProducts = $allAllowedProducts->filter(function($p) use ($needUnits, $needNonUnits) {
                                        // Normalize is_unit check (can be 1/0 or true/false)
                                        $pIsUnit = false;
                                        if ($p->productType && isset($p->productType->is_unit)) {
                                            $pIsUnit = ($p->productType->is_unit == 1 || $p->productType->is_unit === true);
                                        }
                                        if ($needUnits) return $pIsUnit;
                                        if ($needNonUnits) return !$pIsUnit;
                                        return true;
                                    });
                                    
                                    if ($filteredProducts->count() > 0) {
                                        $product = $filteredProducts->first();
                                        \Log::warning("  ⚠ Detail '{$productTypeName}': No product selected in Material List, using first available (filtered): {$product->name} (ID: {$product->id})");
                                    }
                                } else {
                                    $product = $allAllowedProducts->first();
                                    \Log::warning("  ⚠ Detail '{$productTypeName}': No product selected in Material List, using first available: {$product->name} (ID: {$product->id})");
                                }
                            }
                        }
                    }
                    
                    if (!$product) {
                        \Log::warning("  ❌ Detail '{$productTypeName}': No product found. Skipping.");
                        continue;
                    }
                    
                    // DOUBLE CHECK: Verify product's is_unit matches job type requirement (only for install and service jobs)
                    if ($filterByUnitType) {
                        // Load productType if not already loaded
                        if (!$product->relationLoaded('productType')) {
                            $product->load('productType');
                        }
                        
                        // Normalize is_unit check (can be 1/0 or true/false)
                        $productIsUnit = false;
                        if ($product->productCategory && isset($product->productCategory->is_unit)) {
                            $productIsUnit = ($product->productCategory->is_unit == 1 || $product->productCategory->is_unit === true);
                        } elseif ($product->productType && isset($product->productType->is_unit)) {
                            $productIsUnit = ($product->productType->is_unit == 1 || $product->productType->is_unit === true);
                        }
                        
                        // Double-check from database if needed
                        if (!$product->productCategory || !isset($product->productCategory->is_unit)) {
                            \Log::warning("  ⚠️ Product '{$product->name}' has no Category/Type or is_unit. Checking database directly...");
                            
                            $productTypeCheck = \DB::table('master_products as mp')
                                ->join('product_categories as pc', 'mp.product_category_id', '=', 'pc.id')
                                ->where('mp.id', $product->id)
                                ->select('pc.is_unit', 'pc.name as product_category_name')
                                ->first();
                            
                            if (!$productTypeCheck) {
                                $productTypeCheck = \DB::table('master_products as mp')
                                    ->join('product_types as pt', 'mp.product_type_id', '=', 'pt.id')
                                    ->where('mp.id', $product->id)
                                    ->select('pt.is_unit', 'pt.name as product_type_name')
                                    ->first();
                            }
                            
                            if ($productTypeCheck) {
                                $productIsUnit = ($productTypeCheck->is_unit == 1 || $productTypeCheck->is_unit === true);
                            } else {
                                \Log::warning("  ❌ Product '{$product->name}' (ID: {$product->id}) not found in database. Skipping.");
                                continue;
                            }
                        }
                        
                        // Final validation: product's is_unit must match job type
                        if ($needUnits && !$productIsUnit) {
                            \Log::warning("  ❌ Product '{$product->name}' is not a unit (is_unit: NO), but Install job requires units. Skipping.");
                            continue;
                        }
                        
                        if ($needNonUnits && $productIsUnit) {
                            \Log::warning("  ❌ Product '{$product->name}' is a unit (is_unit: YES), but Service job requires non-units. Skipping.");
                            continue;
                        }
                        
                    }

                    // Get quantity from rental detail
                    $quantity = $detail->quantity ?? 0;
                    if ($quantity <= 0) {
                        \Log::warning("  ⚠ Detail '{$productTypeName}': Quantity is 0 or negative. Skipping.");
                        continue;
                    }
                    
                    $totalQuantity += $quantity;
                    
                    // Set first product found for Material Issue header
                    if (!$firstProduct) {
                        $firstProduct = $product;
                    }

                    // Add to material issue items array
                    $materialIssueItems[] = [
                        'product_id' => $product->id,
                        'room_name' => $jaRoom->room_name,
                        'quantity' => $quantity,
                        'convert' => 1,
                        'bom_quantity' => $product->bom_quantity ?? 0,
                        'unit_price' => $product->last_unit_price ?? 0,
                        'total_price' => ($product->last_unit_price ?? 0) * $quantity,
                        'notes' => "Room: {$jaRoom->room_name}, Rental: {$rental->rental_name}, Product Type: {$productTypeName}, RentalDetailID: {$detail->id}",
                    ];
                }
            }

            if (empty($materialIssueItems)) {
                \Log::warning("⚠️ No material issue items found. Cannot create Material Issue.");
                \Log::warning("   - Job Type: {$jobType}");
                \Log::warning("   - Job Advice Type: {$jobAdviceType}");
                \Log::warning("   - Rooms count: {$rooms->count()}");
                \Log::warning("   - Need Units: " . ($needUnits ? 'Yes' : 'No'));
                \Log::warning("   - Need Non-Units: " . ($needNonUnits ? 'Yes' : 'No'));
                \Log::warning("   - Filter By Unit Type: " . ($filterByUnitType ? 'Yes' : 'No'));
                \Log::warning("   - Possible reasons:");
                \Log::warning("     1. No rental details found in Master Rental");
                \Log::warning("     2. All materials filtered out (is_unit mismatch)");
                \Log::warning("     3. No products selected in rental details");
                \Log::warning("   - For Service jobs: Make sure Master Rental has non-unit materials (liquid, refill, cleaner, etc.) with is_unit = false");
                return;
            }

            // MOM CONSOLIDATION: Check if ANY assignment for this SAME JOB NUMBER already has a MaterialIssue
            $materialIssue = null;
            if ($jobSchedule->job_number) {
                $materialIssue = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule.jobSchedule', function($q) use ($jobSchedule) {
                    $q->where('job_number', $jobSchedule->job_number);
                })
                ->whereIn('status', ['pending', 'draft', 'approved']) // Only reuse if not yet 'issued'
                ->where('warehouse_id', $warehouse->id)
                ->first();
            }

            // Create 1 Material Issue for all rooms or reuse existing
            try {
                if ($materialIssue) {
                    $issueNumber = $materialIssue->issue_number;
                } else {
                    $materialIssue = MaterialIssue::create([
                        'issue_number' => $issueNumber,
                        'warehouse_id' => $warehouse->id,
                        'issued_by' => Auth::id(),
                        'team_id' => $team->id ?? null,
                        'product_id' => $firstProduct ? $firstProduct->id : null,
                        'issue_date' => $jobSchedule->schedule_date ?? $jobAssignSchedule->assigned_date ?? now()->toDateString(),
                        'quantity' => $totalQuantity,
                        'unit_price' => 0,
                        'total_amount' => 0,
                        'requested_by' => Auth::id(),
                        'request_reason' => 'installation',
                        'status' => 'draft',
                        'priority' => 'medium',
                        'description' => "Material issue for Job Schedule {$jobSchedule->job_number} - {$rooms->count()} room(s)",
                        'notes' => "Auto-generated from Job Assign Schedule. Contains materials for " . $rooms->count() . " room(s).",
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }

                // Create Material Issue Items for all rooms
                foreach ($materialIssueItems as $itemData) {
                    // Idempotency: skip if item with same job_assign_schedule_id, product_id, and room_name exists in this MI
                    $existsInMI = \App\Models\MaterialIssueItem::where('material_issue_id', $materialIssue->id)
                        ->where('job_assign_schedule_id', $jobAssignSchedule->id)
                        ->where('product_id', $itemData['product_id'])
                        ->where('room_name', $itemData['room_name'])
                        ->exists();

                    if (!$existsInMI) {
                        \App\Models\MaterialIssueItem::create([
                            'material_issue_id' => $materialIssue->id,
                            'job_assign_schedule_id' => $jobAssignSchedule->id,
                            'product_id' => $itemData['product_id'],
                            'room_name' => $itemData['room_name'],
                            'quantity' => $itemData['quantity'],
                            'convert' => $itemData['convert'],
                            'bom_quantity' => $itemData['bom_quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $itemData['total_price'],
                            'notes' => $itemData['notes'],
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                    }
                }

                // Create Job Assign Material Issue link if not exists
                \App\Models\JobAssignMaterialIssue::firstOrCreate(
                    [
                        'job_assign_schedule_id' => $jobAssignSchedule->id,
                        'material_issue_id' => $materialIssue->id,
                    ],
                    [
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );

                $hasMaterialItemsForAssignment = \App\Models\MaterialIssueItem::where('material_issue_id', $materialIssue->id)
                    ->where('job_assign_schedule_id', $jobAssignSchedule->id)
                    ->exists();

                if ($hasMaterialItemsForAssignment && in_array($jobSchedule->status, ['assign_material', 'assign_team', 'scheduled', 'new_job'], true)) {
                    $jobSchedule->update([
                        'status' => 'assign_material',
                        'updated_by' => Auth::id(),
                    ]);
                }

                
            } catch (\Exception $e) {
                \Log::error("❌ Failed to create Material Issue: " . $e->getMessage());
                \Log::error("Stack trace: " . $e->getTraceAsString());
                throw $e;
            }
            
            return;
            
            // OLD LOGIC BELOW (DISABLED) - Process each room
            $materialIssuesCreated = 0;
            foreach ($rooms as $jaRoom) {
                
                $rental = $jaRoom->rentalProduct;
                if (!$rental) {
                    \Log::warning("❌ Rental product not found for JA Room {$jaRoom->id} (rental_product_id: " . ($jaRoom->rental_product_id ?? 'null') . ")");
                    continue;
                }


                // Get ALL rental components (not just preferred products)
                // IMPORTANT: Always get from live Master Rental data, not from snapshot
                
                // Get all components (including inactive for logging)
                $allComponents = $rental->rentalComponents()
                    ->with(['preferredProducts.productType', 'allowedProducts.productType'])
                    ->orderBy('sort_order')
                    ->get();
                
                foreach ($allComponents as $idx => $comp) {
                }
                
                // Filter only active components for processing
                $components = $allComponents->where('is_active', true)->values();

                
                if ($components->isEmpty()) {
                    \Log::warning("❌ No active components found for rental {$rental->rental_name} (ID: {$rental->id})");
                    continue;
                }
                
                // Log each component
                foreach ($components as $index => $component) {
                }

                // Get quotation room for this JA room to get aroma/variant
                // MOM9: Check quotation directly (for job advice from quotation) or through contract
                $quotationRoom = null;
                $aromaProduct = null;
                $quotation = null;
                
                // MOM9: Get quotation - either directly or through contract
                if ($jobAdvice->quotation_id) {
                    // Job advice from quotation directly
                    $quotation = $jobAdvice->quotation;
                } elseif ($jobAdvice->contract && $jobAdvice->contract->quotation) {
                    // Job advice from contract (existing flow)
                    $quotation = $jobAdvice->contract->quotation;
                }
                
                if ($quotation) {
                    // MOM9: If job advice has quotation_room_id, use it directly
                    if ($jaRoom->quotation_room_id) {
                        $quotationRoom = $quotation->quotationRooms
                            ->where('id', $jaRoom->quotation_room_id)
                            ->first();
                    }
                    
                    // If not found and has contractRoom, try matching by room
                    if (!$quotationRoom && $jaRoom->contractRoom) {
                        $contractRoom = $jaRoom->contractRoom;
                        $room = $contractRoom->room;
                        
                        // Try to find quotation room by room_id first
                        $quotationRoom = $quotation->quotationRooms
                            ->where('room_id', $room->id ?? null)
                            ->first();
                        
                        // If not found by room_id, try by room_name
                        if (!$quotationRoom && $room && $room->room_name) {
                            $quotationRoom = $quotation->quotationRooms
                                ->where('room_name', $room->room_name)
                                ->first();
                        }
                    }
                    
                    // If still not found, try by room_name from JA room
                    if (!$quotationRoom && $jaRoom->room_name) {
                        $quotationRoom = $quotation->quotationRooms
                            ->where('room_name', $jaRoom->room_name)
                            ->first();
                    }
                    
                    if ($quotationRoom && $quotationRoom->aromaProduct) {
                        $aromaProduct = $quotationRoom->aromaProduct;
                    }
                }

                // Process each component - create material issue for EACH component
                foreach ($components as $component) {
                    
                    // For "Aroma Refill" component, use aromaProduct from quotation room if available
                    $product = null;
                    if (stripos($component->component_name, 'aroma') !== false || stripos($component->component_name, 'refill') !== false) {
                        if ($aromaProduct) {
                            $product = $aromaProduct;
                        } else {
                        }
                    }
                    
                    // If not aroma component or no aroma product from quotation, use preferred/allowed product
                    if (!$product) {
                        $preferredProducts = $component->preferredProducts()->get();
                        $product = $preferredProducts->first();
                        
                        if (!$product) {
                            // Fallback to first allowed active product
                            $allowedProducts = $component->allowedProducts()
                                ->wherePivot('is_active', true)
                                ->get();
                            $product = $allowedProducts->first();
                        }
                    }

                    if (!$product) {
                        \Log::warning("❌ No product found for component {$component->component_name} (ID: {$component->id}) in rental {$rental->rental_name}. Component will be skipped.");
                        \Log::warning("   - Component is_active: " . ($component->is_active ? 'Yes' : 'No'));
                        \Log::warning("   - Preferred products count: " . $component->preferredProducts()->count());
                        \Log::warning("   - Allowed products count: " . $component->allowedProducts()->wherePivot('is_active', true)->count());
                        continue;
                    }


                    // Check if material issue already exists for this combination (same job_assign_schedule, product, and quantity)
                    $existingMaterialIssue = JobAssignMaterialIssue::where('job_assign_schedule_id', $jobAssignSchedule->id)
                        ->whereHas('materialIssue', function($q) use ($product, $component) {
                            $q->where('product_id', $product->id)
                              ->where('quantity', $component->quantity ?? 0);
                        })
                        ->first();

                    if ($existingMaterialIssue) {
                        continue;
                    }

                    // Generate unique issue number (with retry loop to handle race conditions)
                    $issueNumber = null;
                    $maxRetries = 10;
                    $retry = 0;
                    
                    do {
                        try {
                            $issueNumber = $this->generateUniqueIssueNumber($warehouse->id);
                            
                            // Double-check if issue number already exists (handles race conditions)
                            $exists = MaterialIssue::withoutTrashed()
                                ->where('issue_number', $issueNumber)
                                ->exists();
                            
                            if (!$exists) {
                                break; // Found unique number
                            }
                            
                            // If exists, retry
                            $retry++;
                            \Log::warning("Issue number {$issueNumber} already exists. Retrying... (Attempt {$retry}/{$maxRetries})");
                            usleep(200000); // 0.2 second delay
                            
                        } catch (\Exception $e) {
                            \Log::error("Failed to generate unique issue number: " . $e->getMessage());
                            $retry++;
                            if ($retry >= $maxRetries) {
                                \Log::error("Failed to generate unique issue number after {$maxRetries} attempts. Skipping component.");
                                continue 2; // Skip to next component
                            }
                            usleep(200000); // 0.2 second delay
                        }
                    } while ($retry < $maxRetries);
                    
                    if (!$issueNumber) {
                        \Log::error("Failed to generate unique issue number after {$maxRetries} attempts. Skipping component.");
                        continue; // Skip this component
                    }

                    // Create Material Issue with auto-approved status (for auto-created from Job Assign Schedule)
                    try {
                        $materialIssue = MaterialIssue::create([
                            'issue_number' => $issueNumber,
                        'warehouse_id' => $warehouse->id,
                        'issued_by' => Auth::id(),
                        'team_id' => $team->id ?? null,
                        'product_id' => $product->id,
                        'issue_date' => $jobSchedule->schedule_date ?? $jobAssignSchedule->assigned_date ?? now()->toDateString(),
                        'quantity' => $component->quantity ?? 0,
                        'unit_price' => $product->last_unit_price ?? 0,
                        'total_amount' => ($product->last_unit_price ?? 0) * ($component->quantity ?? 0),
                        'requested_by' => Auth::id(),
                        'request_reason' => 'installation',
                        'status' => 'approved', // Auto-approved for auto-created material issues
                        'priority' => 'medium',
                        'description' => "Auto-created material issue for {$component->component_name} from rental {$rental->rental_name}",
                        'notes' => "Auto-generated from rental component. Component: {$component->component_name}, Rental: {$rental->rental_name}, Room: {$jaRoom->room_name}",
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Handle duplicate entry error
                        if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            \Log::warning("Duplicate issue number {$issueNumber} detected during insert. Skipping component.");
                            continue; // Skip this component
                        }
                        throw $e; // Re-throw other database errors
                    }

                    // Create Job Assign Material Issue
                    JobAssignMaterialIssue::create([
                        'job_assign_schedule_id' => $jobAssignSchedule->id,
                        'material_issue_id' => $materialIssue->id,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    $materialIssuesCreated++;
                }
            }

            if ($materialIssuesCreated > 0) {
            } else {
            }

        } catch (\Exception $e) {
            \Log::error("Error auto-creating material issues for Job Assign Schedule {$jobAssignSchedule->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate unique issue number
     * MOM10: Use DocumentNumberService to generate material issue number with branch code                                                                      
     */
    private function generateUniqueIssueNumber($warehouseId = null): string     
    {
        // Use DocumentNumberService to generate material issue number
        $documentNumberService = new DocumentNumberService();
        return $documentNumberService->generate(
            'material_issue',
            null, // Will get from warehouse
            null,
            null,
            null,
            null,
            $warehouseId // Get branch from warehouse
        );
    }
    
    /**
     * MOM13: Generate BA Number
     * Format: BranchCode-BA/YY-MM/NNNN
     * BA = Berita Acara
     */
    private function generateBANumber(JobSchedule $jobSchedule)
    {
        $branchCode = 'JKT'; // Default branch code
        $typeCode = 'BA';
        
        // Format: BranchCode-BA/YY-MM/NNNN
        $yearMonth = date('y-m');
        
        // Count existing job schedules with BA number in same month
        $count = JobSchedule::withTrashed()
            ->where('ba_number', 'like', "{$branchCode}-{$typeCode}/{$yearMonth}/%")
            ->count();
        
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return "{$branchCode}-{$typeCode}/{$yearMonth}/{$sequence}";
    }
    private function getJobAssignScheduleBuildings()
    {
        return Building::orderBy('nama_gedung')->get(['id', 'name', 'nama_gedung']);
    }

    private function getJobAssignScheduleTeams(bool $forIndex = true)
    {
        $cacheKey = $forIndex ? 'job-assign-schedules:teams:index' : 'job-assign-schedules:teams:form';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($forIndex) {
            $query = Team::query();

            if ($forIndex) {
                $query->where('active_status', true)->orderBy('team_code');
            } else {
                $query->orderBy('team_name');
            }

            return $query->get();
        });
    }

    private function getJobAssignScheduleCustomers()
    {
        return Cache::remember('job-assign-schedules:customers', now()->addMinutes(10), function () {
            return Customer::orderBy('name')->get(['id', 'name']);
        });
    }

    private function getSchedulableJobSchedules($includeId = null)
    {
        return Cache::remember('job-assign-schedules:job-schedules:' . ($includeId ?: 'scheduled'), now()->addMinutes(2), function () use ($includeId) {
            return JobSchedule::with(['jobAdvice.customer:id,name', 'building:id,name,nama_gedung'])
                ->where(function ($query) use ($includeId) {
                    $query->where('status', 'scheduled');

                    if ($includeId) {
                        $query->orWhere('id', $includeId);
                    }
                })
                ->orderBy('schedule_date', 'asc')
                ->get();
        });
    }

    private function getJobAssignScheduleStatistics(): array
    {
        return Cache::remember('job-assign-schedules:statistics', now()->addSeconds(60), function () {
            return [
                'total' => JobAssignSchedule::count(),
                'assigned' => JobAssignSchedule::where('status', 'assigned')->count(),
                'in_progress' => JobAssignSchedule::where('status', 'in_progress')->count(),
                'completed' => JobAssignSchedule::where('status', 'completed')->count(),
                'cancelled' => JobAssignSchedule::where('status', 'cancelled')->count(),
            ];
        });
    }

}
