<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\JobSchedule;
use App\Models\JobReport;
use App\Models\TechnicianLocation;
use App\Models\TemperatureRecord;
use App\Models\JobSignature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OperationalController extends Controller
{
    /**
     * Get today's jobs for technician.
     */
    public function getTodayJobs(Request $request)
    {
        try {
            $technicianId = Auth::id();
            
            $jobs = JobSchedule::with(['company', 'building', 'jobReports', 'temperatureRecords'])
                ->where('assigned_technician_id', $technicianId)
                ->where('schedule_date', today())
                ->whereIn('status', ['assigned', 'in_progress'])
                ->orderBy('schedule_date', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $jobs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching today\'s jobs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job detail for mobile.
     */
    public function getJobDetail($id)
    {
        try {
            $job = JobSchedule::with([
                'company', 
                'building', 
                'jobReports', 
                'temperatureRecords', 
                'jobSignatures',
                'jobMaterials.product'
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching job detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start job.
     */
    public function startJob(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $job = JobSchedule::findOrFail($id);
            
            // Update job status
            $job->update([
                'status' => 'in_progress',
                'work_status' => 'in_progress',
                'started_at' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_updated_at' => now()
            ]);

            // Record technician location
            TechnicianLocation::create([
                'technician_id' => Auth::id(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Job started successfully',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error starting job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete job.
     */
    public function completeJob(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'job_type' => 'required|in:install,service,remove,maintenance',
            'temperature' => 'nullable|numeric|min:-50|max:100',
            'condition' => 'nullable|in:good,fair,poor',
            'refill_status' => 'nullable|in:full,half,empty,not_applicable',
            'notes' => 'nullable|string|max:1000',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'signature' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $job = JobSchedule::findOrFail($id);
            
            // Handle photo uploads
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('job-reports/photos', 'public');
                    $photoPaths[] = $path;
                }
            }

            // Create job report
            $jobReport = JobReport::create([
                'job_schedule_id' => $id,
                'technician_id' => Auth::id(),
                'job_type' => $request->job_type,
                'temperature' => $request->temperature,
                'condition' => $request->condition,
                'refill_status' => $request->refill_status,
                'photos' => $photoPaths,
                'signature' => $request->signature,
                'notes' => $request->notes,
                'completed_at' => now()
            ]);

            // Update job status
            $job->update([
                'status' => 'completed',
                'work_status' => 'completed',
                'completed_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Job completed successfully',
                'data' => $jobReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error completing job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update GPS location.
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $location = TechnicianLocation::create([
                'technician_id' => Auth::id(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Location updated successfully',
                'data' => $location
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record temperature.
     */
    public function recordTemperature(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_schedule_id' => 'required|exists:job_schedules,id',
            'room_id' => 'nullable|exists:master_rooms,id',
            'temperature' => 'required|numeric|min:-50|max:100',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $temperatureRecord = TemperatureRecord::create([
                'job_schedule_id' => $request->job_schedule_id,
                'room_id' => $request->room_id,
                'temperature' => $request->temperature,
                'recorded_at' => now(),
                'recorded_by' => Auth::id(),
                'notes' => $request->notes
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Temperature recorded successfully',
                'data' => $temperatureRecord
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error recording temperature: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit job report from mobile app
     * Used for syncing offline reports or submitting reports directly
     */
    public function submitJobReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_schedule_id' => 'required|exists:job_schedules,id',
            'job_type' => 'nullable|in:install,service,remove,maintenance',
            'temperature' => 'nullable|numeric|min:-50|max:100',
            'condition' => 'nullable|in:good,fair,poor',
            'refill_status' => 'nullable|in:full,half,empty,not_applicable',
            'notes' => 'nullable|string|max:1000',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'signature' => 'nullable|string',
            'photo_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'pic_name' => 'nullable|string',
            'signature_data' => 'nullable|string', // Base64 signature
            'completed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobScheduleId = $request->job_schedule_id;
            $job = JobSchedule::findOrFail($jobScheduleId);

            // Handle photo uploads if provided
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('job-reports/photos', 'public');
                    $photoPaths[] = $path;
                }
            }

            // Handle PIC photo upload if provided
            $picPhotoPath = null;
            if ($request->hasFile('photo_pic')) {
                $picPhotoPath = $request->file('photo_pic')->store('job-reports/pic-photos', 'public');
            }

            // Handle signature if provided (base64 or file)
            $signaturePath = null;
            if ($request->signature_data) {
                // Save base64 signature as image
                $signatureData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature_data));
                $signatureFilename = 'signature_' . $jobScheduleId . '_' . time() . '.png';
                $signaturePath = 'job-reports/signatures/' . $signatureFilename;
                Storage::disk('public')->put($signaturePath, $signatureData);
            } elseif ($request->signature) {
                // Legacy: if signature is a string path, use it directly
                $signaturePath = $request->signature;
            }

            // Create or update job report
            $jobReport = JobReport::updateOrCreate(
                ['job_schedule_id' => $jobScheduleId],
                [
                    'technician_id' => Auth::id(),
                    'job_type' => $request->job_type ?? $job->type,
                    'temperature' => $request->temperature,
                    'condition' => $request->condition,
                    'refill_status' => $request->refill_status,
                    'photos' => !empty($photoPaths) ? $photoPaths : ($request->photos ?? []),
                    'signature' => $signaturePath ?? $request->signature,
                    'signature_data' => $request->signature_data,
                    'photo_pic' => $picPhotoPath,
                    'pic_name' => $request->pic_name,
                    'notes' => $request->notes,
                    'completed_at' => $request->completed_at ? Carbon::parse($request->completed_at) : now(),
                    'signature_at' => $signaturePath ? now() : null,
                ]
            );

            \Log::info('Job report submitted from mobile', [
                'job_report_id' => $jobReport->id,
                'job_schedule_id' => $jobScheduleId,
                'technician_id' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Job report submitted successfully',
                'data' => $jobReport->load(['jobSchedule', 'technician'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error submitting job report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['photos', 'photo_pic', 'signature_data']),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error submitting job report: ' . $e->getMessage()
            ], 500);
        }
    }
}
