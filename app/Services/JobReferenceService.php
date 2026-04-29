<?php

namespace App\Services;

use App\Models\JobSchedule;
use App\Models\JobReport;
use App\Models\Contract;
use App\Models\Building;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JobReferenceService
{
    /**
     * Generate job reference number for service
     */
    public function generateJobReferenceNumber(string $jobType = 'service', int $contractId = null, int $buildingId = null): array
    {
        try {
            $prefix = $this->getJobTypePrefix($jobType);
            $date = now()->format('Ymd');
            $sequence = $this->getNextSequenceNumber($jobType, $date);
            
            $jobReference = $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            
            // Validate uniqueness
            if ($this->isJobReferenceExists($jobReference)) {
                return $this->generateJobReferenceNumber($jobType, $contractId, $buildingId);
            }

            return [
                'success' => true,
                'data' => [
                    'job_reference' => $jobReference,
                    'job_type' => $jobType,
                    'generated_at' => now()->toISOString(),
                    'contract_id' => $contractId,
                    'building_id' => $buildingId
                ],
                'message' => 'Job reference number generated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Job reference generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate job reference: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Assign job reference to job schedule
     */
    public function assignJobReference(int $jobScheduleId, string $jobReference = null): array
    {
        try {
            $jobSchedule = JobSchedule::findOrFail($jobScheduleId);
            
            if (!$jobReference) {
                $result = $this->generateJobReferenceNumber('service', $jobSchedule->contract_id, $jobSchedule->building_id);
                if (!$result['success']) {
                    return $result;
                }
                $jobReference = $result['data']['job_reference'];
            }

            // Validate job reference format
            if (!$this->validateJobReferenceFormat($jobReference)) {
                return [
                    'success' => false,
                    'message' => 'Invalid job reference format'
                ];
            }

            // Check if job reference already exists
            if ($this->isJobReferenceExists($jobReference)) {
                return [
                    'success' => false,
                    'message' => 'Job reference already exists'
                ];
            }

            // Update job schedule with job reference
            $jobSchedule->update([
                'reference_number' => $jobReference,
                'updated_by' => auth()->id()
            ]);

            return [
                'success' => true,
                'data' => [
                    'job_schedule' => $jobSchedule,
                    'job_reference' => $jobReference
                ],
                'message' => 'Job reference assigned successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Job reference assignment failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to assign job reference: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get job reference details
     */
    public function getJobReferenceDetails(string $jobReference): array
    {
        try {
            $jobSchedule = JobSchedule::where('reference_number', $jobReference)
                ->with(['building', 'contract', 'assignedTechnician', 'jobReports'])
                ->first();

            if (!$jobSchedule) {
                return [
                    'success' => false,
                    'message' => 'Job reference not found'
                ];
            }

            $details = [
                'job_reference' => $jobSchedule->reference_number,
                'job_number' => $jobSchedule->job_number,
                'job_type' => $jobSchedule->type,
                'status' => $jobSchedule->status,
                'schedule_date' => $jobSchedule->schedule_date?->format('Y-m-d'),
                'building' => [
                    'id' => $jobSchedule->building_id,
                    'name' => $jobSchedule->building_name,
                    'company' => $jobSchedule->company_name
                ],
                'contract' => [
                    'id' => $jobSchedule->contract_id,
                    'contract_number' => $jobSchedule->contract_number
                ],
                'technician' => $jobSchedule->assignedTechnician ? [
                    'id' => $jobSchedule->assignedTechnician->id,
                    'name' => $jobSchedule->assignedTechnician->name
                ] : null,
                'reports_count' => $jobSchedule->jobReports->count(),
                'created_at' => $jobSchedule->created_at?->toISOString(),
                'updated_at' => $jobSchedule->updated_at?->toISOString()
            ];

            return [
                'success' => true,
                'data' => $details,
                'message' => 'Job reference details retrieved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Get job reference details failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get job reference details: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Search job references
     */
    public function searchJobReferences(array $filters = []): array
    {
        try {
            $query = JobSchedule::with(['building', 'contract', 'assignedTechnician'])
                ->whereNotNull('reference_number');

            // Apply filters
            if (isset($filters['job_reference'])) {
                $query->where('reference_number', 'like', "%{$filters['job_reference']}%");
            }

            if (isset($filters['job_type'])) {
                $query->where('type', $filters['job_type']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['building_id'])) {
                $query->where('building_id', $filters['building_id']);
            }

            if (isset($filters['contract_id'])) {
                $query->where('contract_id', $filters['contract_id']);
            }

            if (isset($filters['technician_id'])) {
                $query->where('assigned_technician_id', $filters['technician_id']);
            }

            if (isset($filters['date_from'])) {
                $query->where('schedule_date', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('schedule_date', '<=', $filters['date_to']);
            }

            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            $jobSchedules = $query->orderBy('created_at', 'desc')->get();

            $results = $jobSchedules->map(function($jobSchedule) {
                return [
                    'id' => $jobSchedule->id,
                    'job_reference' => $jobSchedule->reference_number,
                    'job_number' => $jobSchedule->job_number,
                    'job_type' => $jobSchedule->type,
                    'status' => $jobSchedule->status,
                    'schedule_date' => $jobSchedule->schedule_date?->format('Y-m-d'),
                    'building' => [
                        'id' => $jobSchedule->building_id,
                        'name' => $jobSchedule->building_name
                    ],
                    'contract' => [
                        'id' => $jobSchedule->contract_id,
                        'contract_number' => $jobSchedule->contract_number
                    ],
                    'technician' => $jobSchedule->assignedTechnician ? [
                        'id' => $jobSchedule->assignedTechnician->id,
                        'name' => $jobSchedule->assignedTechnician->name
                    ] : null
                ];
            });

            return [
                'success' => true,
                'data' => $results,
                'count' => $results->count(),
                'message' => 'Job references search completed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Search job references failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to search job references: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get job reference statistics
     */
    public function getJobReferenceStatistics(array $filters = []): array
    {
        try {
            $query = JobSchedule::whereNotNull('reference_number');

            // Apply date filters
            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $stats = [
                'total_job_references' => $query->count(),
                'by_job_type' => $query->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'by_status' => $query->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'by_month' => $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->pluck('count', 'month')
                    ->toArray(),
                'recent_references' => $query->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->pluck('reference_number')
                    ->toArray()
            ];

            return [
                'success' => true,
                'data' => $stats,
                'message' => 'Job reference statistics retrieved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Get job reference statistics failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get job reference statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate job reference format
     */
    public function validateJobReferenceFormat(string $jobReference): bool
    {
        // Format: PREFIX + YYYYMMDD + 4-digit sequence
        // Example: SRV202509290001, INST202509290001
        $pattern = '/^(SRV|INST|MAINT|REP|CHK)\d{8}\d{4}$/';
        return preg_match($pattern, $jobReference) === 1;
    }

    /**
     * Check if job reference exists
     */
    public function isJobReferenceExists(string $jobReference): bool
    {
        return JobSchedule::where('reference_number', $jobReference)->exists();
    }

    /**
     * Get job type prefix
     */
    private function getJobTypePrefix(string $jobType): string
    {
        $prefixes = [
            'service' => 'SRV',
            'install' => 'INST',
            'maintenance' => 'MAINT',
            'repair' => 'REP',
            'check' => 'CHK'
        ];

        return $prefixes[$jobType] ?? 'SRV';
    }

    /**
     * Get next sequence number for job type and date
     */
    private function getNextSequenceNumber(string $jobType, string $date): int
    {
        $prefix = $this->getJobTypePrefix($jobType);
        $datePrefix = $prefix . $date;

        $lastJob = JobSchedule::where('reference_number', 'like', $datePrefix . '%')
            ->orderBy('reference_number', 'desc')
            ->first();

        if (!$lastJob) {
            return 1;
        }

        $lastSequence = (int) substr($lastJob->reference_number, -4);
        return $lastSequence + 1;
    }

    /**
     * Bulk generate job references
     */
    public function bulkGenerateJobReferences(array $jobScheduleIds): array
    {
        try {
            $results = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($jobScheduleIds as $jobScheduleId) {
                try {
                    $result = $this->assignJobReference($jobScheduleId);
                    
                    if ($result['success']) {
                        $results[] = [
                            'job_schedule_id' => $jobScheduleId,
                            'job_reference' => $result['data']['job_reference'],
                            'status' => 'success'
                        ];
                    } else {
                        $errors[] = [
                            'job_schedule_id' => $jobScheduleId,
                            'error' => $result['message']
                        ];
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'job_schedule_id' => $jobScheduleId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            if (empty($errors)) {
                DB::commit();
                return [
                    'success' => true,
                    'data' => [
                        'generated_references' => $results,
                        'total_generated' => count($results)
                    ],
                    'message' => 'Job references generated successfully'
                ];
            } else {
                DB::rollBack();
                return [
                    'success' => false,
                    'data' => [
                        'generated_references' => $results,
                        'errors' => $errors
                    ],
                    'message' => 'Some job references could not be generated'
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk generate job references failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to bulk generate job references: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get job reference history
     */
    public function getJobReferenceHistory(string $jobReference): array
    {
        try {
            $jobSchedule = JobSchedule::where('reference_number', $jobReference)->first();

            if (!$jobSchedule) {
                return [
                    'success' => false,
                    'message' => 'Job reference not found'
                ];
            }

            $history = [
                'job_reference' => $jobReference,
                'created_at' => $jobSchedule->created_at?->toISOString(),
                'updated_at' => $jobSchedule->updated_at?->toISOString(),
                'status_changes' => $this->getStatusChanges($jobSchedule),
                'assignments' => $this->getAssignmentHistory($jobSchedule),
                'reports' => $this->getReportHistory($jobSchedule)
            ];

            return [
                'success' => true,
                'data' => $history,
                'message' => 'Job reference history retrieved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Get job reference history failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get job reference history: ' . $e->getMessage()
            ];
        }
    }

    private function getStatusChanges($jobSchedule): array
    {
        // This would typically come from an audit log or status change history table
        return [
            [
                'status' => $jobSchedule->status,
                'changed_at' => $jobSchedule->updated_at?->toISOString(),
                'changed_by' => $jobSchedule->updated_by
            ]
        ];
    }

    private function getAssignmentHistory($jobSchedule): array
    {
        return [
            [
                'technician_id' => $jobSchedule->assigned_technician_id,
                'assigned_at' => $jobSchedule->assign_date?->toISOString(),
                'assigned_by' => $jobSchedule->created_by
            ]
        ];
    }

    private function getReportHistory($jobSchedule): array
    {
        return $jobSchedule->jobReports->map(function($report) {
            return [
                'id' => $report->id,
                'job_type' => $report->job_type,
                'completed_at' => $report->completed_at?->toISOString(),
                'technician_id' => $report->technician_id
            ];
        })->toArray();
    }
}
