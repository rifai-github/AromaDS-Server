<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\JobSchedule;
use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Complaint::with([
            'customer',
            'contract',
            'jobSchedule',
            'building',
            'room',
            'reportedBy',
            'assignedTo',
            'resolvedBy'
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('complaint_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by complaint type
        if ($request->filled('complaint_type')) {
            $query->where('complaint_type', $request->complaint_type);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by contract
        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        // Filter by assigned user
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter overdue
        if ($request->filled('overdue') && $request->overdue == 'true') {
            $query->overdue();
        }

        // Filter high priority
        if ($request->filled('high_priority') && $request->high_priority == 'true') {
            $query->highPriority();
        }

        $complaints = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $complaints
            ]);
        }

        return view('marketing.complaints.index', compact('complaints'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $customers = Customer::all();
        $users = User::all();
        $complaintTypes = [
            'service_quality' => 'Service Quality',
            'product_quality' => 'Product Quality',
            'aroma_issue' => 'Aroma Issue',
            'unit_malfunction' => 'Unit Malfunction',
            'billing' => 'Billing',
            'staff_behavior' => 'Staff Behavior',
            'schedule' => 'Schedule',
            'other' => 'Other'
        ];
        $priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'customers' => $customers,
                    'users' => $users,
                    'complaint_types' => $complaintTypes,
                    'priorities' => $priorities
                ]
            ]);
        }

        return view('marketing.complaints.create', compact('customers', 'users', 'complaintTypes', 'priorities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'job_schedule_id' => 'nullable|exists:job_schedules,id',
            'building_id' => 'nullable|exists:buildings,id',
            'room_id' => 'nullable|exists:rooms,id',
            'complaint_type' => 'required|in:service_quality,product_quality,aroma_issue,unit_malfunction,billing,staff_behavior,schedule,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
            'target_resolution_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Handle file uploads
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('complaints/attachments', 'public');
                    $attachmentPaths[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ];
                }
            }

            $complaint = Complaint::create([
                'complaint_number' => Complaint::generateComplaintNumber(),
                'customer_id' => $request->customer_id,
                'contract_id' => $request->contract_id,
                'job_schedule_id' => $request->job_schedule_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'complaint_type' => $request->complaint_type,
                'priority' => $request->priority,
                'subject' => $request->subject,
                'description' => $request->description,
                'attachments' => $attachmentPaths,
                'target_resolution_date' => $request->target_resolution_date ?? now()->addDays(7),
                'status' => Complaint::STATUS_OPEN,
                'assigned_to' => $request->assigned_to,
                'reported_at' => now(),
                'reported_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            DB::commit();

            Log::info("Complaint created: {$complaint->complaint_number}", [
                'customer' => $request->customer_id,
                'type' => $request->complaint_type,
                'priority' => $request->priority
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Complaint created successfully',
                'data' => $complaint->load(['customer', 'contract', 'reportedBy', 'assignedTo'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error creating complaint: " . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to create complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Complaint $complaint)
    {
        $complaint->load([
            'customer',
            'contract',
            'jobSchedule',
            'building',
            'room',
            'jobAdvice',
            'reportedBy',
            'assignedTo',
            'resolvedBy'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $complaint
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Complaint $complaint)
    {
        $complaint->load([
            'customer',
            'contract',
            'jobSchedule',
            'building',
            'room',
            'reportedBy',
            'assignedTo',
            'resolvedBy'
        ]);

        $customers = Customer::all();
        $users = User::all();
        $complaintTypes = [
            'service_quality' => 'Service Quality',
            'product_quality' => 'Product Quality',
            'aroma_issue' => 'Aroma Issue',
            'unit_malfunction' => 'Unit Malfunction',
            'billing' => 'Billing',
            'staff_behavior' => 'Staff Behavior',
            'schedule' => 'Schedule',
            'other' => 'Other'
        ];
        $priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

        return response()->json([
            'status' => 'success',
            'data' => $complaint,
            'customers' => $customers,
            'users' => $users,
            'complaint_types' => $complaintTypes,
            'priorities' => $priorities
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Complaint $complaint)
    {
        if ($complaint->isResolved || $complaint->isClosed) {
            return response()->json(['status' => 'error', 'message' => 'Cannot update resolved or closed complaint'], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'job_schedule_id' => 'nullable|exists:job_schedules,id',
            'building_id' => 'nullable|exists:buildings,id',
            'room_id' => 'nullable|exists:rooms,id',
            'complaint_type' => 'required|in:service_quality,product_quality,aroma_issue,unit_malfunction,billing,staff_behavior,schedule,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'target_resolution_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $complaint->update([
                'customer_id' => $request->customer_id,
                'contract_id' => $request->contract_id,
                'job_schedule_id' => $request->job_schedule_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'complaint_type' => $request->complaint_type,
                'priority' => $request->priority,
                'subject' => $request->subject,
                'description' => $request->description,
                'target_resolution_date' => $request->target_resolution_date,
                'assigned_to' => $request->assigned_to,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            Log::info("Complaint updated: {$complaint->complaint_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Complaint updated successfully',
                'data' => $complaint->load(['customer', 'contract', 'reportedBy', 'assignedTo'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error updating complaint: " . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to update complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Complaint $complaint)
    {
        if ($complaint->isInProgress || $complaint->isResolved) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete complaint in progress or resolved'], 403);
        }

        try {
            // Delete attachments
            if ($complaint->attachments) {
                foreach ($complaint->attachments as $attachment) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }

            $complaint->delete();
            Log::info("Complaint deleted: {$complaint->complaint_number}");
            return response()->json(['status' => 'success', 'message' => 'Complaint deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting complaint: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Acknowledge complaint
     */
    public function acknowledge(Complaint $complaint)
    {
        if (!$complaint->isOpen) {
            return response()->json(['status' => 'error', 'message' => 'Complaint must be open to acknowledge'], 403);
        }

        try {
            $complaint->acknowledge(Auth::id());
            Log::info("Complaint acknowledged: {$complaint->complaint_number}");
            return response()->json(['status' => 'success', 'message' => 'Complaint acknowledged successfully']);
        } catch (\Exception $e) {
            Log::error("Error acknowledging complaint: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to acknowledge complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign to user
     */
    public function assign(Request $request, Complaint $complaint)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $complaint->assignTo($request->user_id);
            Log::info("Complaint assigned: {$complaint->complaint_number} to user {$request->user_id}");
            return response()->json(['status' => 'success', 'message' => 'Complaint assigned successfully']);
        } catch (\Exception $e) {
            Log::error("Error assigning complaint: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to assign complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Resolve complaint
     */
    public function resolve(Request $request, Complaint $complaint)
    {
        if (!$complaint->isInProgress && !$complaint->isOpen) {
            return response()->json(['status' => 'error', 'message' => 'Complaint must be in progress or open to resolve'], 403);
        }

        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $complaint->resolve($request->resolution_notes, Auth::id());
            Log::info("Complaint resolved: {$complaint->complaint_number}");
            return response()->json(['status' => 'success', 'message' => 'Complaint resolved successfully']);
        } catch (\Exception $e) {
            Log::error("Error resolving complaint: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to resolve complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Close complaint
     */
    public function close(Complaint $complaint)
    {
        if (!$complaint->isResolved) {
            return response()->json(['status' => 'error', 'message' => 'Complaint must be resolved to close'], 403);
        }

        try {
            $complaint->close(Auth::id());
            Log::info("Complaint closed: {$complaint->complaint_number}");
            return response()->json(['status' => 'success', 'message' => 'Complaint closed successfully']);
        } catch (\Exception $e) {
            Log::error("Error closing complaint: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to close complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject complaint
     */
    public function reject(Request $request, Complaint $complaint)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $complaint->reject($request->reason);
            Log::info("Complaint rejected: {$complaint->complaint_number}");
            return response()->json(['status' => 'success', 'message' => 'Complaint rejected successfully']);
        } catch (\Exception $e) {
            Log::error("Error rejecting complaint: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to reject complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reopen complaint
     */
    public function reopen(Complaint $complaint)
    {
        if (!$complaint->isClosed && !$complaint->isRejected) {
            return response()->json(['status' => 'error', 'message' => 'Only closed or rejected complaints can be reopened'], 403);
        }

        try {
            $complaint->reopen();
            Log::info("Complaint reopened: {$complaint->complaint_number}");
            return response()->json(['status' => 'success', 'message' => 'Complaint reopened successfully']);
        } catch (\Exception $e) {
            Log::error("Error reopening complaint: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to reopen complaint: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add satisfaction rating
     */
    public function addSatisfaction(Request $request, Complaint $complaint)
    {
        if (!$complaint->isResolved && !$complaint->isClosed) {
            return response()->json(['status' => 'error', 'message' => 'Complaint must be resolved or closed to add satisfaction rating'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $complaint->addSatisfactionRating($request->rating, $request->feedback);
            Log::info("Satisfaction rating added for complaint: {$complaint->complaint_number}, rating: {$request->rating}");
            return response()->json(['status' => 'success', 'message' => 'Satisfaction rating added successfully']);
        } catch (\Exception $e) {
            Log::error("Error adding satisfaction rating: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to add satisfaction rating: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Set follow-up
     */
    public function setFollowUp(Request $request, Complaint $complaint)
    {
        $validator = Validator::make($request->all(), [
            'follow_up_date' => 'required|date|after_or_equal:today',
            'follow_up_notes' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $complaint->setFollowUp($request->follow_up_date, $request->follow_up_notes);
            Log::info("Follow-up set for complaint: {$complaint->complaint_number}");
            return response()->json(['status' => 'success', 'message' => 'Follow-up set successfully']);
        } catch (\Exception $e) {
            Log::error("Error setting follow-up: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to set follow-up: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        try {
            $stats = [
                'total_complaints' => Complaint::count(),
                'open' => Complaint::open()->count(),
                'in_progress' => Complaint::inProgress()->count(),
                'resolved' => Complaint::resolved()->count(),
                'closed' => Complaint::closed()->count(),
                'overdue' => Complaint::overdue()->count(),
                'urgent' => Complaint::urgent()->count(),
                'high_priority' => Complaint::highPriority()->count(),
                'avg_resolution_time' => Complaint::whereNotNull('resolved_at')->avg('resolution_time_in_hours'),
                'avg_satisfaction_rating' => Complaint::whereNotNull('satisfaction_rating')->avg('satisfaction_rating')
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching complaint statistics: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch statistics'], 500);
        }
    }
}

