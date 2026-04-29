<?php

namespace App\Services\Reports;

use App\Models\ReportAlert;
use App\Models\AlertNotification;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportAlertService
{
    /**
     * Create a new report alert
     */
    public function createAlert(array $data): ReportAlert
    {
        return DB::transaction(function () use ($data) {
            $alert = ReportAlert::create([
                'name' => $data['name'],
                'report_id' => $data['report_id'],
                'condition_field' => $data['condition_field'],
                'condition_operator' => $data['condition_operator'],
                'condition_value' => $data['condition_value'],
                'notification_type' => $data['notification_type'],
                'recipients' => $data['recipients'],
                'message_template' => $data['message_template'] ?? null,
                'schedule' => $data['schedule'] ?? 'realtime',
                'is_active' => $data['is_active'] ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return $alert;
        });
    }

    /**
     * Update report alert
     */
    public function updateAlert(ReportAlert $alert, array $data): ReportAlert
    {
        $alert->update([
            'name' => $data['name'],
            'report_id' => $data['report_id'],
            'condition_field' => $data['condition_field'],
            'condition_operator' => $data['condition_operator'],
            'condition_value' => $data['condition_value'],
            'notification_type' => $data['notification_type'],
            'recipients' => $data['recipients'],
            'message_template' => $data['message_template'] ?? $alert->message_template,
            'schedule' => $data['schedule'] ?? $alert->schedule,
            'is_active' => $data['is_active'] ?? $alert->is_active,
            'updated_by' => Auth::id()
        ]);

        return $alert;
    }

    /**
     * Test alert condition
     */
    public function testAlert(ReportAlert $alert): array
    {
        try {
            $report = $alert->report;
            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }

            // Execute report query
            $data = $this->executeReportQuery($report);
            
            // Check condition
            $conditionMet = $this->checkCondition($alert, $data);
            
            return [
                'success' => true,
                'message' => $conditionMet ? 'Condition is met' : 'Condition is not met',
                'data' => [
                    'condition_met' => $conditionMet,
                    'current_value' => $this->getCurrentValue($alert, $data),
                    'condition' => "{$alert->condition_field} {$alert->condition_operator} {$alert->condition_value}",
                    'sample_data' => array_slice($data, 0, 5) // First 5 rows for preview
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Execute report query
     */
    private function executeReportQuery(Report $report): array
    {
        $query = $report->query;
        
        // Replace common parameters
        $query = str_replace(':date', "'" . now()->toDateString() . "'", $query);
        $query = str_replace(':start_of_month', "'" . now()->startOfMonth()->toDateString() . "'", $query);
        $query = str_replace(':end_of_month', "'" . now()->endOfMonth()->toDateString() . "'", $query);

        return DB::select($query);
    }

    /**
     * Check alert condition
     */
    private function checkCondition(ReportAlert $alert, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $currentValue = $this->getCurrentValue($alert, $data);
        $targetValue = $alert->condition_value;

        switch ($alert->condition_operator) {
            case '>':
                return $currentValue > $targetValue;
            case '<':
                return $currentValue < $targetValue;
            case '=':
                return $currentValue == $targetValue;
            case '!=':
                return $currentValue != $targetValue;
            case '>=':
                return $currentValue >= $targetValue;
            case '<=':
                return $currentValue <= $targetValue;
            default:
                return false;
        }
    }

    /**
     * Get current value from data
     */
    private function getCurrentValue(ReportAlert $alert, array $data): mixed
    {
        if (empty($data)) {
            return null;
        }

        $row = (array) $data[0];
        return $row[$alert->condition_field] ?? null;
    }

    /**
     * Process all active alerts
     */
    public function processAlerts(): array
    {
        $alerts = ReportAlert::where('is_active', true)
                            ->with('report')
                            ->get();

        $results = [];
        
        foreach ($alerts as $alert) {
            try {
                $result = $this->processAlert($alert);
                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = [
                    'alert_id' => $alert->id,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                
                Log::error("Alert processing failed for alert {$alert->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Process individual alert
     */
    public function processAlert(ReportAlert $alert): array
    {
        // Check if alert should be processed based on schedule
        if (!$this->shouldProcessAlert($alert)) {
            return [
                'alert_id' => $alert->id,
                'status' => 'skipped',
                'message' => 'Alert not due for processing'
            ];
        }

        // Execute report query
        $data = $this->executeReportQuery($alert->report);
        
        // Check condition
        $conditionMet = $this->checkCondition($alert, $data);
        
        if ($conditionMet) {
            // Send notification
            $this->sendNotification($alert, $data);
            
            // Update last triggered
            $alert->update(['last_triggered_at' => now()]);
            
            return [
                'alert_id' => $alert->id,
                'status' => 'triggered',
                'message' => 'Alert condition met, notification sent'
            ];
        }

        return [
            'alert_id' => $alert->id,
            'status' => 'no_action',
            'message' => 'Alert condition not met'
        ];
    }

    /**
     * Check if alert should be processed
     */
    private function shouldProcessAlert(ReportAlert $alert): bool
    {
        switch ($alert->schedule) {
            case 'realtime':
                return true;
            case 'daily':
                return !$alert->last_triggered_at || 
                       $alert->last_triggered_at->lt(now()->startOfDay());
            case 'weekly':
                return !$alert->last_triggered_at || 
                       $alert->last_triggered_at->lt(now()->startOfWeek());
            case 'monthly':
                return !$alert->last_triggered_at || 
                       $alert->last_triggered_at->lt(now()->startOfMonth());
            default:
                return true;
        }
    }

    /**
     * Send notification
     */
    private function sendNotification(ReportAlert $alert, array $data): void
    {
        $recipients = $this->parseRecipients($alert->recipients);
        $message = $this->buildMessage($alert, $data);

        foreach ($recipients as $recipient) {
            try {
                switch ($alert->notification_type) {
                    case 'email':
                        $this->sendEmailNotification($recipient, $alert, $message);
                        break;
                    case 'sms':
                        $this->sendSmsNotification($recipient, $alert, $message);
                        break;
                    case 'push':
                        $this->sendPushNotification($recipient, $alert, $message);
                        break;
                }

                // Log notification
                AlertNotification::create([
                    'alert_id' => $alert->id,
                    'recipient' => $recipient,
                    'notification_type' => $alert->notification_type,
                    'message' => $message,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'created_by' => Auth::id()
                ]);

            } catch (\Exception $e) {
                // Log failed notification
                AlertNotification::create([
                    'alert_id' => $alert->id,
                    'recipient' => $recipient,
                    'notification_type' => $alert->notification_type,
                    'message' => $message,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_at' => now(),
                    'created_by' => Auth::id()
                ]);

                Log::error("Notification failed for alert {$alert->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Parse recipients string
     */
    private function parseRecipients(string $recipients): array
    {
        return array_map('trim', explode(',', $recipients));
    }

    /**
     * Build notification message
     */
    private function buildMessage(ReportAlert $alert, array $data): string
    {
        if ($alert->message_template) {
            $message = $alert->message_template;
        } else {
            $message = "Alert: {$alert->name}\n\n";
            $message .= "Condition: {$alert->condition_field} {$alert->condition_operator} {$alert->condition_value}\n";
            $message .= "Report: {$alert->report->name}\n";
            $message .= "Triggered at: " . now()->format('Y-m-d H:i:s');
        }

        // Replace placeholders
        $currentValue = $this->getCurrentValue($alert, $data);
        $message = str_replace('{current_value}', $currentValue, $message);
        $message = str_replace('{condition_field}', $alert->condition_field, $message);
        $message = str_replace('{condition_operator}', $alert->condition_operator, $message);
        $message = str_replace('{condition_value}', $alert->condition_value, $message);
        $message = str_replace('{report_name}', $alert->report->name, $message);
        $message = str_replace('{alert_name}', $alert->name, $message);
        $message = str_replace('{triggered_at}', now()->format('Y-m-d H:i:s'), $message);

        return $message;
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(string $email, ReportAlert $alert, string $message): void
    {
        // This would integrate with your email service
        // For now, just log the email
        Log::info("Email notification sent to {$email} for alert {$alert->id}");
    }

    /**
     * Send SMS notification
     */
    private function sendSmsNotification(string $phone, ReportAlert $alert, string $message): void
    {
        // This would integrate with your SMS service
        // For now, just log the SMS
        Log::info("SMS notification sent to {$phone} for alert {$alert->id}");
    }

    /**
     * Send push notification
     */
    private function sendPushNotification(string $userId, ReportAlert $alert, string $message): void
    {
        // This would integrate with your push notification service
        // For now, just log the push notification
        Log::info("Push notification sent to user {$userId} for alert {$alert->id}");
    }

    /**
     * Get alert statistics
     */
    public function getAlertStatistics(): array
    {
        return [
            'total_alerts' => ReportAlert::count(),
            'active_alerts' => ReportAlert::where('is_active', true)->count(),
            'inactive_alerts' => ReportAlert::where('is_active', false)->count(),
            'alerts_by_type' => ReportAlert::selectRaw('notification_type, COUNT(*) as count')
                                          ->groupBy('notification_type')
                                          ->pluck('count', 'notification_type')
                                          ->toArray(),
            'alerts_by_schedule' => ReportAlert::selectRaw('schedule, COUNT(*) as count')
                                              ->groupBy('schedule')
                                              ->pluck('count', 'schedule')
                                              ->toArray(),
            'total_notifications' => AlertNotification::count(),
            'sent_notifications' => AlertNotification::where('status', 'sent')->count(),
            'failed_notifications' => AlertNotification::where('status', 'failed')->count(),
        ];
    }

    /**
     * Get alert notifications
     */
    public function getAlertNotifications(int $alertId = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = AlertNotification::with('alert');
        
        if ($alertId) {
            $query->where('alert_id', $alertId);
        }

        return $query->orderBy('sent_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return AlertNotification::where('sent_at', '<', $cutoffDate)->delete();
    }
}
