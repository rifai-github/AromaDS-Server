<?php

namespace App\Http\Controllers;

use App\Services\JobReferenceService;
use App\Models\JobSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobReferenceController extends Controller
{
    protected $jobReferenceService;

    public function __construct(JobReferenceService $jobReferenceService)
    {
        $this->jobReferenceService = $jobReferenceService;
    }

    /**
     * Generate job reference number
     */
    public function generateJobReference(Request $request)
    {
        try {
            $request->validate([
                'job_type' => 'required|in:service,install,maintenance,repair,check',
                'contract_id' => 'nullable|exists:contracts,id',
                'building_id' => 'nullable|exists:buildings,id'
            ]);

            $result = $this->jobReferenceService->generateJobReferenceNumber(
                $request->job_type,
                $request->contract_id,
                $request->building_id
            );

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate job reference: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign job reference to job schedule
     */
    public function assignJobReference(Request $request)
    {
        try {
            $request->validate([
                'job_schedule_id' => 'required|exists:job_schedules,id',
                'job_reference' => 'nullable|string|max:20'
            ]);

            $result = $this->jobReferenceService->assignJobReference(
                $request->job_schedule_id,
                $request->job_reference
            );

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign job reference: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job reference details
     */
    public function getJobReferenceDetails(Request $request)
    {
        try {
            $request->validate([
                'job_reference' => 'required|string|max:20'
            ]);

            $result = $this->jobReferenceService->getJobReferenceDetails($request->job_reference);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get job reference details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search job references
     */
    public function searchJobReferences(Request $request)
    {
        try {
            $request->validate([
                'job_reference' => 'nullable|string|max:20',
                'job_type' => 'nullable|in:service,install,maintenance,repair,check',
                'status' => 'nullable|string|max:50',
                'building_id' => 'nullable|exists:buildings,id',
                'contract_id' => 'nullable|exists:contracts,id',
                'technician_id' => 'nullable|exists:users,id',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $filters = $request->only([
                'job_reference', 'job_type', 'status', 'building_id', 
                'contract_id', 'technician_id', 'date_from', 'date_to', 'limit'
            ]);

            $result = $this->jobReferenceService->searchJobReferences($filters);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data'],
                    'count' => $result['count']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search job references: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job reference statistics
     */
    public function getJobReferenceStatistics(Request $request)
    {
        try {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from'
            ]);

            $filters = $request->only(['date_from', 'date_to']);

            $result = $this->jobReferenceService->getJobReferenceStatistics($filters);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get job reference statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate job reference format
     */
    public function validateJobReference(Request $request)
    {
        try {
            $request->validate([
                'job_reference' => 'required|string|max:20'
            ]);

            $isValid = $this->jobReferenceService->validateJobReferenceFormat($request->job_reference);
            $exists = $this->jobReferenceService->isJobReferenceExists($request->job_reference);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'job_reference' => $request->job_reference,
                    'is_valid_format' => $isValid,
                    'exists' => $exists,
                    'available' => !$exists
                ],
                'message' => 'Job reference validation completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to validate job reference: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk generate job references
     */
    public function bulkGenerateJobReferences(Request $request)
    {
        try {
            $request->validate([
                'job_schedule_ids' => 'required|array|min:1',
                'job_schedule_ids.*' => 'required|exists:job_schedules,id'
            ]);

            $result = $this->jobReferenceService->bulkGenerateJobReferences($request->job_schedule_ids);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'data' => $result['data']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk generate job references: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job reference history
     */
    public function getJobReferenceHistory(Request $request)
    {
        try {
            $request->validate([
                'job_reference' => 'required|string|max:20'
            ]);

            $result = $this->jobReferenceService->getJobReferenceHistory($request->job_reference);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get job reference history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job schedules without job references
     */
    public function getJobSchedulesWithoutReferences(Request $request)
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
                'job_type' => 'nullable|in:service,install,maintenance,repair,check',
                'status' => 'nullable|string|max:50'
            ]);

            $query = JobSchedule::whereNull('reference_number')
                ->with(['building', 'contract', 'assignedTechnician']);

            if ($request->job_type) {
                $query->where('type', $request->job_type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $limit = $request->limit ?? 50;
            $jobSchedules = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            $results = $jobSchedules->map(function($jobSchedule) {
                return [
                    'id' => $jobSchedule->id,
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
                    ] : null,
                    'created_at' => $jobSchedule->created_at?->toISOString()
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'job_schedules' => $results,
                    'total_count' => $results->count()
                ],
                'message' => 'Job schedules without references retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get job schedules without references: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-assign job references to pending job schedules
     */
    public function autoAssignJobReferences(Request $request)
    {
        try {
            $request->validate([
                'job_type' => 'nullable|in:service,install,maintenance,repair,check',
                'status' => 'nullable|string|max:50',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $query = JobSchedule::whereNull('reference_number');

            if ($request->job_type) {
                $query->where('type', $request->job_type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $limit = $request->limit ?? 50;
            $jobScheduleIds = $query->limit($limit)->pluck('id')->toArray();

            if (empty($jobScheduleIds)) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'generated_references' => [],
                        'total_generated' => 0
                    ],
                    'message' => 'No job schedules found without references'
                ]);
            }

            $result = $this->jobReferenceService->bulkGenerateJobReferences($jobScheduleIds);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'data' => $result['data']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to auto-assign job references: ' . $e->getMessage()
            ], 500);
        }
    }
}
