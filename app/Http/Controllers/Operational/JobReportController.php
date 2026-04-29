<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\JobReport;
use App\Models\JobSchedule;
use App\Models\User;
use App\Services\QRCodeScannerService;
use App\Services\GPSLocationService;
use App\Services\PhotoUploadService;
use App\Services\DigitalSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JobReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = JobReport::with(['jobSchedule', 'technician']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('jobSchedule', function($jobQuery) use ($search) {
                    $jobQuery->where('job_number', 'like', "%{$search}%")
                             ->orWhere('company_name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'completed') {
                $query->completed();
            } elseif ($request->status === 'pending') {
                $query->pending();
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $jobReports = $query->orderBy('created_at', 'desc')->paginate(25);
        $technicians = User::where('is_active', true)->where('roles', 'technician')->get();
        $jobSchedules = JobSchedule::where('status', 'in_progress')->get();

        return view('operational.job-reports.index', compact('jobReports', 'technicians', 'jobSchedules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $jobSchedules = JobSchedule::where('status', 'in_progress')->get();
        $technicians = User::where('is_active', true)->where('roles', 'technician')->get();

        return view('operational.job-reports.create', compact('jobSchedules', 'technicians'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Determine technician_id based on request source
        $technicianId = null;
        if ($request->filled('technician_id')) {
            // Admin creating job report - technician_id provided
            $technicianId = $request->technician_id;
        } elseif (Auth::check() && Auth::user()->roles === 'technician') {
            // Technician creating via mobile app - auto-detect from login
            $technicianId = Auth::id();
        }

        $validator = Validator::make($request->all(), [
            'job_schedule_id' => 'required|exists:job_schedules,id',
            'technician_id' => $technicianId ? 'nullable' : 'required|exists:users,id',
            'job_type' => 'required|in:install,service,remove,maintenance',
            'temperature' => 'nullable|numeric|min:-50|max:100',
            'condition' => 'nullable|in:good,fair,poor',
            'refill_status' => 'nullable|in:full,half,empty,not_applicable',
            'notes' => 'nullable|string|max:1000',
            // GPS/Location fields
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:500',
            // Photo fields
            'photo_before' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'photo_after' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'photo_pic' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            // Digital Signature fields
            'signature_data' => 'nullable|string',
            'pic_name' => 'nullable|string|max:255',
            'pic_position' => 'nullable|string|max:255',
            // QR Code fields
            'unit_serial_number' => 'nullable|string|max:255',
            'unit_mac_address' => 'nullable|string|max:17',
            'material_qr_codes' => 'nullable|array',
            'material_qr_codes.*' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            
            // Set technician_id based on source
            $data['technician_id'] = $technicianId;
            
            // Initialize services
            $photoService = new PhotoUploadService();
            $signatureService = new DigitalSignatureService();
            $qrService = new QRCodeScannerService();
            $gpsService = new GPSLocationService();
            
            // Handle GPS/Location
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $data['location_updated_at'] = now();
                
                // Get address from coordinates if not provided
                if (!$request->filled('location_address')) {
                    $address = $gpsService->reverseGeocode($request->latitude, $request->longitude);
                    if ($address) {
                        $data['location_address'] = $address['full_address'];
                    }
                }
            }
            
            // Handle photo uploads
            if ($request->hasFile('photo_before')) {
                $result = $photoService->uploadPhoto($request->file('photo_before'), 'before');
                if ($result['success']) {
                    $data['photo_before'] = $result['filename'];
                }
            }
            
            if ($request->hasFile('photo_after')) {
                $result = $photoService->uploadPhoto($request->file('photo_after'), 'after');
                if ($result['success']) {
                    $data['photo_after'] = $result['filename'];
                }
            }
            
            if ($request->hasFile('photo_pic')) {
                $result = $photoService->uploadPhoto($request->file('photo_pic'), 'pic');
                if ($result['success']) {
                    $data['photo_pic'] = $result['filename'];
                }
            }
            
            // Handle digital signature
            if ($request->filled('signature_data')) {
                $result = $signatureService->saveSignature(
                    $request->signature_data,
                    $request->pic_name,
                    $request->pic_position
                );
                if ($result['success']) {
                    $data['signature_file'] = $result['filename'];
                    $data['signature_at'] = now();
                }
            }
            
            // Handle QR code validation
            if ($request->filled('unit_mac_address')) {
                $deviceData = $qrService->getFullDeviceData([$request->unit_mac_address]);
                if ($deviceData && isset($deviceData['data']['deviceList'][0])) {
                    $device = $deviceData['data']['deviceList'][0];
                    $data['device_snapshot'] = $device['deviceSnapshot'] ?? null;
                    $data['device_online_status'] = $device['deviceSnapshot']['online'] ?? null;
                    $data['device_liquid_level'] = $device['deviceSnapshot']['liquidLevel'] ?? null;
                    $data['device_fan_level'] = $device['deviceSnapshot']['fanLevel'] ?? null;
                    $data['qr_scan_at'] = now();
                }
            }
            
            // Set completion time if not provided
            if (!$request->filled('completed_at')) {
                $data['completed_at'] = now();
            }

            $jobReport = JobReport::create($data);

            // Update job schedule status if completed
            // STUDY CASE B1: Check if all rooms are completed before marking job schedule as completed
            if ($request->job_type === 'install' || $request->job_type === 'service') {
                $jobSchedule = JobSchedule::find($request->job_schedule_id);
                
                // STUDY CASE B1: Check if all rooms are completed
                if ($jobSchedule->areAllRoomsCompleted()) {
                    $updateData = [
                        'status' => 'completed',
                        'completed_at' => now(),
                        'work_status' => 'completed'
                    ];
                    
                    // MOM13: Auto-fill ba_date dan ba_number ketika status berubah ke completed
                    if (!$jobSchedule->ba_date) {
                        $updateData['ba_date'] = now()->toDateString();
                    }
                    if (!$jobSchedule->ba_number) {
                        $updateData['ba_number'] = $this->generateBANumber($jobSchedule);
                    }
                    
                    $jobSchedule->update($updateData);
                    \Log::info("✅ STUDY CASE B1: All rooms completed, JobSchedule {$jobSchedule->job_number} marked as completed");
                    
                    // MOM10 UPDATE: Check if install + first service completed, then auto-generate remaining services
                    $this->checkAndGenerateRemainingServices($jobSchedule);

                    // MOM: Check for remove job after service completion
                    if ($jobSchedule->type === 'service') {
                        $jobScheduleController = new \App\Http\Controllers\Operational\JobScheduleController();
                        $reflection = new \ReflectionClass($jobScheduleController);
                        $methodRemove = $reflection->getMethod('checkAndCreateRemoveJobAfterAllServicesComplete');
                        $methodRemove->setAccessible(true);
                        $methodRemove->invoke($jobScheduleController, $jobSchedule, $jobSchedule->jobAdvice);
                    }

                    // MOM: Check if Install Free completed, then auto-generate Remove Free
                    // "jika job install free maka job removenya harus remove free"
                     if ($jobSchedule->jobAdvice && strtolower($jobSchedule->jobAdvice->type ?? '') === 'install_free') {
                        $jobAdviceController = new \App\Http\Controllers\Marketing\JobAdviceController();
                        $jobAdviceController->generateRemoveFreeSchedule($jobSchedule->jobAdvice, $jobSchedule);
                    }

                    // MOM: Auto-generate Removal for Daily Rentals
                    if ($jobSchedule->jobAdvice) {
                        $jobAdviceController = new \App\Http\Controllers\Marketing\JobAdviceController();
                        $jobAdviceController->generateAutoRemoveDailySchedule($jobSchedule->jobAdvice, $jobSchedule);
                    }
                } else {
                    // Not all rooms completed, keep status as in_progress
                    if ($jobSchedule->status !== 'in_progress') {
                        $jobSchedule->update([
                            'status' => 'in_progress',
                            'work_status' => 'in_progress'
                        ]);
                    }
                    \Log::info("⚠️ STUDY CASE B1: Not all rooms completed, JobSchedule {$jobSchedule->job_number} status remains in_progress");
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Job report created successfully',
                'data' => $jobReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating job report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $jobReport = JobReport::with(['jobSchedule', 'technician', 'temperatureRecords', 'signatures'])
                             ->findOrFail($id);

        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $jobReport
            ]);
        }

        // For non-AJAX requests, redirect to index with error message
        return redirect()->route('operational.job-reports.index')
            ->with('error', 'Please use the modal system to view job reports.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $jobReport = JobReport::findOrFail($id);
        $jobSchedules = JobSchedule::where('status', 'in_progress')->get();
        $technicians = User::where('is_active', true)->where('roles', 'technician')->get();

        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'jobReport' => $jobReport,
                    'jobSchedules' => $jobSchedules,
                    'technicians' => $technicians
                ]
            ]);
        }

        // For non-AJAX requests, redirect to index with error message
        return redirect()->route('operational.job-reports.index')
            ->with('error', 'Please use the modal system to edit job reports.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $jobReport = JobReport::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'job_schedule_id' => 'required|exists:job_schedules,id',
            'technician_id' => 'required|exists:users,id',
            'job_type' => 'required|in:install,service,remove,maintenance',
            'temperature' => 'nullable|numeric|min:-50|max:100',
            'condition' => 'nullable|in:good,fair,poor',
            'refill_status' => 'nullable|in:full,half,empty,not_applicable',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'signature' => 'nullable|string',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            
            // Handle photo uploads
            if ($request->hasFile('photos')) {
                $photoPaths = [];
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('job-reports/photos', 'public');
                    $photoPaths[] = $path;
                }
                $data['photos'] = $photoPaths;
            }

            $jobReport->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Job report updated successfully',
                'data' => $jobReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating job report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $jobReport = JobReport::findOrFail($id);
            
            // Delete associated photos
            if ($jobReport->photos) {
                foreach ($jobReport->photos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            }
            
            $jobReport->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Job report deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting job report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete job reports.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:job_reports,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobReports = JobReport::whereIn('id', $request->ids)->get();
            
            foreach ($jobReports as $jobReport) {
                // Delete associated photos
                if ($jobReport->photos) {
                    foreach ($jobReport->photos as $photo) {
                        Storage::disk('public')->delete($photo);
                    }
                }
            }
            
            $count = JobReport::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$count} job report(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting job reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate QR code for mobile app
     */
    public function validateQRCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $qrService = new QRCodeScannerService();
            $result = $qrService->validateQRCode($request->qr_code);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error validating QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process mandatory QR scan for installation
     */
    public function processMandatoryQRScan(Request $request, JobReport $jobReport)
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string|max:255',
            'unit_serial_number' => 'nullable|string|max:255',
            'unit_mac_address' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if QR scan is required for this job type
            if (!$jobReport->requiresMandatoryQRScan()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR scan is not required for this job type'
                ], 400);
            }

            // Check if already completed
            if ($jobReport->isMandatoryQRScanCompleted()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR scan already completed for this job'
                ], 400);
            }

            // 1. Validate QR code with Cloud/Format Check
            $qrService = new QRCodeScannerService();
            $qrResult = $qrService->validateQRCode($request->qr_code);

            if (!$qrResult['valid']) {
                $jobReport->markQRScanFailed($qrResult['message'] ?? 'Invalid QR code');
                
                return response()->json([
                    'status' => 'error',
                    'message' => $qrResult['message'] ?? 'Invalid QR code',
                    'data' => $qrResult
                ], 400);
            }

            // 2. STUDY CASE B2: Context Validation (Check if SN is allowed for this Job)
            $jobSchedule = $jobReport->jobSchedule;
            if ($jobSchedule) {
                // Get scanned Serial Number (from QR result or request)
                $scannedSN = null;
                if (isset($qrResult['device']['serialNumber'])) {
                    $scannedSN = $qrResult['device']['serialNumber'];
                } elseif (isset($qrResult['device']['mac'])) {
                    // Start of Mac is not typically SN, but sometimes used interchangeably in QR content.
                    // Ideally we depend on what validateQRCode returns.
                    // Assuming validateQRCode returns 'device' object with 'serialNumber' if available.
                    
                    // Fallback: Checks if the QR content itself is the SN (simple format)
                    // or if request passed it explicitly
                }
                
                // If cloud didn't return SN (e.g. offline device), try to use the unit_serial_number from request if it matches QR logic
                if (empty($scannedSN) && $request->filled('unit_serial_number')) {
                    $scannedSN = $request->unit_serial_number;
                }
                
                // If we still don't have an SN, we can't context-validate easily unless we map MAC -> SN locally.
                // But for now, let's assume we can get SN or we validate based on what we have.
                // Let's rely on what validateQRCode returns or the raw QR if it's an SN.
                
                // For simplicity/robustness:
                // Extract SN from QR result if possible.
                // If the QR Code string *is* the SN (common in some systems), use that.
                if (empty($scannedSN)) {
                     // Try to see if QR code is just the SN
                     $scannedSN = $request->qr_code; 
                }

                $allowedSNs = $this->getAllowedSerialNumbers($jobSchedule);

                // Only enforce if we successfully retrieved a list of allowed SNs
                // If list is empty (e.g. data missing), we might choose to be lenient or strict.
                // User requirement implies strictness: "Kode SN aja beda... harusnya yg ini".
                
                if (!empty($allowedSNs)) {
                    // Check if scanned SN is in the allowed list
                    // Normalize for comparison (case insensitive)
                    $normalizedAllowed = array_map('strtoupper', $allowedSNs);
                    $normalizedScanned = strtoupper($scannedSN);
                    
                    if (!in_array($normalizedScanned, $normalizedAllowed)) {
                         // Fail validation
                         $expectedStr = implode(', ', $allowedSNs);
                         $msg = "Invalid Device for this Job. Scanned: {$scannedSN}. Expected one of: {$expectedStr}";
                         
                         $jobReport->markQRScanFailed($msg);
                         
                         return response()->json([
                            'status' => 'error',
                            'message' => $msg,
                            'debug_allowed' => $allowedSNs
                        ], 400);
                    }
                } else {
                    // List is empty. Warn but maybe allow? 
                    // Or Strict Block?
                    // For "Install", if list is empty, it means no Material Issue -> Dangerous to allow install?
                    // For "Remove", if list is empty (no unit on wall, no SN in job), maybe allow anything?
                    
                    if (in_array($jobSchedule->type, ['install', 'install_free'])) {
                         \Log::warning("JobReport QR Scan: No assigned materials found for Install Job {$jobSchedule->job_number}. Allowing scan but this is suspicious.");
                    }
                }
            }

            // Prepare QR scan data
            $qrData = [
                'qr_code' => $request->qr_code,
                'unit_serial_number' => $request->unit_serial_number,
                'unit_mac_address' => $request->unit_mac_address,
                'device_info' => $qrResult['device'] ?? null,
                'scan_timestamp' => now()->toISOString(),
                'technician_id' => auth()->id(),
                'validation_result' => $qrResult
            ];

            // Mark QR scan as completed
            $jobReport->markQRScanCompleted($qrData);

            // Update additional fields if provided
            $updateData = [];
            if ($request->unit_serial_number) {
                $updateData['unit_serial_number'] = $request->unit_serial_number;
            }
            if ($request->unit_mac_address) {
                $updateData['unit_mac_address'] = $request->unit_mac_address;
            }
            if (!empty($updateData)) {
                $jobReport->update($updateData);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'QR scan completed successfully',
                'data' => [
                    'job_report_id' => $jobReport->id,
                    'qr_scan_completed' => true,
                    'qr_scan_at' => $jobReport->mandatory_qr_scan_at,
                    'device_info' => $qrResult['device'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            $jobReport->markQRScanFailed('System error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error processing QR scan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to get allowed Serial Numbers for a Job Schedule
     * STUDY CASE B2
     */
    private function getAllowedSerialNumbers($jobSchedule)
    {
        $allowedSNs = [];
        $type = strtolower($jobSchedule->type ?? '');

        // LOGIC FOR REMOVE / MAINTAIN: Prioritize UnitOnWall
        if (in_array($type, ['remove', 'remove_free', 'maintenance', 'service'])) {
             // 1. Check Unit On Wall (Priority)
             // Check based on Room ID first
             if ($jobSchedule->room_id) {
                 $uow = \App\Models\UnitOnWall::where('building_id', $jobSchedule->building_id)
                     ->where('room_id', $jobSchedule->room_id)
                     ->where('status', 'active')
                     ->pluck('serial_number')
                     ->toArray();
                 if (!empty($uow)) {
                     return $uow; // Return immediately if found (Override any other source)
                 }
             }
             
             // If not found in Room, maybe check Building generic? (Risk of wrong room)
             // Let's stick to strict room check for now unless specified otherwise.
             
             // 2. Fallback: Check Job Schedule Serial Numbers (Material Issues/Legacy)
             // This is handled below in the general block
        }

        // GENERAL LOGIC (Install, or Fallback for Remove)
        // Fetch Serial Numbers from Material Issues (linked via JobAssignSchedule)
        
        $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($jobSchedule) {
            $q->where('job_schedule_id', $jobSchedule->id);
        })->get();

        if ($materialIssues->isNotEmpty()) {
            $materialIssueNumbers = $materialIssues->pluck('issue_number')->toArray();
            
            $snList = \App\Models\SerialNumber::whereHas('inventoryIssuingItems.inventoryIssuing', function($q) use ($materialIssueNumbers) {
                    $q->whereIn('reference_no', $materialIssueNumbers)
                      ->whereIn('status', ['processed', 'received', 'sent']);
                })
                ->whereNotNull('serial_number')
                ->pluck('serial_number')
                ->toArray();
                
            $allowedSNs = array_merge($allowedSNs, $snList);
        }
        
        return array_unique($allowedSNs);
    }

    /**
     * Get mandatory QR scan status for a job report
     */
    public function getMandatoryQRScanStatus(JobReport $jobReport)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'job_report_id' => $jobReport->id,
                    'mandatory_qr_scan_required' => $jobReport->mandatory_qr_scan_required,
                    'mandatory_qr_scan_completed' => $jobReport->mandatory_qr_scan_completed,
                    'qr_scan_validation_status' => $jobReport->qr_scan_validation_status,
                    'qr_scan_validation_message' => $jobReport->qr_scan_validation_message,
                    'mandatory_qr_scan_at' => $jobReport->mandatory_qr_scan_at,
                    'can_proceed' => $jobReport->isMandatoryQRScanCompleted() || !$jobReport->requiresMandatoryQRScan()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting QR scan status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current location for mobile app
     */
    public function getCurrentLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $gpsService = new GPSLocationService();
            $address = $gpsService->reverseGeocode($request->latitude, $request->longitude);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'address' => $address,
                    'timestamp' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload photos for mobile app
     */
    public function uploadPhotos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photos' => 'required|array|min:1|max:3',
            'photos.*' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'type' => 'required|in:before,after,pic'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $photoService = new PhotoUploadService();
            $results = [];

            foreach ($request->file('photos') as $photo) {
                $result = $photoService->uploadPhoto($photo, $request->type);
                $results[] = $result;
            }

            return response()->json([
                'status' => 'success',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error uploading photos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save digital signature for mobile app
     */
    public function saveSignature(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'signature_data' => 'required|string',
            'pic_name' => 'nullable|string|max:255',
            'pic_position' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $signatureService = new DigitalSignatureService();
            $result = $signatureService->saveSignature(
                $request->signature_data,
                $request->pic_name,
                $request->pic_position
            );

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error saving signature: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MOM10 UPDATE: Check if install + first service completed, then auto-generate remaining services
     * 
     * @param JobSchedule $completedJobSchedule
     * @return void
     */
    private function checkAndGenerateRemainingServices($completedJobSchedule)
    {
        try {
            // Only process for install or first-service completion
            if (!in_array($completedJobSchedule->type, ['install', 'service', 'service_first'])) {
                return;
            }
            
            // Get job advice with rooms
            $jobAdvice = $completedJobSchedule->jobAdvice;
            if (!$jobAdvice) {
                \Log::warning("No job advice found for job schedule {$completedJobSchedule->job_number}");
                return;
            }
            
            // Only process for "install" type job advice (not install_free)
            $jobAdviceType = strtolower($jobAdvice->type ?? '');
            if ($jobAdviceType !== 'install') {
                \Log::info("Job advice type is '{$jobAdviceType}', not 'install'. Skipping remaining service generation.");
                return;
            }
            
            // Load job advice with necessary relationships
            $jobAdvice->load([
                'rooms.contractRoom.room.building',
                'contract.quotation.survey.building'
            ]);
            
            // Process each room in job advice
            foreach ($jobAdvice->rooms as $jaRoom) {
                // Check if this room's install and first service are both completed
                $installCompleted = false;
                $firstServiceCompleted = false;
                
                // Check install job status
                if ($jaRoom->install_job_schedule_id) {
                    $installJob = \App\Models\JobSchedule::find($jaRoom->install_job_schedule_id);
                    $installRoomCompleted = $installJob && \App\Models\JobScheduleRoom::where('job_schedule_id', $installJob->id)
                        ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                        ->where(function ($query) use ($jaRoom) {
                            $query->where('job_advice_room_id', $jaRoom->id)
                                ->orWhereHas('rentals', function ($rentalQuery) use ($jaRoom) {
                                    $rentalQuery->where('job_advice_room_id', $jaRoom->id);
                                });
                        })
                        ->exists();

                    if ($installJob && in_array($installJob->status, ['completed', 'done_job']) && $installRoomCompleted) {
                        $installCompleted = true;
                    }
                }
                
                // Check first service job status (period = 1)
                if ($jaRoom->service_job_schedule_id) {
                    $firstServiceJob = \App\Models\JobSchedule::find($jaRoom->service_job_schedule_id);
                    $firstServiceRoomCompleted = $firstServiceJob && \App\Models\JobScheduleRoom::where('job_schedule_id', $firstServiceJob->id)
                        ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                        ->where(function ($query) use ($jaRoom) {
                            $query->where('job_advice_room_id', $jaRoom->id)
                                ->orWhereHas('rentals', function ($rentalQuery) use ($jaRoom) {
                                    $rentalQuery->where('job_advice_room_id', $jaRoom->id);
                                });
                        })
                        ->exists();

                    if ($firstServiceJob && in_array($firstServiceJob->status, ['completed', 'done_job']) && $firstServiceJob->period == 1 && $firstServiceRoomCompleted) {
                        $firstServiceCompleted = true;
                    }
                }
                
                // If both install and first service completed, generate remaining services
                if ($installCompleted && $firstServiceCompleted) {
                    // Check if remaining services already generated
                    $existingServicesCount = \App\Models\JobSchedule::whereHas('jobAdvice.rooms', function($q) use ($jaRoom) {
                            $q->where('id', $jaRoom->id);
                        })
                        ->whereIn('type', ['service', 'service_first', 'service_routine'])
                        ->count();
                    
                    // If only 1 service exists (the first one), generate remaining
                    if ($existingServicesCount <= 1) {
                        \Log::info("🔧 Install + First Service completed for room {$jaRoom->room_name}. Generating remaining services...");
                        
                        // Get building
                        $building = null;
                        if ($jobAdvice->contract && $jobAdvice->contract->quotation && $jobAdvice->contract->quotation->survey) {
                            $building = $jobAdvice->contract->quotation->survey->building;
                        }
                        
                        if (!$building && $jaRoom->contractRoom && $jaRoom->contractRoom->room) {
                            $building = $jaRoom->contractRoom->room->building;
                        }
                        
                        if ($building) {
                            // Call JobAdviceController method to generate remaining services
                            $jobAdviceController = new \App\Http\Controllers\Marketing\JobAdviceController();
                            $reflection = new \ReflectionClass($jobAdviceController);
                            $method = $reflection->getMethod('generateRemainingServiceSchedules');
                            $method->setAccessible(true);
                            
                            $remainingServices = $method->invoke($jobAdviceController, $jobAdvice, $jaRoom, $building);
                            
                            \Log::info("✅ Generated {$remainingServices->count()} remaining service schedules for room {$jaRoom->room_name}");
                        } else {
                            \Log::warning("⚠️ No building found for room {$jaRoom->room_name}. Cannot generate remaining services.");
                        }
                    } else {
                        \Log::info("Remaining services already generated for room {$jaRoom->room_name} (found {$existingServicesCount} services)");
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error("❌ Failed to check and generate remaining services: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            // Don't throw exception, just log it
        }
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
}
