<?php

namespace App\Services;

use App\Models\JobReport;
use App\Models\MaintenanceSchedule;
use App\Models\EmergencyLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MobileSyncService
{
    /**
     * Sync offline data to server
     */
    public function syncOfflineData($userId, $offlineData)
    {
        try {
            DB::beginTransaction();
            
            $syncedData = [];
            
            // Sync job reports
            if (isset($offlineData['job_reports'])) {
                $syncedData['job_reports'] = $this->syncJobReports($userId, $offlineData['job_reports']);
            }
            
            // Sync maintenance schedules
            if (isset($offlineData['maintenance_schedules'])) {
                $syncedData['maintenance_schedules'] = $this->syncMaintenanceSchedules($userId, $offlineData['maintenance_schedules']);
            }
            
            // Sync emergency logs
            if (isset($offlineData['emergency_logs'])) {
                $syncedData['emergency_logs'] = $this->syncEmergencyLogs($userId, $offlineData['emergency_logs']);
            }
            
            DB::commit();
            
            return [
                'status' => 'success',
                'message' => 'Data synced successfully',
                'synced_data' => $syncedData,
                'sync_timestamp' => now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mobile sync error: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Sync failed: ' . $e->getMessage(),
                'sync_timestamp' => now()->toISOString()
            ];
        }
    }
    
    /**
     * Sync job reports
     */
    private function syncJobReports($userId, $jobReports)
    {
        $synced = [];
        
        foreach ($jobReports as $jobData) {
            try {
                $jobReport = JobReport::where('id', $jobData['id'])
                    ->where('assigned_to', $userId)
                    ->first();
                
                if ($jobReport) {
                    // Update existing job report
                    $jobReport->update([
                        'status' => $jobData['status'],
                        'notes' => $jobData['notes'] ?? $jobReport->notes,
                        'completion_notes' => $jobData['completion_notes'] ?? $jobReport->completion_notes,
                        'completed_at' => $jobData['status'] === 'completed' ? now() : $jobReport->completed_at,
                        'updated_at' => now()
                    ]);
                    
                    $synced[] = [
                        'id' => $jobReport->id,
                        'status' => 'updated',
                        'updated_at' => $jobReport->updated_at->toISOString()
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Job report sync error: ' . $e->getMessage());
                $synced[] = [
                    'id' => $jobData['id'],
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $synced;
    }
    
    /**
     * Sync maintenance schedules
     */
    private function syncMaintenanceSchedules($userId, $schedules)
    {
        $synced = [];
        
        foreach ($schedules as $scheduleData) {
            try {
                $schedule = MaintenanceSchedule::where('id', $scheduleData['id'])
                    ->where('assigned_to', $userId)
                    ->first();
                
                if ($schedule) {
                    // Update existing schedule
                    $schedule->update([
                        'status' => $scheduleData['status'],
                        'notes' => $scheduleData['notes'] ?? $schedule->notes,
                        'completion_notes' => $scheduleData['completion_notes'] ?? $schedule->completion_notes,
                        'completed_at' => $scheduleData['status'] === 'completed' ? now() : $schedule->completed_at,
                        'updated_at' => now()
                    ]);
                    
                    $synced[] = [
                        'id' => $schedule->id,
                        'status' => 'updated',
                        'updated_at' => $schedule->updated_at->toISOString()
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Maintenance schedule sync error: ' . $e->getMessage());
                $synced[] = [
                    'id' => $scheduleData['id'],
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $synced;
    }
    
    /**
     * Sync emergency logs
     */
    private function syncEmergencyLogs($userId, $emergencyLogs)
    {
        $synced = [];
        
        foreach ($emergencyLogs as $logData) {
            try {
                // Create new emergency log
                $emergencyLog = EmergencyLog::create([
                    'user_id' => $userId,
                    'emergency_type' => $logData['emergency_type'],
                    'description' => $logData['description'],
                    'location' => $logData['location'] ?? null,
                    'priority' => $logData['priority'],
                    'status' => 'reported',
                    'reported_at' => $logData['reported_at'] ?? now(),
                    'created_at' => $logData['created_at'] ?? now()
                ]);
                
                $synced[] = [
                    'id' => $emergencyLog->id,
                    'status' => 'created',
                    'created_at' => $emergencyLog->created_at->toISOString()
                ];
            } catch (\Exception $e) {
                Log::error('Emergency log sync error: ' . $e->getMessage());
                $synced[] = [
                    'id' => $logData['id'] ?? 'unknown',
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $synced;
    }
    
    /**
     * Get data for offline sync
     */
    public function getOfflineData($userId, $lastSync = null)
    {
        $user = User::find($userId);
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'User not found'
            ];
        }
        
        $query = JobReport::where('assigned_to', $userId)
            ->with(['customer', 'building', 'room', 'rental']);
            
        if ($lastSync) {
            $query->where('updated_at', '>', Carbon::parse($lastSync));
        }
        
        $jobReports = $query->get();
        
        $maintenanceQuery = MaintenanceSchedule::where('assigned_to', $userId)
            ->with(['unit', 'building', 'room']);
            
        if ($lastSync) {
            $maintenanceQuery->where('updated_at', '>', Carbon::parse($lastSync));
        }
        
        $maintenanceSchedules = $maintenanceQuery->get();
        
        return [
            'status' => 'success',
            'data' => [
                'job_reports' => $jobReports->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'job_number' => $job->job_number,
                        'customer_name' => $job->customer->name ?? 'N/A',
                        'building_name' => $job->building->building_name ?? 'N/A',
                        'room_name' => $job->room->room_name ?? 'N/A',
                        'status' => $job->status,
                        'priority' => $job->priority,
                        'description' => $job->description,
                        'notes' => $job->notes,
                        'scheduled_date' => $job->scheduled_date ? $job->scheduled_date->format('Y-m-d H:i:s') : null,
                        'created_at' => $job->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $job->updated_at->format('Y-m-d H:i:s')
                    ];
                }),
                'maintenance_schedules' => $maintenanceSchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'title' => $schedule->title,
                        'description' => $schedule->description,
                        'scheduled_date' => $schedule->scheduled_date->format('Y-m-d H:i:s'),
                        'priority' => $schedule->priority,
                        'status' => $schedule->status,
                        'building_name' => $schedule->building->building_name ?? 'N/A',
                        'room_name' => $schedule->room->room_name ?? 'N/A',
                        'created_at' => $schedule->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $schedule->updated_at->format('Y-m-d H:i:s')
                    ];
                })
            ],
            'sync_timestamp' => now()->toISOString()
        ];
    }
}
