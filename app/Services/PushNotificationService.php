<?php

namespace App\Services;

use App\Models\User;
use App\Models\JobReport;
use App\Models\MaintenanceSchedule;
use App\Models\EmergencyLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    /**
     * Send push notification to user
     */
    public function sendNotification($userId, $title, $message, $data = [])
    {
        try {
            $user = User::find($userId);
            if (!$user || !$user->device_token) {
                return [
                    'status' => 'error',
                    'message' => 'User not found or device token not available'
                ];
            }

            $payload = [
                'to' => $user->device_token,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                    'sound' => 'default',
                    'badge' => 1
                ],
                'data' => array_merge($data, [
                    'timestamp' => now()->toISOString(),
                    'user_id' => $userId
                ])
            ];

            // Send to FCM (Firebase Cloud Messaging)
            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info('Push notification sent successfully', [
                    'user_id' => $userId,
                    'title' => $title,
                    'response' => $response->json()
                ]);

                return [
                    'status' => 'success',
                    'message' => 'Notification sent successfully',
                    'response' => $response->json()
                ];
            } else {
                Log::error('Push notification failed', [
                    'user_id' => $userId,
                    'response' => $response->body()
                ]);

                return [
                    'status' => 'error',
                    'message' => 'Failed to send notification',
                    'response' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Push notification error: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Notification error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send job assignment notification
     */
    public function sendJobAssignmentNotification($jobReportId)
    {
        $jobReport = JobReport::with(['assignedUser', 'customer', 'building'])->find($jobReportId);
        
        if (!$jobReport || !$jobReport->assignedUser) {
            return [
                'status' => 'error',
                'message' => 'Job report or assigned user not found'
            ];
        }

        $title = 'New Job Assignment';
        $buildingName = $jobReport->building ? $jobReport->building->building_name : 'N/A';
        $message = "You have been assigned a new job: {$jobReport->job_number} at {$buildingName}";
        
        $data = [
            'type' => 'job_assignment',
            'job_id' => $jobReport->id,
            'job_number' => $jobReport->job_number,
            'customer_name' => $jobReport->customer ? $jobReport->customer->name : 'N/A',
            'building_name' => $buildingName,
            'priority' => $jobReport->priority
        ];

        return $this->sendNotification(
            $jobReport->assigned_to,
            $title,
            $message,
            $data
        );
    }

    /**
     * Send maintenance schedule notification
     */
    public function sendMaintenanceNotification($scheduleId)
    {
        $schedule = MaintenanceSchedule::with(['assignedUser', 'building', 'room'])->find($scheduleId);
        
        if (!$schedule || !$schedule->assignedUser) {
            return [
                'status' => 'error',
                'message' => 'Maintenance schedule or assigned user not found'
            ];
        }

        $title = 'Maintenance Schedule Reminder';
        $buildingName = $schedule->building ? $schedule->building->building_name : 'N/A';
        $roomName = $schedule->room ? $schedule->room->room_name : 'N/A';
        $message = "You have a maintenance scheduled: {$schedule->title} at {$buildingName}";
        
        $data = [
            'type' => 'maintenance_schedule',
            'schedule_id' => $schedule->id,
            'title' => $schedule->title,
            'building_name' => $buildingName,
            'room_name' => $roomName,
            'priority' => $schedule->priority,
            'scheduled_date' => $schedule->scheduled_date->format('Y-m-d H:i:s')
        ];

        return $this->sendNotification(
            $schedule->assigned_to,
            $title,
            $message,
            $data
        );
    }

    /**
     * Send emergency notification
     */
    public function sendEmergencyNotification($emergencyLogId)
    {
        $emergencyLog = EmergencyLog::with(['user', 'assignedUser'])->find($emergencyLogId);
        
        if (!$emergencyLog) {
            return [
                'status' => 'error',
                'message' => 'Emergency log not found'
            ];
        }

        $title = 'Emergency Alert';
        $message = "Emergency reported: {$emergencyLog->emergency_type} - {$emergencyLog->description}";
        $reportedBy = $emergencyLog->user ? $emergencyLog->user->name : 'Unknown';
        
        $data = [
            'type' => 'emergency',
            'emergency_id' => $emergencyLog->id,
            'emergency_type' => $emergencyLog->emergency_type,
            'priority' => $emergencyLog->priority,
            'location' => $emergencyLog->location,
            'reported_by' => $reportedBy
        ];

        // Send to all active users with emergency contact role
        $emergencyContacts = User::whereHas('roles', function($query) {
            $query->where('name', 'emergency_contact');
        })->where('is_active', true)->get();

        $results = [];
        foreach ($emergencyContacts as $contact) {
            $result = $this->sendNotification(
                $contact->id,
                $title,
                $message,
                $data
            );
            $results[] = $result;
        }

        return [
            'status' => 'success',
            'message' => 'Emergency notifications sent',
            'results' => $results
        ];
    }

    /**
     * Send bulk notification to multiple users
     */
    public function sendBulkNotification($userIds, $title, $message, $data = [])
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            $result = $this->sendNotification($userId, $title, $message, $data);
            $results[] = [
                'user_id' => $userId,
                'result' => $result
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Bulk notifications sent',
            'results' => $results
        ];
    }

    /**
     * Send notification to users by role
     */
    public function sendNotificationByRole($roleName, $title, $message, $data = [])
    {
        $users = User::whereHas('roles', function($query) use ($roleName) {
            $query->where('name', $roleName);
        })->where('is_active', true)->get();

        $userIds = $users->pluck('id')->toArray();
        
        return $this->sendBulkNotification($userIds, $title, $message, $data);
    }

    /**
     * Send notification to users by branch
     */
    public function sendNotificationByBranch($branchId, $title, $message, $data = [])
    {
        $users = User::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

        $userIds = $users->pluck('id')->toArray();
        
        return $this->sendBulkNotification($userIds, $title, $message, $data);
    }
}
