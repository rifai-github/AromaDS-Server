<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\JobReport;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\MaintenanceSchedule;
use App\Models\EmergencyContact;
use App\Services\MobileSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MobileController extends Controller
{
    /**
     * Mobile API Authentication
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'device_id' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|in:android,ios'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('mobile-app')->plainTextToken;
            
            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'device_id' => $request->device_id,
                'device_type' => $request->device_type
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->roles->first()?->name ?? 'user',
                        'branch_id' => $user->branch_id,
                        'profile_photo' => $user->profile_photo
                    ],
                    'token' => $token,
                    'expires_at' => now()->addDays(30)->toISOString()
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Mobile API Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get Dashboard Data for Mobile
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get user's job reports
        $jobReports = JobReport::where('assigned_to', $user->id)
            ->with(['customer', 'building', 'room', 'rental'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get today's maintenance schedules
        $todaySchedules = MaintenanceSchedule::where('assigned_to', $user->id)
            ->whereDate('scheduled_date', today())
            ->where('status', '!=', 'completed')
            ->with(['unit', 'building', 'room'])
            ->get();

        // Get pending job reports
        $pendingJobs = JobReport::where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->count();

        // Get completed jobs this week
        $completedThisWeek = JobReport::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'user',
                    'branch_id' => $user->branch_id
                ],
                'statistics' => [
                    'pending_jobs' => $pendingJobs,
                    'completed_this_week' => $completedThisWeek,
                    'today_schedules' => $todaySchedules->count()
                ],
                'recent_jobs' => $jobReports->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'job_number' => $job->job_number,
                        'customer_name' => $job->customer->name ?? 'N/A',
                        'building_name' => $job->building->building_name ?? 'N/A',
                        'room_name' => $job->room->room_name ?? 'N/A',
                        'status' => $job->status,
                        'priority' => $job->priority,
                        'created_at' => $job->created_at->format('Y-m-d H:i:s')
                    ];
                }),
                'today_schedules' => $todaySchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'title' => $schedule->title,
                        'description' => $schedule->description,
                        'scheduled_date' => $schedule->scheduled_date->format('Y-m-d H:i:s'),
                        'priority' => $schedule->priority,
                        'status' => $schedule->status,
                        'building_name' => $schedule->building->building_name ?? 'N/A',
                        'room_name' => $schedule->room->room_name ?? 'N/A'
                    ];
                })
            ]
        ]);
    }

    /**
     * Get Job Reports for Mobile
     */
    public function jobReports(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status', 'all');
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);

        $query = JobReport::where('assigned_to', $user->id)
            ->with(['customer', 'building', 'room', 'rental', 'assignedUser']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $jobReports = $query->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $jobReports->map(function ($job) {
                return [
                    'id' => $job->id,
                    'job_number' => $job->job_number,
                    'customer' => [
                        'id' => $job->customer->id ?? null,
                        'name' => $job->customer->name ?? 'N/A',
                        'phone' => $job->customer->phone ?? 'N/A'
                    ],
                    'building' => [
                        'id' => $job->building->id ?? null,
                        'name' => $job->building->building_name ?? 'N/A',
                        'address' => $job->building->address ?? 'N/A'
                    ],
                    'room' => [
                        'id' => $job->room->id ?? null,
                        'name' => $job->room->room_name ?? 'N/A'
                    ],
                    'rental' => [
                        'id' => $job->rental->id ?? null,
                        'name' => $job->rental->rental_name ?? 'N/A'
                    ],
                    'status' => $job->status,
                    'priority' => $job->priority,
                    'description' => $job->description,
                    'notes' => $job->notes,
                    'created_at' => $job->created_at->format('Y-m-d H:i:s'),
                    'scheduled_date' => $job->scheduled_date ? $job->scheduled_date->format('Y-m-d H:i:s') : null,
                    'completed_at' => $job->completed_at ? $job->completed_at->format('Y-m-d H:i:s') : null
                ];
            })
        ]);
    }

    /**
     * Update Job Report Status
     */
    public function updateJobStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'completion_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $jobReport = JobReport::where('id', $id)
            ->where('assigned_to', $request->user()->id)
            ->first();

        if (!$jobReport) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job report not found or not assigned to you'
            ], 404);
        }

        $jobReport->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'completion_notes' => $request->completion_notes,
            'completed_at' => $request->status === 'completed' ? now() : null
        ]);

        // MOM15: Real-time Invoice Trigger for Mobile App
        if ($request->status === 'completed' && $jobReport->rental && $jobReport->rental->contract) {
             try {
                $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
                $invoiceService->attemptAutoInvoiceForContract($jobReport->rental->contract->id);
            } catch (\Exception $e) {
                \Log::error("Failed to trigger real-time invoice check from Mobile App: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Job status updated successfully',
            'data' => [
                'id' => $jobReport->id,
                'status' => $jobReport->status,
                'updated_at' => $jobReport->updated_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get Maintenance Schedules for Mobile
     */
    public function maintenanceSchedules(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->get('date', today()->format('Y-m-d'));
        $status = $request->get('status', 'all');

        $query = MaintenanceSchedule::where('assigned_to', $user->id)
            ->whereDate('scheduled_date', $date)
            ->with(['unit', 'building', 'room', 'assignedUser']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $schedules = $query->orderBy('scheduled_date', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->title,
                    'description' => $schedule->description,
                    'scheduled_date' => $schedule->scheduled_date->format('Y-m-d H:i:s'),
                    'priority' => $schedule->priority,
                    'status' => $schedule->status,
                    'building' => [
                        'id' => $schedule->building->id ?? null,
                        'name' => $schedule->building->building_name ?? 'N/A',
                        'address' => $schedule->building->address ?? 'N/A'
                    ],
                    'room' => [
                        'id' => $schedule->room->id ?? null,
                        'name' => $schedule->room->room_name ?? 'N/A'
                    ],
                    'unit' => [
                        'id' => $schedule->unit->id ?? null,
                        'name' => $schedule->unit->unit_name ?? 'N/A',
                        'serial_number' => $schedule->unit->serial_number ?? 'N/A'
                    ],
                    'created_at' => $schedule->created_at->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }

    /**
     * Update Maintenance Schedule Status
     */
    public function updateMaintenanceStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'completion_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule = MaintenanceSchedule::where('id', $id)
            ->where('assigned_to', $request->user()->id)
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maintenance schedule not found or not assigned to you'
            ], 404);
        }

        $schedule->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'completion_notes' => $request->completion_notes,
            'completed_at' => $request->status === 'completed' ? now() : null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Maintenance status updated successfully',
            'data' => [
                'id' => $schedule->id,
                'status' => $schedule->status,
                'updated_at' => $schedule->updated_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get Emergency Contacts for Mobile
     */
    public function emergencyContacts(Request $request): JsonResponse
    {
        $contacts = EmergencyContact::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $contacts->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                    'role' => $contact->role,
                    'priority' => $contact->priority,
                    'is_available' => $contact->is_available
                ];
            })
        ]);
    }

    /**
     * Create Emergency Log from Mobile
     */
    public function createEmergencyLog(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emergency_type' => 'required|string|max:100',
            'description' => 'required|string|max:1000',
            'location' => 'nullable|string|max:255',
            'priority' => 'required|string|in:low,medium,high,critical'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $emergencyLog = \App\Models\EmergencyLog::create([
            'user_id' => $request->user()->id,
            'emergency_type' => $request->emergency_type,
            'description' => $request->description,
            'location' => $request->location,
            'priority' => $request->priority,
            'status' => 'reported',
            'reported_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Emergency log created successfully',
            'data' => [
                'id' => $emergencyLog->id,
                'emergency_type' => $emergencyLog->emergency_type,
                'priority' => $emergencyLog->priority,
                'status' => $emergencyLog->status,
                'reported_at' => $emergencyLog->reported_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get User Profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->roles->first()?->name ?? 'user',
                'branch' => [
                    'id' => $user->branch->id ?? null,
                    'name' => $user->branch->branch_name ?? 'N/A'
                ],
                'profile_photo' => $user->profile_photo,
                'last_login_at' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : null,
                'created_at' => $user->created_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Update User Profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|string|min:6',
            'new_password' => 'nullable|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        // Update basic info
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone
        ]);

        // Update password if provided
        if ($request->new_password) {
            if (!$request->current_password || !Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Sync offline data
     */
    public function syncOfflineData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offline_data' => 'required|array',
            'last_sync' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $syncService = new MobileSyncService();
        $result = $syncService->syncOfflineData(
            $request->user()->id,
            $request->offline_data
        );

        return response()->json($result);
    }

    /**
     * Get data for offline sync
     */
    public function getOfflineData(Request $request): JsonResponse
    {
        $lastSync = $request->get('last_sync');
        
        $syncService = new MobileSyncService();
        $result = $syncService->getOfflineData(
            $request->user()->id,
            $lastSync
        );

        return response()->json($result);
    }
}
