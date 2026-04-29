<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobSchedule;
use App\Models\JobPhoto;
use App\Models\TechnicianActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TechnicianController extends Controller
{
    /**
     * Get assigned jobs for technician
     */
    public function getJobs(Request $request)
    {
        $technicianId = Auth::id();
        
        $query = JobSchedule::with(['company', 'building', 'assignedTechnician'])
            ->where('assigned_technician_id', $technicianId);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('work_status', $request->status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('schedule_date', $request->date);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $jobs = $query->orderBy('schedule_date', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $jobs,
            'message' => 'Jobs retrieved successfully'
        ]);
    }

    /**
     * Get specific job details
     */
    public function getJobDetails($jobId)
    {
        $technicianId = Auth::id();
        
        $job = JobSchedule::with([
            'company', 
            'building', 
            'assignedTechnician',
            'jobPhotos',
            'technicianActivities' => function($query) {
                $query->orderBy('activity_time', 'desc');
            }
        ])
        ->where('id', $jobId)
        ->where('assigned_technician_id', $technicianId)
        ->first();

        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found or not assigned to you'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $job,
            'message' => 'Job details retrieved successfully'
        ]);
    }

    /**
     * Start a job
     */
    public function startJob(Request $request, $jobId)
    {
        $technicianId = Auth::id();
        
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_address' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $job = JobSchedule::where('id', $jobId)
                ->where('assigned_technician_id', $technicianId)
                ->first();

            if (!$job) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found or not assigned to you'
                ], 404);
            }

            if ($job->work_status === 'in_progress') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job is already in progress'
                ], 400);
            }

            // MOM9: Auto-update status to "teknisi_tiba_dilokasi" if not already at location
            if (!in_array($job->status, ['teknisi_tiba_dilokasi', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'])) {
                $job->update([
                    'status' => 'teknisi_tiba_dilokasi',
                    'updated_by' => $technicianId
                ]);
            }
            
            // Update job status
            $job->update([
                'work_status' => 'in_progress',
                'started_at' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'technician_location' => $request->location_address,
                'location_updated_at' => now()
            ]);
            
            // MOM9: Auto-update status to "in_progress" when technician starts work
            // Status: barang_diambil → in_progress (ketika mulai pekerjaan diklik via apps)
            if (in_array($job->status, ['teknisi_tiba_dilokasi', 'barang_diambil', 'barang_siap_diambil', 'barang_dipersiapkan', 'assign_material', 'assign_team', 'scheduled', 'new_job'])) {
                $job->update([
                    'status' => 'in_progress',
                    'updated_by' => $technicianId
                ]);
            }

            // Create activity record
            TechnicianActivity::create([
                'technician_id' => $technicianId,
                'job_schedule_id' => $jobId,
                'activity_type' => 'start_work',
                'activity_time' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_address' => $request->location_address,
                'notes' => $request->notes,
                'metadata' => [
                    'device_info' => $request->header('User-Agent'),
                    'app_version' => $request->header('App-Version')
                ]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Job started successfully',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a job
     */
    public function completeJob(Request $request, $jobId)
    {
        $technicianId = Auth::id();
        
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_address' => 'nullable|string',
            'technician_notes' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $job = JobSchedule::where('id', $jobId)
                ->where('assigned_technician_id', $technicianId)
                ->first();

            if (!$job) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found or not assigned to you'
                ], 404);
            }

            if ($job->work_status !== 'in_progress') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job is not in progress'
                ], 400);
            }

            // Update job status
            $job->update([
                'work_status' => 'completed',
                'completed_at' => now(),
                'technician_notes' => $request->technician_notes,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'technician_location' => $request->location_address,
                'location_updated_at' => now()
            ]);
            
            // MOM9: Auto-update status to "teknisi_selesai_pengerjaan" when technician completes work
            // Status: in_progress → teknisi_selesai_pengerjaan (ketika semua room selesai dikerjakan)
            if (in_array($job->status, ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_tiba_dilokasi', 'barang_diambil', 'barang_siap_diambil', 'barang_dipersiapkan', 'assign_material', 'assign_team', 'scheduled', 'new_job'])) {
                $job->update([
                    'status' => 'teknisi_selesai_pengerjaan',
                    'updated_by' => $technicianId
                ]);
            }

            // MOM15: Real-time Invoice Trigger
            // If job is 'completed' (which is mapped to 'work_status' here, but affects overall flow)
            // We should check if this triggers invoice generation for the contract
            if ($job->jobAdvice && $job->jobAdvice->contract_id) {
                try {
                    $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
                    $invoiceService->attemptAutoInvoiceForContract($job->jobAdvice->contract_id);
                } catch (\Exception $e) {
                    \Log::error("Failed to trigger real-time invoice check from Technician App: " . $e->getMessage());
                }
            }

            // Create activity record
            TechnicianActivity::create([
                'technician_id' => $technicianId,
                'job_schedule_id' => $jobId,
                'activity_type' => 'complete_work',
                'activity_time' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_address' => $request->location_address,
                'notes' => $request->notes,
                'metadata' => [
                    'device_info' => $request->header('User-Agent'),
                    'app_version' => $request->header('App-Version')
                ]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Job completed successfully',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload photo for a job
     */
    public function uploadPhoto(Request $request, $jobId)
    {
        $technicianId = Auth::id();
        
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:10240', // 10MB max
            'photo_type' => 'required|in:before,after,progress,issue',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'photo_notes' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $job = JobSchedule::where('id', $jobId)
                ->where('assigned_technician_id', $technicianId)
                ->first();

            if (!$job) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found or not assigned to you'
                ], 404);
            }

            // Handle file upload
            $file = $request->file('photo');
            $fileName = time() . '_' . $technicianId . '_' . $jobId . '.' . $file->getClientOriginalExtension();
            $uploadPath = 'job-photos';
            $filePath = $uploadPath . '/' . $fileName;
            
            // Ensure directory exists
            $fullPath = public_path('uploads/' . $uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            // Move file to public/uploads directory
            $file->move($fullPath, $fileName);

            // Create photo record
            $photo = JobPhoto::create([
                'job_schedule_id' => $jobId,
                'job_schedule_room_id' => $request->job_schedule_room_id ?? $request->room_id,
                'photo_path' => $filePath,
                'photo_type' => $request->photo_type,
                'description' => $request->description,
                'uploaded_by' => $technicianId,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Photo uploaded successfully',
                'data' => $photo
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload photo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update technician location
     */
    public function updateLocation(Request $request)
    {
        $technicianId = Auth::id();
        
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_address' => 'nullable|string',
            'job_id' => 'nullable|exists:job_schedules,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update current job location if job_id provided
            if ($request->filled('job_id')) {
                $job = JobSchedule::where('id', $request->job_id)
                    ->where('assigned_technician_id', $technicianId)
                    ->first();

                if ($job) {
                    $job->update([
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'technician_location' => $request->location_address,
                        'location_updated_at' => now()
                    ]);
                }
            }

            // Create location activity record
            TechnicianActivity::create([
                'technician_id' => $technicianId,
                'job_schedule_id' => $request->job_id,
                'activity_type' => 'location_update',
                'activity_time' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_address' => $request->location_address,
                'metadata' => [
                    'device_info' => $request->header('User-Agent'),
                    'app_version' => $request->header('App-Version')
                ]
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Location updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get technician dashboard data
     */
    public function getDashboard()
    {
        $technicianId = Auth::id();
        
        $today = now()->toDateString();
        
        $stats = [
            'total_jobs' => JobSchedule::where('assigned_technician_id', $technicianId)->count(),
            'today_jobs' => JobSchedule::where('assigned_technician_id', $technicianId)
                ->whereDate('schedule_date', $today)->count(),
            'in_progress_jobs' => JobSchedule::where('assigned_technician_id', $technicianId)
                ->where('work_status', 'in_progress')->count(),
            'completed_today' => JobSchedule::where('assigned_technician_id', $technicianId)
                ->where('work_status', 'completed')
                ->whereDate('completed_at', $today)->count(),
        ];

        $recentActivities = TechnicianActivity::where('technician_id', $technicianId)
            ->with(['jobSchedule'])
            ->orderBy('activity_time', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => $stats,
                'recent_activities' => $recentActivities
            ],
            'message' => 'Dashboard data retrieved successfully'
        ]);
    }

    /**
     * Report issue for a job
     */
    public function reportIssue(Request $request, $jobId)
    {
        $technicianId = Auth::id();
        
        $validator = Validator::make($request->all(), [
            'issue_type' => 'required|string',
            'description' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_address' => 'nullable|string',
            'photos.*' => 'nullable|image|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $job = JobSchedule::where('id', $jobId)
                ->where('assigned_technician_id', $technicianId)
                ->first();

            if (!$job) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found or not assigned to you'
                ], 404);
            }

            // Create activity record
            TechnicianActivity::create([
                'technician_id' => $technicianId,
                'job_schedule_id' => $jobId,
                'activity_type' => 'issue_report',
                'activity_time' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_address' => $request->location_address,
                'notes' => $request->description,
                'metadata' => [
                    'issue_type' => $request->issue_type,
                    'device_info' => $request->header('User-Agent'),
                    'app_version' => $request->header('App-Version')
                ]
            ]);

            // Handle photo uploads if any
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $fileName = time() . '_issue_' . $technicianId . '_' . $jobId . '.' . $photo->getClientOriginalExtension();
                    $uploadPath = 'job-photos/issues';
                    $filePath = $uploadPath . '/' . $fileName;
                    
                    // Ensure directory exists
                    $fullPath = public_path('uploads/' . $uploadPath);
                    if (!file_exists($fullPath)) {
                        mkdir($fullPath, 0755, true);
                    }
                    
                    // Move file to public/uploads directory
                    $photo->move($fullPath, $fileName);

                    JobPhoto::create([
                        'job_schedule_id' => $jobId,
                        'job_schedule_room_id' => $request->job_schedule_room_id ?? $request->room_id,
                        'photo_path' => $filePath,
                        'description' => 'Issue Report: ' . $request->issue_type . ($request->description ? ' - ' . $request->description : ''),
                        'uploaded_by' => $technicianId,
                        'photo_type' => 'issue',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Issue reported successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to report issue: ' . $e->getMessage()
            ], 500);
        }
    }
}
