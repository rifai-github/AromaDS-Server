<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\JobSchedule;
use App\Models\JobAssignment;
use App\Models\JobMaterial;
use App\Models\JobMaterialTransfer;
use App\Services\DocumentNumberService;
use App\Models\PeriodicJob;
use App\Models\Team;
use App\Models\MasterProduct;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Building;
use App\Models\User;
use App\Models\Quotation;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\InventoryMovement;
use App\Models\UnitOnWall;
use App\Models\UnitOnWallHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class JobScheduleController extends Controller
{
    use AccessControlFilterTrait;
    use \App\Http\Traits\ColumnFilterTrait;
    
    public function index(Request $request)
    {
        $viewMode = $request->get('view_mode', 'job');
        
        if ($viewMode === 'room') {
            $query = \App\Models\JobScheduleRoom::with([
                'jobSchedule.jobAdvice.customer',
                'jobSchedule.jobAdvice.contract.quotation.branch.city',
                'jobSchedule.building',
                'jobSchedule.assignedTechnician',
                'jobSchedule.createdBy',
                'jobSchedule.updatedBy',
                'jobSchedule.jobAssignSchedules.team',
                'jobSchedule.jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
                'jobSchedule.jobScheduleRooms', // Added for granular status logic
                'room',
                'roomAssignment.team'
            ]);
        } else {
            $query = JobSchedule::with([
                'jobAdvice.customer',
                'jobAdvice.contract.quotation.branch.city',
                'building',
                'room',
                'assignedTechnician',
                'createdBy',
                'updatedBy',
                'jobAssignSchedules.team',
                'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
                'jobScheduleRooms'
            ]);

            // Fix Duplication in Job View:
            // Show only one representative Job Schedule per Building + Type (for auto-generated JA jobs).
            // Manual jobs (job_advice_id IS NULL) are displayed individually.
            $query->where(function ($q) {
                $table = (new \App\Models\JobSchedule)->getTable();
                $q->whereIn('id', function ($sub) use ($table) {
                    $sub->selectRaw('MIN(id)')
                        ->from($table)
                        ->whereNotNull('job_advice_id')
                        ->whereNull('deleted_at')
                        ->groupBy('job_advice_id', 'building_id', 'type', 'period');
                })
                ->orWhereNull('job_advice_id');
            });
        }

        // Apply access control filter
        $user = Auth::user();
        if (!$user->hasRoleStartingWith('Management')) {
            $accessibleUserIds = $this->getAccessibleUserIds($user);
            
            // Get teams where user is leader or member
            $userTeamIds = \DB::table('teams')
                ->where('team_head_id', $user->id)
                ->pluck('id')
                ->merge(
                    \DB::table('team_members')
                        ->where('user_id', $user->id)
                        ->pluck('team_id')
                )
                ->unique()
                ->toArray();
            
            // Get allowed branch IDs from Hierarchy Data
            $branchIds = [];
            $branchAccess = $user->accessLevels()
                ->where('access_type', 'branch')
                ->where('is_active', true)
                ->first();
            if ($branchAccess) {
                $config = $branchAccess->access_config ?? [];
                $branchIds = $config['allowed_branches'] ?? [];
                if (empty($branchIds) && $user->branch_id) {
                    $branchIds = [$user->branch_id];
                }
            }
            
            if ($viewMode === 'room') {
                $query->whereHas('jobSchedule', function($q) use ($accessibleUserIds, $userTeamIds, $branchIds) {
                    $q->where(function($subQ) use ($accessibleUserIds, $userTeamIds, $branchIds) {
                         $subQ->whereIn('created_by', $accessibleUserIds)
                              ->orWhereHas('jobAdvice', function($adviceQ) use ($accessibleUserIds, $branchIds) {
                                  $adviceQ->whereIn('created_by', $accessibleUserIds)
                                          ->orWhereIn('request_by', $accessibleUserIds);
                                  
                                  if (!empty($branchIds)) {
                                      $adviceQ->orWhereHas('quotation', function($qQ) use ($branchIds) {
                                          $qQ->whereIn('branch_id', $branchIds);
                                      })->orWhereHas('contract.quotation', function($qQ) use ($branchIds) {
                                          $qQ->whereIn('branch_id', $branchIds);
                                      });
                                  }
                              })
                              ->orWhereHas('jobAssignSchedules', function($assignQ) use ($userTeamIds) {
                                  $assignQ->whereIn('team_id', $userTeamIds);
                              });
                    });
                });
            } else {
                $query->where(function($q) use ($accessibleUserIds, $userTeamIds, $branchIds) {
                    $q->whereIn('created_by', $accessibleUserIds)
                      ->orWhereHas('jobAdvice', function($subQ) use ($accessibleUserIds, $branchIds) {
                          $subQ->whereIn('created_by', $accessibleUserIds)
                               ->orWhereIn('request_by', $accessibleUserIds);
                          
                          if (!empty($branchIds)) {
                              $subQ->orWhereHas('quotation', function($qQ) use ($branchIds) {
                                  $qQ->whereIn('branch_id', $branchIds);
                              })->orWhereHas('contract.quotation', function($qQ) use ($branchIds) {
                                  $qQ->whereIn('branch_id', $branchIds);
                              });
                          }
                      })
                      ->orWhereHas('jobAssignSchedules', function($subQ) use ($userTeamIds) {
                          $subQ->whereIn('team_id', $userTeamIds);
                      });
                });
            }
        }
        
        // Apply filters
        // Common filters mapping to appropriate relations
        $applyJobFilter = function($q, $column, $operator, $value) use ($viewMode) {
            if ($viewMode === 'room') {
                $q->whereHas('jobSchedule', function($jq) use ($column, $operator, $value) {
                    $jq->where($column, $operator, $value);
                });
            } else {
                $q->where($column, $operator, $value);
            }
        };

        if ($request->filled('search') && !$request->has('filter')) {
            $search = $request->search;
            if ($viewMode === 'room') {
                $query->where(function($masterQ) use ($search) {
                     $masterQ->whereHas('jobSchedule', function($q) use ($search) {
                         $q->where('job_number', 'like', "%{$search}%")
                           ->orWhere('company_name', 'like', "%{$search}%")
                           ->orWhere('contract_number', 'like', "%{$search}%")
                           ->orWhereHas('jobAdvice.customer', function($subQ) use ($search) {
                               $subQ->where('name', 'like', "%{$search}%");
                           })
                           ->orWhereHas('building', function($subQ) use ($search) {
                               $subQ->where('nama_gedung', 'like', "%{$search}%");
                           });
                     })->orWhereHas('room', function($rQ) use ($search) {
                        $rQ->where('room_name', 'like', "%{$search}%");
                     });
                });
            } else {
                $query->where(function($q) use ($search) {
                    $q->where('job_number', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('contract_number', 'like', "%{$search}%")
                      ->orWhereHas('jobAdvice.customer', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('building', function($subQ) use ($search) {
                          $subQ->where('nama_gedung', 'like', "%{$search}%");
                      });
                });
            }
        }

        if ($request->filled('type') && !$request->has('filter')) {
            $applyJobFilter($query, 'type', '=', $request->type);
        }

        if ($request->filled('status') && !$request->has('filter')) {
            if ($viewMode === 'room') {
                $query->whereHas('jobSchedule', function($q) use ($request) {
                     $q->where('status', $request->status);
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('building_id') && !$request->has('filter')) {
             $applyJobFilter($query, 'building_id', '=', $request->building_id);
        }

        if ($request->filled('contract_number') && !$request->has('filter')) {
             $applyJobFilter($query, 'contract_number', '=', $request->contract_number);
        }
        
        if ($request->filled('schedule_date') && !$request->has('filter')) {
             $applyJobFilter($query, 'schedule_date', '=', $request->schedule_date);
        }

        if ($request->filled('company_name') && !$request->has('filter')) {
             if ($viewMode === 'room') {
                 $query->whereHas('jobSchedule', function($q) use ($request) {
                     $q->where('company_name', 'like', "%{$request->company_name}%")
                       ->orWhereHas('jobAdvice.customer', function($subQ) use ($request) {
                           $subQ->where('name', 'like', "%{$request->company_name}%");
                       });
                 });
             } else {
                 $query->where(function($q) use ($request) {
                    $q->where('company_name', 'like', "%{$request->company_name}%")
                      ->orWhereHas('jobAdvice.customer', function($subQ) use ($request) {
                          $subQ->where('name', 'like', "%{$request->company_name}%");
                      });
                });
             }
        }

        if ($request->filled('date_from')) {
            if ($viewMode === 'room') {
                 $query->whereHas('jobSchedule', function($jq) use ($request) {
                     $jq->whereDate('schedule_date', '>=', $request->date_from);
                 });
            } else {
                 $query->whereDate('schedule_date', '>=', $request->date_from);
            }
        }

        if ($request->filled('date_to')) {
            if ($viewMode === 'room') {
                 $query->whereHas('jobSchedule', function($jq) use ($request) {
                     $jq->whereDate('schedule_date', '<=', $request->date_to);
                 });
            } else {
                 $query->whereDate('schedule_date', '<=', $request->date_to);
             }
        }
        
        // Generic Column Filter
        if ($request->has('filter')) {
            foreach ($request->input('filter') as $key => $value) {
                if ($value === null || $value === '') continue;
                
                // Special handling for building relation filters
                $normalizedKey = str_replace('__', '.', $key);
                if ($normalizedKey === 'building.nama_gedung' || $key === 'building__nama_gedung') {
                    if ($viewMode === 'room') {
                        $query->whereHas('jobSchedule.building', function($q) use ($value) {
                            $q->where('nama_gedung', 'like', "%{$value}%");
                        });
                    } else {
                        $query->whereHas('building', function($q) use ($value) {
                            $q->where('nama_gedung', 'like', "%{$value}%");
                        });
                    }
                    continue; 
                }

                // Smart Branch Filter: Includes City Name from Branch or District Name from Building
                if ($normalizedKey === 'building.district.name' || $key === 'building__district__name') {
                    $query->where(function($q) use ($value) {
                        // 1. Check District name from Building relation
                        $q->whereHas('building.district', function($sub) use ($value) {
                            $sub->where('name', 'like', "%{$value}%");
                        })
                        // 2. Or check City name from Branch relation (via JA -> Quotation)
                        ->orWhereHas('jobAdvice.quotation.branch.city', function($sub) use ($value) {
                            $sub->where('name', 'like', "%{$value}%");
                        })
                        // 3. Or check City name from Branch relation (via JA -> Contract -> Quotation)
                        ->orWhereHas('jobAdvice.contract.quotation.branch.city', function($sub) use ($value) {
                            $sub->where('name', 'like', "%{$value}%");
                        })
                        // 4. Fallback to direct district field on JobSchedule
                        ->orWhere('district', 'like', "%{$value}%");
                    });
                    continue;
                }
                if ($normalizedKey === 'status') {
                    $searchValues = [$value];
                    $valLower = strtolower($value);

                    if (str_contains($valLower, 'new') || str_contains($valLower, 'schedul')) {
                        $searchValues = array_unique(array_merge($searchValues, ['new_job', 'scheduled']));
                    }
                    if (str_contains($valLower, 'done') || str_contains($valLower, 'complete')) {
                        $searchValues = array_unique(array_merge($searchValues, ['done_job', 'completed']));
                    }
                    if (str_contains($valLower, 'material')) {
                        $searchValues = array_unique(array_merge($searchValues, ['assign_material', 'barang_dipersiapkan', 'barang_siap_diambil', 'barang_diambil', 'material_issue']));
                    }
                    if (str_contains($valLower, 'prep')) {
                        $searchValues = array_unique(array_merge($searchValues, ['material_issue', 'barang_dipersiapkan']));
                    }
                    if (str_contains($valLower, 'issue')) {
                        // Matches both 'Material in Prep' (internal code material_issue) 
                        // and 'Material Issued' (internal code barang_diambil)
                        $searchValues = array_unique(array_merge($searchValues, ['material_issue', 'barang_diambil']));
                    }
                    if (str_contains($valLower, 'ready')) {
                        $searchValues = array_unique(array_merge($searchValues, ['barang_siap_diambil']));
                    }
                    if (str_contains($valLower, 'assign')) {
                        $searchValues = array_unique(array_merge($searchValues, ['assign_material', 'assign_team']));
                    }
                    
                    if ($viewMode === 'room') {
                        $query->whereHas('jobSchedule', function($jq) use ($searchValues) {
                            $jq->where(function($sub) use ($searchValues) {
                                foreach ($searchValues as $sv) {
                                    $sub->orWhere('status', 'like', "%{$sv}%");
                                }
                            });
                        });
                    } else {
                        $query->where(function($sub) use ($searchValues) {
                            foreach ($searchValues as $sv) {
                                $sub->orWhere('status', 'like', "%{$sv}%");
                            }
                        });
                    }
                    continue; 
                }
                
                // Adjust key for Room View
                $targetKey = $normalizedKey;
                if ($viewMode === 'room') {
                    $jobColumns = [
                        'job_number', 'type', 'company_name', 'contract_number', 'period', 
                        'p_invoice', 'schedule_date', 'expected_date', 'ba_date', 
                        'assign_date', 'issue_date', 'postal_code', 'district', 
                        'internal_notes', 'reference_number'
                    ];
                    if (in_array($key, $jobColumns)) {
                        $targetKey = 'jobSchedule.' . $key;
                    } elseif (str_starts_with($normalizedKey, 'building.')) {
                        $targetKey = 'jobSchedule.' . $normalizedKey;
                    } elseif (str_starts_with($normalizedKey, 'jobAssignSchedules.')) {
                        $targetKey = 'jobSchedule.' . $normalizedKey;
                    }
                }
                
                $allowedDirectColumns = [
                    'job_number', 'type', 'company_name', 'contract_number', 'period', 
                    'p_invoice', 'schedule_date', 'expected_date', 'ba_date', 
                    'assign_date', 'issue_date', 'postal_code', 'district', 
                    'internal_notes', 'reference_number', 'status', 'day', 'sub_district'
                ];
                
                if (str_contains($targetKey, '.')) {
                    $parts = explode('.', $targetKey);
                    $column = array_pop($parts);
                    $relation = implode('.', $parts);
                    $query->whereHas($relation, function($q) use ($column, $value) {
                        $q->where($column, 'like', "%{$value}%");
                    });
                } else {
                    if (in_array($targetKey, $allowedDirectColumns)) {
                        $query->where($targetKey, 'like', "%{$value}%");
                    }
                }
            }
        }

        // Handling Sort for Room View (if sorting by Job properties)
        if ($viewMode === 'room') {
             $query->orderBy('created_at', 'desc');
        } else {
             $query->with(['jobAssignSchedules.team'])->orderBy('created_at', 'desc');
        }
        
        $perPage = $request->input('per_page', 25);
        $jobSchedules = $query->paginate($perPage)->appends($request->query());

        // Fix for Fragmented Data: aggregate rooms from sibling JobSchedules
        // with a single batched lookup per page to avoid N+1 queries.
        if ($viewMode !== 'room') {
            $this->attachGroupedRoomsToJobs($jobSchedules);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $jobSchedules->items()
            ]);
        }

        [
            $buildings,
            $job_advices,
            $rooms,
            $technicians,
            $teams,
            $contracts,
        ] = $this->getIndexFilterOptions();

        // Check permissions for dropdown actions
        $canSuspend = $user->hasPermission('operational.job-schedules-suspend.view');
        $canDPF = $user->hasPermission('operational.job-schedules-dpf.view');
        $canUnpostBA = $user->hasPermission('operational.job-schedules-unpost-ba.view');
        $canUnpostIssue = $user->hasPermission('operational.job-schedules-unpost-issue.view');
        $canUnassignTeam = $user->hasPermission('operational.job-schedules-unassign-team.view');
        $canMaterialAssign = $user->hasPermission('operational.job-schedules-material-assign.view');
        $canUnassignMaterial = $user->hasPermission('operational.job-schedules-unassign-material.view');
        $canPrint = $user->hasPermission('operational.job-schedules-print.view');
        $canAssignTeam = $user->hasPermission('operational.job-schedules-assign-team.view');

        return view('operational.job-schedules.index', compact(
            'jobSchedules', 'buildings', 'job_advices', 'rooms', 
            'technicians', 'teams', 'contracts', 'viewMode',
            'canSuspend', 'canDPF', 'canUnpostBA', 'canUnpostIssue', 
            'canUnassignTeam', 'canMaterialAssign', 'canUnassignMaterial', 
            'canPrint', 'canAssignTeam'
        ));
    }

    private function attachGroupedRoomsToJobs($jobSchedules): void
    {
        $jobs = collect($jobSchedules->items());

        foreach ($jobs->whereNull('job_advice_id') as $job) {
            $job->setRelation('allGroupedRooms', $job->jobScheduleRooms);
        }

        $groupedSeedJobs = $jobs
            ->filter(fn ($job) => $job->job_advice_id)
            ->groupBy(fn ($job) => $this->buildJobGroupingKey($job))
            ->map->first();

        if ($groupedSeedJobs->isEmpty()) {
            return;
        }

        $siblingJobs = JobSchedule::with([
                'room',
                'jobScheduleRooms',
                'jobAssignSchedules.team',
                'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
            ])
            ->whereIn('job_advice_id', $groupedSeedJobs->pluck('job_advice_id')->unique()->values())
            ->whereIn('building_id', $groupedSeedJobs->pluck('building_id')->filter()->unique()->values())
            ->whereIn('type', $groupedSeedJobs->pluck('type')->filter()->unique()->values())
            ->whereNull('deleted_at')
            ->get();

        $siblingsByKey = $siblingJobs->groupBy(fn ($job) => $this->buildJobGroupingKey($job));

        $roomsByJobScheduleId = \App\Models\JobScheduleRoom::with([
                'jobSchedule' => function ($q) {
                    $q->select('id', 'job_number', 'status')
                        ->with(['jobAssignSchedules.team', 'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items']);
                },
                'jobSchedule.jobScheduleRooms',
            ])
            ->whereIn('job_schedule_id', $siblingJobs->pluck('id')->unique()->values())
            ->get()
            ->groupBy('job_schedule_id');

        foreach ($groupedSeedJobs as $job) {
            $key = $this->buildJobGroupingKey($job);
            $siblings = $siblingsByKey->get($key, collect([$job]));

            $rooms = $siblings->pluck('id')
                ->flatMap(fn ($siblingId) => $roomsByJobScheduleId->get($siblingId, collect()))
                ->values();

            if ($rooms->isEmpty()) {
                $rooms = $siblings->map(function ($js) {
                    $room = new \App\Models\JobScheduleRoom();
                    $room->room_name = $js->room_name ?? $js->room->room_name ?? 'Unknown Room';
                    $room->setRelation('jobSchedule', $js);

                    return $room;
                })->values();
            }

            $job->setRelation('allGroupedRooms', $rooms);
        }
    }

    private function buildJobGroupingKey(JobSchedule $job): string
    {
        return implode('|', [
            $job->job_advice_id ?? 'no-ja',
            $job->building_id ?? 'no-building',
            $job->type ?? 'no-type',
            $job->period ?? 'no-period',
        ]);
    }

    private function normalizeSelectedJobScheduleRoomIds(array $roomIds): array
    {
        return collect($roomIds)
            ->filter(fn ($roomId) => is_numeric($roomId))
            ->map(fn ($roomId) => (int) $roomId)
            ->filter(fn ($roomId) => $roomId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function ensureMaterialAssignForSelectedRooms(array $roomIds, array &$batchJobNumbers = []): void
    {
        $roomIds = $this->normalizeSelectedJobScheduleRoomIds($roomIds);
        if (empty($roomIds)) {
            return;
        }

        $rooms = \App\Models\JobScheduleRoom::with(['jobSchedule.jobAdvice'])
            ->whereIn('id', $roomIds)
            ->get()
            ->filter(fn ($room) => $room->jobSchedule);

        if ($rooms->isEmpty()) {
            return;
        }

        $documentNumberService = app(\App\Services\DocumentNumberService::class);

        foreach ($rooms->groupBy(fn ($room) => $this->buildJobGroupingKey($room->jobSchedule)) as $groupRooms) {
            $jobs = $groupRooms
                ->map(fn ($room) => $room->jobSchedule)
                ->filter()
                ->unique('id')
                ->values();

            if ($jobs->isEmpty()) {
                continue;
            }

            $firstJob = $jobs->first();
            $batchKey = ($firstJob->job_advice_id ?? 'manual') . '_' . ($firstJob->building_id ?? '0') . '_' . $firstJob->type . '_' . ($firstJob->schedule_date ? $firstJob->schedule_date->format('Y-m-d') : 'nodate');
            $sharedJobNumber = $jobs->pluck('job_number')->filter()->first() ?? ($batchJobNumbers[$batchKey] ?? null);

            if (!$sharedJobNumber) {
                $type = strtolower($firstJob->type ?? '');
                $jaType = strtolower($firstJob->jobAdvice->type ?? '');
                $docType = 'job_schedule';

                if ($type === 'install' || $type === 'install_free') {
                    $docType = ($jaType === 'install_free' || $type === 'install_free') ? 'installation_free' : 'installation_report';
                } elseif (str_contains($type, 'service')) {
                    $docType = 'customer_service_report';
                } elseif (str_contains($type, 'remove')) {
                    $docType = ($type === 'remove_free' || $type === 'remove free' || $jaType === 'remove_free') ? 'remove_free' : 'remove';
                }

                $sharedJobNumber = $documentNumberService->generate(
                    $docType,
                    null,
                    $firstJob->building_id,
                    $firstJob->jobAdvice?->contract_id ?? null,
                    $firstJob->jobAdvice?->quotation_id ?? null,
                    null,
                    null
                );
            }

            $batchJobNumbers[$batchKey] = $sharedJobNumber;

            foreach ($jobs as $job) {
                if (!in_array($job->status, ['new_job', 'scheduled', 'assign_material'], true)) {
                    continue;
                }

                $job->update([
                    'job_number' => $sharedJobNumber,
                    'status' => 'assign_material',
                    'assign_date' => $job->assign_date ?: now()->toDateString(),
                    'updated_by' => Auth::id(),
                ]);

                $jobAssignSchedule = \App\Models\JobAssignSchedule::firstOrCreate(
                    [
                        'job_schedule_id' => $job->id,
                        'team_id' => null,
                    ],
                    [
                        'assigned_by' => Auth::id(),
                        'assigned_date' => now()->toDateString(),
                        'status' => 'assigned',
                        'notes' => 'Auto-created via Material Assign selected room sync',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );

                if ($jobAssignSchedule->status === 'cancelled') {
                    $jobAssignSchedule->update([
                        'status' => 'assigned',
                        'assigned_by' => Auth::id(),
                        'assigned_date' => now()->toDateString(),
                        'updated_by' => Auth::id(),
                    ]);
                }

                $roomIdsForJob = $groupRooms
                    ->where('job_schedule_id', $job->id)
                    ->pluck('id')
                    ->values()
                    ->all();

                $this->autoCreateMaterialIssue($jobAssignSchedule, $roomIdsForJob);
            }
        }
    }

    private function getIndexFilterOptions(): array
    {
        return [
            Cache::remember('job-schedules:filters:buildings:v1', now()->addMinutes(10), function () {
                return Building::withoutGlobalScope('autoFilter')
                    ->orderBy('nama_gedung')
                    ->get();
            }),
            Cache::remember('job-schedules:filters:job-advices:v1', now()->addMinutes(5), function () {
                return \App\Models\JobAdvice::withoutGlobalScope('autoFilter')
                    ->with('customer')
                    ->whereIn('status', ['approved', 'submitted'])
                    ->get();
            }),
            Cache::remember('job-schedules:filters:rooms:v1', now()->addMinutes(10), function () {
                return \App\Models\MasterRoom::withoutGlobalScope('autoFilter')
                    ->orderBy('room_name')
                    ->get();
            }),
            Cache::remember('job-schedules:filters:technicians:v1', now()->addMinutes(5), function () {
                return User::withoutGlobalScope('autoFilter')
                    ->where('is_active', true)
                    ->where('roles', 'technician')
                    ->orderBy('name')
                    ->get();
            }),
            Cache::remember('job-schedules:filters:teams:v1', now()->addMinutes(5), function () {
                return Team::withoutGlobalScope('autoFilter')
                    ->where('active_status', true)
                    ->orderBy('team_name')
                    ->get();
            }),
            Cache::remember('job-schedules:filters:contracts:v1', now()->addMinutes(10), function () {
                return \App\Models\Contract::withoutGlobalScope('autoFilter')
                    ->orderBy('contract_number')
                    ->get();
            }),
        ];
    }
    
    public function extendDay(Request $request, $id)
    {
        $request->validate([
            'new_date' => 'required|date|after:today'
        ]);
        
        $jobSchedule = JobSchedule::findOrFail($id);
        
        // Logic to update expected date or schedule date
        $jobSchedule->expected_date = $request->new_date; 
        $jobSchedule->save();
        
        return response()->json(['status' => 'success', 'message' => 'Job extended successfully']);
    }

    /**
     * Helper to sync team_id to Inventory Issuing records
     * derived from job schedule's material issues.
     */
    private function syncTeamToInventoryIssuing($jobScheduleId, $teamId)
    {
        try {
            // Find material issues linked to this job schedule
            // via JobAssignSchedule -> JobAssignMaterialIssue -> MaterialIssue
            $materialIssueNumbers = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($jobScheduleId) {
                $q->where('job_schedule_id', $jobScheduleId);
            })->pluck('issue_number')->toArray();
            
            if (empty($materialIssueNumbers)) {
                return;
            }
            
            // Update Inventory Issuing records linked by reference_no
            $updated = \App\Models\InventoryIssuing::whereIn('reference_no', $materialIssueNumbers)
                ->update(['team_id' => $teamId]);
                
            if ($updated > 0) {
                \Log::info("Synced Team ID " . ($teamId ?? 'NULL') . " to {$updated} Inventory Issuing record(s) for Job Schedule ID {$jobScheduleId}");
            }
            
        } catch (\Exception $e) {
            \Log::error("Failed to sync team to inventory issuing for Job Schedule ID {$jobScheduleId}: " . $e->getMessage());
            // Non-critical, don't throw
        }
    }

    private function getJobAdviceRoomPhysicalKey($jaRoom): string
    {
        $buildingId = $jaRoom->contractRoom?->room?->building_id
            ?? $jaRoom->quotationRoom?->room?->building_id
            ?? $jaRoom->contractRoom?->building_id
            ?? $jaRoom->quotationRoom?->building_id
            ?? 'no-building';

        $roomId = $jaRoom->contractRoom?->room_id
            ?? $jaRoom->quotationRoom?->room_id
            ?? $jaRoom->room_id
            ?? null;

        if ($roomId) {
            return "room:{$buildingId}:{$roomId}";
        }

        return 'name:' . $buildingId . ':' . strtolower(trim((string) $jaRoom->room_name));
    }

    private function getJobAdviceRoomPhysicalRoomId($jaRoom): ?int
    {
        return $jaRoom->contractRoom?->room_id
            ?? $jaRoom->quotationRoom?->room_id
            ?? $jaRoom->room_id
            ?? null;
    }

    private function syncJobScheduleRoomsFromJobAdvice(JobSchedule $jobSchedule, $jobAdvice, ?string $linkColumn = null, ?array $onlyPhysicalRoomIds = null): void
    {
        $jobAdvice->loadMissing([
            'rooms.contractRoom.room',
            'rooms.quotationRoom.room',
            'rooms.rentalProduct',
        ]);

        $allowedRoomIds = $onlyPhysicalRoomIds === null
            ? null
            : array_values(array_unique(array_filter(array_map('intval', $onlyPhysicalRoomIds))));

        $sourceRooms = $jobAdvice->rooms;
        if ($allowedRoomIds !== null) {
            $sourceRooms = $sourceRooms->filter(function ($jaRoom) use ($allowedRoomIds) {
                $physicalRoomId = $this->getJobAdviceRoomPhysicalRoomId($jaRoom);

                return $physicalRoomId && in_array((int) $physicalRoomId, $allowedRoomIds, true);
            });
        }

        if ($sourceRooms->isEmpty()) {
            \Log::warning("No eligible Job Advice rooms to sync for job schedule {$jobSchedule->id}", [
                'job_number' => $jobSchedule->job_number,
                'job_advice_id' => $jobAdvice->id ?? null,
                'allowed_room_ids' => $allowedRoomIds,
            ]);

            return;
        }

        $groups = $sourceRooms->groupBy(fn ($jaRoom) => $this->getJobAdviceRoomPhysicalKey($jaRoom));
        $userId = Auth::id() ?? \App\Models\User::first()?->id;

        foreach ($groups as $roomGroup) {
            $primaryJaRoom = $roomGroup->first();

            if ($linkColumn) {
                foreach ($roomGroup as $jaRoom) {
                    $jaRoom->update([$linkColumn => $jobSchedule->id]);
                }
            }

            $jobScheduleRoom = \App\Models\JobScheduleRoom::firstOrCreate(
                [
                    'job_schedule_id' => $jobSchedule->id,
                    'job_advice_room_id' => $primaryJaRoom->id,
                ],
                [
                    'room_name' => $primaryJaRoom->room_name,
                    'room_id' => $this->getJobAdviceRoomPhysicalRoomId($primaryJaRoom),
                    'status' => 'pending',
                    'material_return_status' => 'not_required',
                    'notes' => $roomGroup->count() > 1 ? 'Rentals in this room: ' . $roomGroup->count() : null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]
            );

            if (! $jobScheduleRoom->wasRecentlyCreated) {
                $jobScheduleRoom->fill([
                    'room_name' => $jobScheduleRoom->room_name ?: $primaryJaRoom->room_name,
                    'room_id' => $jobScheduleRoom->room_id ?: $this->getJobAdviceRoomPhysicalRoomId($primaryJaRoom),
                    'updated_by' => $userId,
                ])->save();
            }

            $isFirst = true;
            foreach ($roomGroup as $jaRoom) {
                $rentalLink = \App\Models\JobScheduleRoomRental::withTrashed()->firstOrNew([
                    'job_schedule_room_id' => $jobScheduleRoom->id,
                    'job_advice_room_id' => $jaRoom->id,
                ]);
                $rentalLink->is_primary = $isFirst;
                $rentalLink->save();
                if (method_exists($rentalLink, 'trashed') && $rentalLink->trashed()) {
                    $rentalLink->restore();
                }
                $isFirst = false;
            }
        }
    }

    /**
     * Resolve the correct job status after team unassignment.
     * Priority:
     * 1. Preserve advanced execution statuses
     * 2. Follow linked Inventory Issuing status (ready/prep/issued)
     * 3. Fall back to Material Issue status
     * 4. Fall back to material link existence
     */
    private function determineStatusAfterTeamUnassign(JobSchedule $jobSchedule): string
    {
        $advancedStatuses = ['barang_diambil', 'teknisi_tiba_dilokasi', 'meninggalkan_lokasi', 'in_progress', 'suspend', 'dpf'];
        if (in_array($jobSchedule->status, $advancedStatuses, true)) {
            return $jobSchedule->status;
        }

        $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($jobSchedule) {
            $q->where('job_schedule_id', $jobSchedule->id);
        })->get();

        if ($materialIssues->isNotEmpty()) {
            $issueNumbers = $materialIssues->pluck('issue_number')->filter()->values();

            if ($issueNumbers->isNotEmpty()) {
                $inventoryIssuings = \App\Models\InventoryIssuing::whereIn('reference_no', $issueNumbers->all())->get();

                if ($inventoryIssuings->contains(fn ($issuing) => in_array($issuing->status, ['sent', 'received'], true))) {
                    return 'barang_diambil';
                }

                if ($inventoryIssuings->contains(fn ($issuing) => $issuing->status === 'processed')) {
                    return 'barang_siap_diambil';
                }

                if ($inventoryIssuings->contains(fn ($issuing) => $issuing->status === 'pending')) {
                    return 'barang_dipersiapkan';
                }
            }

            if ($materialIssues->contains(fn ($issue) => $issue->status === 'issued')) {
                return 'barang_diambil';
            }

            if ($materialIssues->contains(fn ($issue) => in_array($issue->status, ['approved', 'pending', 'draft', 'out_of_stock'], true))) {
                return 'assign_material';
            }

            return 'assign_material';
        }

        $hasMaterialLink = \App\Models\JobAssignMaterialIssue::whereHas('jobAssignSchedule', function($q) use ($jobSchedule) {
            $q->where('job_schedule_id', $jobSchedule->id);
        })->exists();

        return $hasMaterialLink ? 'assign_material' : 'new_job';
    }

    public function unassignTeam(Request $request, $id)
    {
        // Cancel Assignment Only (Non-Destructive)
        // MOM: Modified to handle Grouped Jobs (Shared Job Number)
        // Ensure all jobs sharing the same Job Number are unassigned together.
        
        $jobSchedule = JobSchedule::findOrFail($id);
        
        // 1. Protection: Check if job is already done
        if (in_array($jobSchedule->status, ['done_job', 'completed'])) {
            return response()->json([
                'status' => 'error', 
                'message' => "Job ({$jobSchedule->job_number}) sudah berstatus 'Done Job' dan tidak bisa di-unassign."
            ], 422);
        }

        // Find all siblings based on Job View Grouping (Same JA + Building + Type)
        $targets = collect([$jobSchedule]);
        
        if ($jobSchedule->job_advice_id) {
            $siblingsQuery = JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                ->where('building_id', $jobSchedule->building_id)
                ->where('type', $jobSchedule->type)
                ->where('id', '!=', $id)
                ->where('status', '!=', 'cancelled');
            
            // Add accurate period grouping to prevent unassigning future services
            if ($jobSchedule->period !== null) {
                $siblingsQuery->where('period', $jobSchedule->period);
            } else {
                $siblingsQuery->whereNull('period');
            }
            
            // If room_ids is provided, filter siblings to only those rooms
            if ($request->has('room_ids')) {
                $siblingsQuery->whereIn('id', $request->room_ids);
            }

            $siblings = $siblingsQuery->get();
                
            $targets = $targets->merge($siblings);
            \Log::info("Unassign Team: Found " . $targets->count() . " linked jobs via Grouping (JA+Building+Type+Period)");
        }
        
        DB::beginTransaction();
        try {
            $count = 0;
            
            foreach ($targets as $targetJob) {
                // 1. Cancel Active Assignments
                $activeAssignments = \App\Models\JobAssignSchedule::where('job_schedule_id', $targetJob->id)
                    ->where('status', '!=', 'cancelled')
                    ->get();
                    
                foreach ($activeAssignments as $assignment) {
                    $assignment->update([
                        'status' => 'cancelled',
                        'notes' => ($assignment->notes ?? '') . "\n[UNASSIGNED] on " . now() . " by " . Auth::user()->name,
                        'updated_by' => Auth::id()
                    ]);
                }
                
                // 2. Reset Job Status
                $newStatus = $this->determineStatusAfterTeamUnassign($targetJob);

                $targetJob->status = $newStatus;
                $targetJob->job_number = ($newStatus !== 'new_job' && $newStatus !== 'scheduled') ? $targetJob->job_number : null;
                $targetJob->assign_date = null;
                $targetJob->internal_notes .= "\n[UNASSIGNED] Team unassigned on " . now() . " by " . Auth::user()->name . ". Status set to {$newStatus}.";
                $targetJob->save();
                
                // 3. Sync Inventory Issuing Team ID to NULL
                $this->syncTeamToInventoryIssuing($targetJob->id, null);
                
                $count++;
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => "Team unassigned for {$count} job(s). Job ID preserved."]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function unpostIssue(Request $request, $id)
    {
        // Revert to Assign Team
        $jobSchedule = JobSchedule::with(['jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items.product', 'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.warehouse'])
            ->findOrFail($id);
            
        // Check if any material issue is already processed (Inventory Issuing status check)
        foreach ($jobSchedule->jobAssignSchedules as $jas) {
            foreach ($jas->jobAssignMaterialIssues as $jami) {
                if ($jami->materialIssue) {
                    $invIssuing = \App\Models\InventoryIssuing::where('reference_no', $jami->materialIssue->issue_number)->first();
                    if ($invIssuing && $invIssuing->status !== 'pending') {
                         return response()->json([
                            'status' => 'error', 
                            'message' => "Cannot unpost issue. Inventory Issuing {$invIssuing->issuing_number} is already {$invIssuing->status}."
                        ], 403);
                    }
                    
                    // Also check MaterialIssue status if no InvIssuing found but status implies processed?
                    // Generally 'issued' status implies InvIssuing exists. If it's 'approved' or 'pending', usually OK.
                }
            }
        }
        
        DB::beginTransaction();
        try {
            foreach ($jobSchedule->jobAssignSchedules as $jas) {
                foreach ($jas->jobAssignMaterialIssues as $jami) {
                    if ($jami->materialIssue) {
                        $materialIssue = $jami->materialIssue;
                        
                        // 1. Delete Inventory Issuing (pending only)
                        $invIssuing = \App\Models\InventoryIssuing::where('reference_no', $materialIssue->issue_number)->first();
                        if ($invIssuing) {
                             // Clean up any receiving created from this issuing
                             $linkedReceivings = \App\Models\InventoryReceiving::where('issuing_id', $invIssuing->id)
                                ->orWhere('reference_no', $invIssuing->issuing_number)
                                ->get();
                                
                             foreach ($linkedReceivings as $receiving) {
                                 $receiving->items()->forceDelete();
                                 $receiving->forceDelete();
                                 \Log::info("Deleted InventoryReceiving ID {$receiving->id} during unpost of MI {$materialIssue->issue_number}");
                             }

                             if ($invIssuing->status === 'pending') {
                                 $invIssuing->items()->delete();
                                 $invIssuing->delete();
                             }
                        }
                        
                        // 2. Revert Stock (Item-based) if status was 'issued'
                        if ($materialIssue->status === 'issued') {
                             $this->revertStockForMaterialIssue($materialIssue);
                        }
                        
                        // 3. Revert Material Issue status to pending (To allow editing)
                        // Note: Database columns issue_date and issued_by are NOT NULL, 
                        // so we keep existing values or keep them as is. Status 'pending' is enough.
                        $materialIssue->update([
                            'status' => 'pending',
                            'updated_by' => Auth::id()
                        ]);
                        
                        \Log::info("Reverted MaterialIssue {$materialIssue->issue_number} status to pending during unpost.");
                    }
                }
            }
            
            // Revert Job Status to assign_material (Ready to re-submit/re-edit)
            $jobSchedule->status = 'assign_material';
            $jobSchedule->material_checked = false; 
            $jobSchedule->material_checked_at = null;
            $jobSchedule->save();
            
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Job issue unposted. Material status reverted to Pending. Stock returned. Job status reverted to Material Assign. You can now edit and re-submit the materials.']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to unpost issue: ' . $e->getMessage()], 500);
        }
    }

    public function unsuspend(Request $request, JobSchedule $jobSchedule)
    {
        if ($jobSchedule->status !== 'suspend') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unsuspend hanya bisa dilakukan untuk job berstatus suspend.',
            ], 422);
        }

        $jobSchedule->load(['jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items.product']);

        try {
            DB::beginTransaction();

            $this->cleanupMaterialAssignForUnsuspend($jobSchedule);

            \App\Models\JobAssignSchedule::where('job_schedule_id', $jobSchedule->id)
                ->where('status', '!=', 'cancelled')
                ->update([
                    'status' => 'cancelled',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n[UNSUSPEND] Assignment cancelled while resetting suspended job to New Job.')"),
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)
                ->where('status', '!=', 'cancelled')
                ->update([
                    'status' => 'cancelled',
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                    'deleted_at' => now(),
                ]);

            \App\Models\JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)
                ->update([
                    'status' => \App\Models\JobScheduleRoom::STATUS_PENDING,
                    'completed_at' => null,
                    'completed_by' => null,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            \App\Models\JobReport::where('job_schedule_id', $jobSchedule->id)
                ->update([
                    'completed_at' => null,
                    'updated_at' => now(),
                ]);

            $jobSchedule->update([
                'job_number' => null,
                'status' => 'new_job',
                'assign_date' => null,
                'issue_date' => null,
                'ba_date' => null,
                'ba_number' => null,
                'started_at' => null,
                'completed_at' => null,
                'assigned_technician_id' => null,
                'work_status' => 'not_started',
                'material_checked' => false,
                'material_checked_at' => null,
                'internal_notes' => trim(($jobSchedule->internal_notes ? $jobSchedule->internal_notes . "\n" : '') . '[UNSUSPEND] Reset from suspend to New Job by ' . (Auth::user()?->name ?? 'system') . ' at ' . now()->format('Y-m-d H:i:s') . '. Job number, team, material assign, and BA cleared.'),
                'updated_by' => Auth::id(),
            ]);

            $this->syncTeamToInventoryIssuing($jobSchedule->id, null);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Job berhasil di-unsuspend dan dikembalikan ke New Job.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to unsuspend job {$jobSchedule->id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal unsuspend job: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function cleanupMaterialAssignForUnsuspend(JobSchedule $jobSchedule): void
    {
        $assignments = \App\Models\JobAssignSchedule::with(['jobAssignMaterialIssues.materialIssue.items.product'])
            ->where('job_schedule_id', $jobSchedule->id)
            ->get();

        foreach ($assignments as $assignment) {
            foreach ($assignment->jobAssignMaterialIssues as $link) {
                $materialIssue = $link->materialIssue;
                if (!$materialIssue) {
                    $link->delete();
                    continue;
                }

                $inventoryIssuing = \App\Models\InventoryIssuing::where('reference_no', $materialIssue->issue_number)->first();
                if ($inventoryIssuing && $inventoryIssuing->status !== 'pending') {
                    throw new \RuntimeException("Material issue {$materialIssue->issue_number} belum bisa dibersihkan karena Inventory Issuing {$inventoryIssuing->issuing_number} sudah {$inventoryIssuing->status}. Lakukan unpost issue/return material terlebih dahulu.");
                }

                if ($materialIssue->status === 'issued') {
                    $this->revertStockForMaterialIssue($materialIssue);
                }

                if ($inventoryIssuing) {
                    $linkedReceivings = \App\Models\InventoryReceiving::where('issuing_id', $inventoryIssuing->id)
                        ->orWhere('reference_no', $inventoryIssuing->issuing_number)
                        ->get();

                    foreach ($linkedReceivings as $receiving) {
                        $receiving->items()->forceDelete();
                        $receiving->forceDelete();
                    }

                    $inventoryIssuing->items()->delete();
                    $inventoryIssuing->delete();
                }

                $materialIssue->items()->delete();
                $materialIssue->delete();
                $link->delete();
            }
        }
    }
    
    private function revertStockForMaterialIssue($materialIssue)
    {
        if (!$materialIssue->warehouse_id || $materialIssue->items->isEmpty()) return;
        
        foreach ($materialIssue->items as $item) {
            if (!$item->product_id || $item->quantity <= 0) continue;
            
            // Find Warehouse Product
            $whProduct = \App\Models\WarehouseProduct::where('warehouse_id', $materialIssue->warehouse_id)
                ->where('master_product_id', $item->product_id)
                ->first();
                
            if (!$whProduct) {
                $whProduct = \App\Models\WarehouseProduct::create([
                    'warehouse_id' => $materialIssue->warehouse_id,
                    'master_product_id' => $item->product_id,
                    'quantity' => 0,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }
            
            // Add Stock Back
            $whProduct->increment('quantity', $item->quantity);
            
            // Log Movement
            $movementData = [
                'warehouse_id' => $materialIssue->warehouse_id,
                'master_product_id' => $item->product_id,
                'movement_type' => 'in', // Return
                'quantity' => $item->quantity,
                'notes' => "Unpost Issue / Return from Job. MI: {$materialIssue->issue_number}",
                'created_by' => Auth::id(),
            ];
            
            // Add optional columns if schema allows (simplified check or try/catch)
            // Assuming simplified for brevity or standard fields
            \App\Models\InventoryMovement::create($movementData);
        }
    }

    public function suspendRoom(Request $request, $id)
    {
        $room = \App\Models\JobScheduleRoom::findOrFail($id);

        if ($room->status === \App\Models\JobScheduleRoom::STATUS_COMPLETED) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room yang sudah completed tidak bisa di-suspend.',
            ], 422);
        }

        $room->update([
            // job_schedule_rooms.status enum only supports pending/in_progress/completed/cancelled.
            // Keep the room actionable and record the suspension in notes instead of writing an invalid enum.
            'status' => \App\Models\JobScheduleRoom::STATUS_PENDING,
            'notes' => trim(($room->notes ? $room->notes . "\n" : '') . '[SUSPEND] Room suspended by ' . (Auth::user()?->name ?? 'system') . ' at ' . now()->format('Y-m-d H:i:s')),
            'updated_by' => Auth::id(),
        ]);

        if ($room->jobSchedule && !in_array($room->jobSchedule->status, ['completed', 'done_job', 'cancelled'], true)) {
            $room->jobSchedule->update([
                'status' => 'suspend',
                'internal_notes' => trim(($room->jobSchedule->internal_notes ? $room->jobSchedule->internal_notes . "\n" : '') . '[SUSPEND] Room ' . ($room->room_name ?? $room->id) . ' suspended at ' . now()->format('Y-m-d H:i:s')),
                'updated_by' => Auth::id(),
            ]);
        }
        
        // Generate Transaction Code Logic here if needed for Room Level?
        
        return response()->json(['status' => 'success', 'message' => 'Room suspended.']);
    }

    public function assignRoom(Request $request, $id)
    {
        // Logic to assign specific room
        return response()->json(['status' => 'success', 'message' => 'Room assigned.']);
    }

    /**
     * Bulk unassign team from selected jobs
     * Resets jobs to new_job status and deletes pending material issues
     * Only works if Material Issue status is 'pending'
     * Syncs Inventory Issuing team to NULL
     */

    public function bulkUnassignTeam(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:job_schedules,id',
            'room_ids' => 'nullable|array',
            'room_ids.*' => 'integer'
        ]);

        try {
            DB::beginTransaction();

            $successCount = 0;
            $errors = [];
            
            // IMPORTANT: room_ids are JobScheduleRoom IDs, NOT Job IDs
            // We need to convert them to Job IDs first
            $targetIds = collect();

            if ($request->has('room_ids') && !empty($request->room_ids)) {
                // room_ids are JobScheduleRoom IDs - convert to Job IDs
                $jobScheduleRoomIds = $request->room_ids;
                
                // Get Job IDs from JobScheduleRoom
                $jobIdsFromRooms = \App\Models\JobScheduleRoom::whereIn('id', $jobScheduleRoomIds)
                    ->pluck('job_schedule_id')
                    ->unique()
                    ->toArray();
                
                $targetIds = collect($jobIdsFromRooms);
                
            } elseif ($request->has('ids') && !empty($request->ids)) {
                $targetIds = collect($request->ids);
                
                // Expand selection to include siblings (same job number/advice)
                foreach ($request->ids as $requestId) {
                    $job = JobSchedule::find($requestId);
                    if ($job && $job->job_advice_id) {
                         // Primary: Job Number
                         $q = JobSchedule::where('job_number', $job->job_number)
                            ->whereNull('deleted_at');
                         $siblings = $q->pluck('id');
                         
                         // Fallback: Advice ID (Always check for split-jobs)
                         if (true) {
                             $qFallback = JobSchedule::where('job_advice_id', $job->job_advice_id)
                                ->where('type', $job->type)
                                ->whereNull('deleted_at');
                             
                             // STRICT BUILDING FILTER
                             if ($job->building_id) {
                                 $qFallback->where('building_id', $job->building_id);
                             }
                                
                             $fallback = $qFallback->pluck('id');
                             
                             if ($fallback->count() > $siblings->count()) {
                                 $siblings = $fallback;
                             }
                         }

                        $targetIds = $targetIds->merge($siblings);
                    }
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada job yang dipilih.'
                ], 422);
            }
            
            $targetIds = $targetIds->unique();
            // NON-BLOCKING CHECK
            // Skip Done/Completed/Cancelled jobs only
            
            $validTargetIds = collect();
            $skippedCount = 0;
            
            foreach ($targetIds as $jobId) {
                $jobCheck = JobSchedule::find($jobId);
                if (!$jobCheck) continue;
                
                // Blacklist Check - only skip if status is done/completed/cancelled
                if (in_array($jobCheck->status, ['done_job', 'completed', 'cancelled'])) {
                     $skippedCount++;
                     continue;
                }
                
                // VALIDASI TEKNISI DI LOKASI - Mencegah tim dihapus jika teknisi sudah di lokasi atau sedang bekerja
                $activeStatuses = ['teknisi_tiba_dilokasi', 'in_progress', 'meninggalkan_lokasi', 'suspend', 'dpf'];
                if (in_array($jobCheck->status, $activeStatuses)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Tidak dapat unassign team: Teknisi untuk job {$jobCheck->job_number} sudah berada di lokasi / sedang mengerjakan. Pembatalan (melewatkan pekerjaan) harus dilakukan oleh teknisi melalui aplikasi mobile."
                    ], 422);
                }
                
                $validTargetIds->push($jobId);
            }
            
            if ($validTargetIds->isEmpty()) {
                 return response()->json([
                    'status' => 'error',
                    'message' => "Tidak ada job yang valid. Semua job terpilih berstatus selesai."
                ], 422);
            }
            
            // Re-assign valid targets
            $targetIds = $validTargetIds;

            // MOM FIX: Removed Blocking Check for Issued Materials.
            // User confirmed: "Jika barang disiapkan, bisa saja ganti tim".
            // User confirmed: "Jika barang disiapkan, bisa saja ganti tim".
            // So we allow unassigning team even if materials are issued. 
            // The logic below will sync inventory issuing team to NULL.
            /*
            foreach ($targetIds as $jobId) {
                $jobCheck = JobSchedule::with(['jobAssignSchedules.jobAssignMaterialIssues.materialIssue'])->find($jobId);
                if ($jobCheck) {
                    foreach ($jobCheck->jobAssignSchedules as $jas) {
                        foreach ($jas->jobAssignMaterialIssues as $jami) {
                            if ($jami->materialIssue && $jami->materialIssue->status === 'issued') {
                                return response()->json([
                                    'status' => 'error',
                                    'message' => "Cannot unassign group: Job {$jobCheck->job_number} has issued materials. Please return materials first."
                                ], 422);
                            }
                        }
                    }
                }
            }
            */

            foreach ($targetIds as $jobId) {
                try {
                    $jobSchedule = JobSchedule::find($jobId);
                    
                    if (!$jobSchedule) {
                        continue;
                    }

                    // 1. Cancel Active Assignments
                    $activeAssignments = \App\Models\JobAssignSchedule::where('job_schedule_id', $jobSchedule->id)
                        ->where('status', '!=', 'cancelled')
                        ->get();
                        
                    foreach ($activeAssignments as $assignment) {
                        $assignment->update([
                            'status' => 'cancelled',
                            'notes' => ($assignment->notes ?? '') . "\n[UNASSIGNED BULK] on " . now() . " by " . Auth::user()->name,
                            'updated_by' => Auth::id()
                        ]);
                    }

                    // 2. Clean up Granular Room Assignments (JobScheduleRoomAssignment)
                    // This ensures the "Assign Team" modal shows rooms as available
                    \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)
                        ->where('status', '!=', 'cancelled')
                        ->update([
                            'status' => 'cancelled',
                            'updated_by' => Auth::id(),
                            'deleted_at' => now() // Soft delete to be sure
                        ]);

                    // 3. Sync Inventory Issuing Team ID to NULL
                    $this->syncTeamToInventoryIssuing($jobSchedule->id, null);

                    // 3. Reset job schedule status
                    $newStatus = $this->determineStatusAfterTeamUnassign($jobSchedule);

                    $newJobNumber = ($newStatus !== 'new_job' && $newStatus !== 'scheduled') ? $jobSchedule->job_number : null;
                    
                    $jobSchedule->update([
                        'status' => $newStatus,
                        'job_number' => $newJobNumber,
                        'assign_date' => null,
                        'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[UNASSIGNED BULK] on " . now() . " by " . Auth::user()->name . ". Status set to {$newStatus}.",
                        'updated_by' => Auth::id()
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Job {$jobId}: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Some jobs failed to unassign: ' . implode(', ', $errors)
                ], 500);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully unassigned {$successCount} jobs."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Bulk Unassign Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'System error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Material Assign (Issue Job Number without team assignment)
     * For jobs with status 'new_job' or 'scheduled'
     */
    public function bulkMaterialAssign(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:job_schedules,id',
            'room_ids' => 'nullable|array',
            'room_ids.*' => 'integer'
        ]);

        if (empty($request->ids) && empty($request->room_ids)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data yang dipilih.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $successCount = 0;
            $skippedCount = 0;
            $skippedRemoveCount = 0;
            $documentNumberService = app(\App\Services\DocumentNumberService::class);
            
            // Determine target Job IDs.
            $targetIds = collect();
            $specificRoomIds = $request->room_ids ?? [];
            $strictSelection = $request->boolean('strict_selection', false);

            if ($request->has('room_ids') && !empty($request->room_ids)) {
                // room_ids are JobScheduleRoom IDs - convert to Job IDs
                $jobIdsFromRooms = \App\Models\JobScheduleRoom::whereIn('id', $request->room_ids)
                    ->pluck('job_schedule_id')
                    ->unique()
                    ->toArray();
                
                $targetIds = collect($jobIdsFromRooms);
            } else {
                $targetIds = collect($request->ids);
                // Expand selection to include siblings only for legacy/group flows.
                // Material Assign from job schedule table must stay checkbox-based.
                if (!$strictSelection) {
                    foreach ($request->ids as $requestId) {
                        $job = JobSchedule::find($requestId);
                        if ($job && $job->job_advice_id) {
                             // Primary: Job Number
                             $q = JobSchedule::where('job_number', $job->job_number)
                                ->whereNull('deleted_at');
                             $siblings = $q->pluck('id');
                             
                             // Fallback: Advice ID (Always check for split-jobs)
                             $qFallback = JobSchedule::where('job_advice_id', $job->job_advice_id)
                                ->where('type', $job->type)
                                ->whereNull('deleted_at');
                             
                             // STRICT BUILDING FILTER
                             if ($job->building_id) {
                                 $qFallback->where('building_id', $job->building_id);
                             }
                                
                             $fallback = $qFallback->pluck('id');
                             
                             if ($fallback->count() > $siblings->count()) {
                                 $siblings = $fallback;
                             }

                            $targetIds = $targetIds->merge($siblings);
                        }
                    }
                }
            }

            $targetIds = $targetIds->unique();
            $batchJobNumbers = []; // Moved outside to be request-scoped but shared across loop
            foreach ($targetIds as $jobId) {
                // Load job with related data for material issue creation
                $job = JobSchedule::with(['jobAdvice.rooms.rentalProduct.rentalComponents', 'building'])->find($jobId);
                
                if (!$job) continue;

                $jobType = strtolower(trim($job->type ?? ''));
                if (in_array($jobType, ['remove', 'remove_free', 'remove free'], true)) {
                    $skippedCount++;
                    $skippedRemoveCount++;
                    continue;
                }

                // Validation: Only for new_job or scheduled
                if (!in_array($job->status, ['new_job', 'scheduled', 'assign_material'])) {
                    $skippedCount++;
                    continue;
                }

                // If already assign_material, we might be adding more rooms OR it's a retry
                // But generally document number is generated once.
                
                if ($job->status !== 'assign_material') {
                    // Determine document type for Job Number generation
                    $type = strtolower($job->type);
                    $jaType = strtolower($job->jobAdvice->type ?? '');
                    
                    $docType = 'job_schedule';
                    if ($type === 'install' || $type === 'install_free') {
                        $docType = ($jaType === 'install_free' || $type === 'install_free') ? 'installation_free' : 'installation_report';
                    } elseif (str_contains($type, 'service')) {
                        $docType = 'customer_service_report';
                    } elseif (str_contains($type, 'remove')) {
                        $docType = ($type === 'remove_free' || $type === 'remove free' || $jaType === 'remove_free') ? 'remove_free' : 'remove';
                    }

                    // Generate NEW Job Number for this batch (or reuse if generated earlier in this loop for same context)
                    // We'll use a local cache for this request to keep batch jobs together
                    $batchKey = ($job->job_advice_id ?? 'manual') . '_' . ($job->building_id ?? '0') . '_' . $job->type . '_' . ($job->schedule_date ? $job->schedule_date->format('Y-m-d') : 'nodate');

                    if (isset($batchJobNumbers[$batchKey])) {
                        $sharedJobNumber = $batchJobNumbers[$batchKey];
                    } else {
                        $sharedJobNumber = $documentNumberService->generate(
                            $docType,
                            null,
                            $job->building_id,
                            $job->jobAdvice?->contract_id ?? null,
                            $job->jobAdvice?->quotation_id ?? null,
                            null,
                            null
                        );
                        $batchJobNumbers[$batchKey] = $sharedJobNumber;
                    }

                    // Update Job Schedule
                    $job->update([
                        'job_number' => $sharedJobNumber,
                        'status' => 'assign_material',
                        'assign_date' => now()->toDateString(),
                        'updated_by' => Auth::id()
                    ]);

                    // Create JobAssignSchedule (WITHOUT TEAM)
                    $jobAssignSchedule = \App\Models\JobAssignSchedule::create([
                        'job_schedule_id' => $job->id,
                        'team_id' => null, // No team assigned yet
                        'assigned_by' => Auth::id(),
                        'assigned_date' => now()->toDateString(),
                        'status' => 'assigned',
                        'notes' => 'Auto-created via Material Assign (No Team)',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                } else {
                    // If already exists, find the existing assign schedule
                    $jobAssignSchedule = \App\Models\JobAssignSchedule::where('job_schedule_id', $job->id)
                        ->whereNull('team_id')
                        ->first();
                     
                     if (!$jobAssignSchedule) {
                         // Should not happen if logic is correct, but create one just in case
                         $jobAssignSchedule = \App\Models\JobAssignSchedule::create([
                            'job_schedule_id' => $job->id,
                            'team_id' => null,
                            'assigned_by' => Auth::id(),
                            'assigned_date' => now()->toDateString(),
                            'status' => 'assigned',
                            'notes' => 'Auto-created via Material Assign (No Team) - Recursive',
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                     }
                }

                // Auto-create Material Issue (using same method as assign_team)
                // Pass specificRoomIds for filtering
                $this->autoCreateMaterialIssue($jobAssignSchedule, $specificRoomIds);

                $successCount++;
            }

            if (!empty($specificRoomIds)) {
                $this->ensureMaterialAssignForSelectedRooms($specificRoomIds, $batchJobNumbers);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil memproses {$successCount} job. "
                    . ($skippedRemoveCount > 0 ? "({$skippedRemoveCount} job remove dilewati karena tidak perlu material assign) " : "")
                    . (($skippedCount - $skippedRemoveCount) > 0 ? "(" . ($skippedCount - $skippedRemoveCount) . " job dilewati karena status tidak sesuai)" : ""),
                'success' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Bulk Material Assign Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses material assign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Unassign Material
     * Revert status from 'assign_material' to 'new_job' and delete related material records
     */
    public function bulkUnassignMaterial(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:job_schedules,id',
            'room_ids' => 'nullable|array',
            'room_ids.*' => 'integer'
        ]);

        if (empty($request->ids) && empty($request->room_ids)) {
             return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data yang dipilih.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $successCount = 0;
            $skippedCount = 0;

            // Determine target Job IDs.
            $targetIds = collect();
            $roomIds = $request->room_ids ?? [];

            if ($request->has('room_ids') && !empty($request->room_ids)) {
                // room_ids are JobScheduleRoom IDs - convert to Job IDs
                $jobIdsFromRooms = \App\Models\JobScheduleRoom::whereIn('id', $request->room_ids)
                    ->pluck('job_schedule_id')
                    ->unique()
                    ->toArray();
                
                $targetIds = collect($jobIdsFromRooms);
            } else {
                $targetIds = collect($request->ids);
                
                // Expand selection to include siblings (same job number/advice)
                foreach ($request->ids as $requestId) {
                    $job = JobSchedule::find($requestId);
                    if ($job && $job->job_advice_id) {
                         // Primary: Job Number
                         $q = JobSchedule::where('job_number', $job->job_number)
                            ->whereNull('deleted_at');
                         $siblings = $q->pluck('id');
                         
                         // Fallback: Advice ID (Always check for split-jobs)
                         $qFallback = JobSchedule::where('job_advice_id', $job->job_advice_id)
                            ->where('type', $job->type)
                            ->whereNull('deleted_at');
                         
                         // STRICT BUILDING FILTER
                         if ($job->building_id) {
                             $qFallback->where('building_id', $job->building_id);
                         }
                            
                         $fallback = $qFallback->pluck('id');
                         
                         if ($fallback->count() > $siblings->count()) {
                             $siblings = $fallback;
                         }

                        $targetIds = $targetIds->merge($siblings);
                    }
                }
            }
            
            $targetIds = $targetIds->unique();

            foreach ($targetIds as $jobId) {
                $job = JobSchedule::find($jobId);
                
                if (!$job) continue;

                // Only revert if status is 'assign_material'
                if ($job->status !== 'assign_material') {
                    $skippedCount++;
                    continue;
                }

                // Delete related records: JobAssignMaterialIssue -> MaterialIssue -> JobAssignSchedule
                $assignSchedules = \App\Models\JobAssignSchedule::where('job_schedule_id', $job->id)
                    ->get();

                foreach ($assignSchedules as $assign) {
                    // Find all material issues linked to this assignment
                    $jobAssignMaterials = \App\Models\JobAssignMaterialIssue::where('job_assign_schedule_id', $assign->id)->get();

                    foreach ($jobAssignMaterials as $jam) {
                        $mi = $jam->materialIssue;
                        if (!$mi) {
                            $jam->delete();
                            continue;
                        }

                        // Find linked InventoryIssuing
                        $invIssuing = \App\Models\InventoryIssuing::where('reference_no', $mi->issue_number)->first();

                        if (!empty($roomIds)) {
                            // Filter items by room names (because JobAssignMaterialIssue doesn't have room_id)
                            $roomNames = \App\Models\JobScheduleRoom::whereIn('id', $roomIds)->pluck('room_name')->toArray();
                            
                            // Delete specific items matching room names in MaterialIssue
                            $mi->items()->whereIn('room_name', $roomNames)->delete();

                            // Sync change to InventoryIssuing if exists and still pending
                            if ($invIssuing && $invIssuing->status === 'pending') {
                                $invIssuing->items()->whereIn('room_name', $roomNames)->delete();
                                
                                // Cleanup InventoryIssuing if no items left
                                if ($invIssuing->items()->count() === 0) {
                                    $invIssuing->delete();
                                }
                            }

                            // Cleanup MaterialIssue header if no items left
                            if ($mi->items()->count() === 0) {
                                $mi->delete(); // header
                                $jam->delete(); // link
                            }
                        } else {
                            // Full delete if no specific rooms
                            if ($invIssuing && $invIssuing->status === 'pending') {
                                $invIssuing->items()->delete();
                                $invIssuing->delete();
                            }
                            
                            $mi->items()->delete();
                            $mi->delete();
                            $jam->delete();
                        }
                    }

                    // Only delete JobAssignSchedule if no more material issues left for this job
                    $remainingIssues = \App\Models\JobAssignMaterialIssue::where('job_assign_schedule_id', $assign->id)->count();
                    if ($remainingIssues === 0) {
                        $assign->delete();
                    }
                }

                // Only Reset Job Schedule status if all material issues are gone
                // AND either no specific rooms or all rooms in job were unassigned
                $remainingJobIssues = \App\Models\JobAssignMaterialIssue::whereHas('jobAssignSchedule', function($q) use ($job) {
                    $q->where('job_schedule_id', $job->id);
                })->count();

                if ($remainingJobIssues === 0) {
                    $job->update([
                        'job_number' => null,
                        'status' => 'new_job',
                        'updated_by' => Auth::id()
                    ]);
                }

                $successCount++;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil membatalkan material assign untuk {$successCount} job. " . ($skippedCount > 0 ? "({$skippedCount} job dilewati karena status bukan 'assign_material')" : ""),
                'success' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Bulk Unassign Material Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membatalkan material assign: ' . $e->getMessage()
            ], 500);
        }
    }


    // Alias for existing undoneJob if needed via API/Action
    public function unpostBA(Request $request, $id)
    {
         return $this->undoneJob($request, JobSchedule::findOrFail($id));
    }

    public function create()
    {
        $job_advices = \App\Models\JobAdvice::with(['customer', 'contract'])
            ->whereIn('status', ['approved', 'submitted'])
            ->orderBy('created_at', 'desc')
            ->get();
        $buildings = Building::orderBy('nama_gedung')->get();
        $contracts = Contract::with(['customer'])->orderBy('contract_number')->get();
        $rooms = \App\Models\MasterRoom::orderBy('room_name')->get();
        $technicians = User::where('is_active', true)->where('roles', 'technician')->orderBy('name')->get();

        return view('operational.job-schedules.create', compact('job_advices', 'buildings', 'contracts', 'rooms', 'technicians'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_advice_id' => 'required|exists:job_advices,id',
            'type' => 'required|in:install,service,maintenance,removal,trial',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'nullable|exists:master_rooms,id',
            'schedule_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'assigned_technician_id' => 'nullable|exists:users,id',
            'internal_notes' => 'nullable|string',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled,pending',
            'period' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:50',
            'day' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:10',
            'district' => 'nullable|string|max:100',
            'sub_district' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get job advice details with rooms
            $jobAdvice = \App\Models\JobAdvice::with(['customer', 'contract', 'rooms.contractRoom.room'])->find($request->job_advice_id);
            
            if (!$jobAdvice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job advice not found'
                ], 404);
            }

            // MOM13: Create ONE job schedule per contract/quotation (NOT per room)
            // All rooms are accessed via job_advice_id -> jobAdvice->rooms
            // This change allows:
            // - 1 quotation with 3 rooms = 1 install_free job schedule
            // - 1 contract with 3 rooms = 1 install job schedule + 1 service job schedule (per period)
            
            $baseJobNumber = JobSchedule::withTrashed()->count() + 1;
            $rooms = $jobAdvice->rooms;
            
            // Build room list for internal_notes
            $roomNames = $rooms->pluck('room_name')->filter()->toArray();
            $roomListNote = count($roomNames) > 0 
                ? "\n[Rooms: " . implode(', ', $roomNames) . "] (" . count($roomNames) . " rooms)"
                : '';
            
            // Normalize type: if Job Advice type is "install_free", use "install" for Job Schedule
            // (Job Schedule enum only allows: install, service, maintenance, removal, trial)
            // The display will show "Install Free" via JobSchedule->display_type accessor
            $jobScheduleType = $request->type;
            if (strtolower($jobAdvice->type ?? '') === 'install_free' && strtolower($request->type) === 'install') {
                $jobScheduleType = 'install'; // Keep as "install" for enum consistency
            }
            
            // Generate job number with shared document-number rules.
            $jobNumber = $this->generateJobNumber($jobScheduleType, $jobAdvice);
            
            // MOM13: Get building for auto-fill location data
            $building = \App\Models\Building::with(['district', 'subdistrict'])->find($request->building_id);
            
            // Create SINGLE job schedule for all rooms
            $job_schedule = JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => $jobScheduleType,
                'job_advice_id' => $request->job_advice_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id, // Can be null - rooms accessed via job_advice_id
                'schedule_date' => $request->schedule_date,
                'expected_date' => $request->expected_date,
                'assigned_technician_id' => $request->assigned_technician_id,
                'internal_notes' => ($request->internal_notes ?? '') . $roomListNote,
                'status' => $request->status,
                'company_name' => $jobAdvice->customer->name ?? null,
                'contract_number' => $jobAdvice->contract->contract_number ?? null,
                'quotation_number' => $jobAdvice->quotation->quotation_number ?? null,
                'period' => $request->service_frequency ?? $request->period,
                'reference_number' => $request->reference_number ?? $jobAdvice->job_advice_number ?? null,
                'day' => $request->day,
                'postal_code' => $request->postal_code ?? $building?->kode_pos ?? $building?->postal_code ?? null,
                'district' => $request->district ?? $building?->district?->name ?? null,
                'sub_district' => $request->sub_district ?? $building?->subdistrict?->name ?? null,
                'service_frequency' => $request->service_frequency,
                'service_period_type' => $request->service_period_type ?? 'monthly',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Calculate service interval if service frequency is provided
            if ($request->service_frequency) {
                $job_schedule->calculateServiceInterval();
            }
            
            $linkColumn = null;
            $installTypes = ['install', 'Install', 'Install Free', 'install_free'];
            if (in_array($request->type, $installTypes)) {
                $linkColumn = 'install_job_schedule_id';
            } elseif ($request->type === 'service') {
                $linkColumn = 'service_job_schedule_id';
            } elseif (in_array($request->type, ['remove', 'remove_free', 'remove free'])) {
                $linkColumn = 'remove_job_schedule_id';
            }

            $this->syncJobScheduleRoomsFromJobAdvice($job_schedule, $jobAdvice, $linkColumn);

            \Log::info("MOM13: Created 1 job schedule ({$jobScheduleType}) from Job Advice {$jobAdvice->job_advice_number} for " . count($roomNames) . " rooms");
            
            // MOM13: If this is an INSTALL job (not install_free), also create FIRST SERVICE at the same time
            // "service pertama itu di buat bersama dengan install"
            $firstServiceSchedule = null;
            $installTypes = ['install', 'Install'];
            $isInstallFree = strtolower($jobAdvice->type ?? '') === 'install_free';
            
            if (in_array($jobScheduleType, $installTypes) && !$isInstallFree) {
                $firstServiceSchedule = $this->createFirstServiceWithInstall($job_schedule, $jobAdvice, $request);
            }

            $response = [
                'status' => 'success',
                'message' => 'Job schedule created successfully for ' . count($roomNames) . ' room(s)',
                'data' => $job_schedule->load(['jobAdvice.customer', 'jobAdvice.rooms', 'building', 'room', 'assignedTechnician']),
                'count' => 1,
                'rooms_count' => count($roomNames)
            ];
            
            if ($firstServiceSchedule) {
                $response['message'] .= ' + First service job created';
                $response['first_service'] = $firstServiceSchedule->load(['jobAdvice.customer', 'building']);
                $response['count'] = 2;
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error("Error creating job schedule: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating job schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(JobSchedule $jobSchedule)
    {
        // STUDY CASE B1: Auto-create JobScheduleRoom if not exists (for backward compatibility)
        // Only if it doesn't have rooms AND it's a type that supports rooms (not manual/standalone without rooms)
        if ($jobSchedule->job_advice_id && $jobSchedule->jobScheduleRooms->isEmpty()) {
            $linkColumn = match (strtolower((string) $jobSchedule->type)) {
                'install', 'install_free', 'install free' => 'install_job_schedule_id',
                'service', 'service_first', 'service_routine', 'csr' => 'service_job_schedule_id',
                'remove', 'remove_free', 'remove free' => 'remove_job_schedule_id',
                default => null,
            };

            $this->syncJobScheduleRoomsFromJobAdvice($jobSchedule, $jobSchedule->jobAdvice, $linkColumn);
            // Reload job schedule to get newly created rooms
            $jobSchedule->refresh();
        }
        
        $jobSchedule->load([
            'jobAdvice.customer',
            'jobAdvice.contract.quotation', // Load quotation for quotation_number
            'jobAdvice.rooms.contractRoom.room.building', // Load rooms with building for rental & team tab
            'jobAdvice.rooms.quotationRoom.room.building', // Load quotation rooms with building
            'jobAdvice.rooms.rentalProduct', // Load rental product
            'building',
            'room',
            'assignedTechnician',
            'jobAssignSchedules.team',
            'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.product', // Load material issues
            'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.team',
            'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.warehouse',
            'jobReports',
            'createdBy',
            'updatedBy',
            // STUDY CASE B1 & B2: Load job schedule rooms and their assignments
            'jobScheduleRooms.jobAdviceRoom.contractRoom.room.building',
            'jobScheduleRooms.jobAdviceRoom.quotationRoom.room.building',
            'jobScheduleRooms.jobAdviceRoom.rentalProduct',
            'jobScheduleRooms.rentals.jobAdviceRoom.rentalProduct',
            'jobScheduleRooms.jobAdviceRoom.jobAdvice', // MOM: Eager load JobAdvice for Change Rental check in accessor
            'jobScheduleRooms.roomAssignment.team',
            'jobScheduleRooms.roomAssignment.jobAssignSchedule.team',
            'jobScheduleRooms.materialReturn',
            'jobScheduleRooms.room'
        ]);
        
        // STUDY CASE B2: Get teams for per-room assignment dropdown
        $teams = \App\Models\Team::orderBy('team_name')->get();
        
        // STUDY CASE B1: Get warehouses for material return
        $warehouses = \App\Models\Warehouse::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // STUDY CASE B1: Get material returns for this job schedule
        $materialReturns = \App\Models\MaterialReturn::where('job_schedule_id', $jobSchedule->id)
            ->with([
                'items.product',
                'warehouse',
                'team',
                'jobScheduleRoom',
                'approvedBy',
                'returnedBy',
                'createdBy'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // MOM: Determine if we're in Job View mode and need to aggregate sibling Material Issues
        $viewMode = request()->get('view_mode', 'job');
        $siblingJobIds = [$jobSchedule->id];
        
        if ($viewMode === 'job' && $jobSchedule->job_advice_id) {
            // Once a job number exists, job detail must aggregate only the same visit/job number.
            // Falling back to JA/building/type grouping before assignment keeps unassigned jobs usable.
            $siblingsQuery = \App\Models\JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                ->where('building_id', $jobSchedule->building_id)
                ->where('type', $jobSchedule->type);

            if ($jobSchedule->job_number) {
                $siblingsQuery->where('job_number', $jobSchedule->job_number);
            } else {
                if ($jobSchedule->period !== null) {
                    $siblingsQuery->where('period', $jobSchedule->period);
                } else {
                    $siblingsQuery->whereNull('period');
                }
            }
                
            $siblingJobIds = $siblingsQuery->pluck('id')->toArray();
        }
        
        // Get all material issues related to this job schedule (and siblings in Job View) via job assign schedules
        $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($siblingJobIds) {
            $q->whereIn('job_schedule_id', $siblingJobIds);
        })->whereExists(function ($query) {
            $query->select(\DB::raw(1))
                ->from('inventory_issuings')
                ->whereColumn('inventory_issuings.reference_no', 'material_issues.issue_number')
                ->whereNull('inventory_issuings.deleted_at');
        })->with(['product.productType', 'team', 'warehouse'])->get();
        
        // Get material issue items (detailed breakdown) with product and packaging info
        $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($siblingJobIds) {
            $q->whereIn('job_schedule_id', $siblingJobIds);
        })->whereHas('materialIssue', function ($materialIssueQuery) {
            $materialIssueQuery->whereExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('inventory_issuings')
                    ->whereColumn('inventory_issuings.reference_no', 'material_issues.issue_number')
                    ->whereNull('inventory_issuings.deleted_at');
            });
        })->with([
            'materialIssue.team',
            'materialIssue.warehouse',
            'product.productType',
            'product.packagingSize'
        ])->get();

        $materialCompletionService = app(\App\Services\Operational\JobMaterialCompletionService::class);
        $materialIssueItems->each(function ($item) use ($materialCompletionService, $jobSchedule) {
            $item->effective_usage_status = $materialCompletionService->getEffectiveItemStatus($item, $jobSchedule);
        });
        
        // Get serial numbers for materials in this job schedule
        // For remove job: Get serial numbers from Unit On Wall (units to be removed)
        // For other jobs: Get serial numbers from material issue items
        if (in_array(strtolower($jobSchedule->type), ['remove', 'remove_free', 'remove free'])) {
            // Remove job: Get serial numbers from Unit On Wall
            $jobAdvice = $jobSchedule->jobAdvice;
            if ($jobAdvice && $jobAdvice->customer_id) {
                $jobSchedule->loadMissing([
                    'jobScheduleRooms.rentals.jobAdviceRoom',
                    'jobScheduleRooms.jobAdviceRoom',
                ]);

                // Load job advice rooms for filtering
                $jobAdvice->load(['rooms.contractRoom', 'rooms.quotationRoom']);

                $assignedRoomIds = $jobSchedule->jobScheduleRooms
                    ->pluck('room_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $assignedRentalIds = $jobSchedule->jobScheduleRooms
                    ->flatMap(function ($scheduleRoom) {
                        return $scheduleRoom->rentals
                            ->map(fn ($rentalLink) => $rentalLink->jobAdviceRoom?->rental_product_id)
                            ->push($scheduleRoom->jobAdviceRoom?->rental_product_id);
                    })
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $roomIds = $assignedRoomIds->isNotEmpty()
                    ? $assignedRoomIds->all()
                    : $jobAdvice->rooms->map(function($jr) {
                    if ($jr->room_id) return $jr->room_id;
                    if ($jr->contractRoom) return $jr->contractRoom->room_id;
                    if ($jr->quotationRoom) return $jr->quotationRoom->room_id;
                    return null;
                    })->filter()->unique()->toArray();
                
                // MOM: Also filter by rental_id to ensure we only target the specific items for this job
                // This prevents showing other units in the same room that are not part of this removal
                $rentalIds = $assignedRentalIds->all();
                
                // Build query for Unit On Wall
                $unitOnWallQuery = \App\Models\UnitOnWall::where('customer_id', $jobAdvice->customer_id)
                    ->where('building_id', $jobSchedule->building_id);
                
                $activeUnitOnWallStatuses = ['active', 'installed', 'on_wall', 'on wall', 'onwall'];

                // MOM: If job is already done, we must include 'removed' status units 
                // because the auto-remove process has already run.
                if (in_array(strtolower($jobSchedule->status), ['completed', 'done_job', 'done job'])) {
                    $unitOnWallQuery->whereIn('status', array_merge($activeUnitOnWallStatuses, ['removed']));
                } else {
                    $unitOnWallQuery->whereIn('status', $activeUnitOnWallStatuses);
                }
                
                // Filter by rooms if available
                if (!empty($roomIds)) {
                    $unitOnWallQuery->whereIn('room_id', $roomIds);
                }
                
                // Filter by rental_ids if available
                if (!empty($rentalIds)) {
                    $unitOnWallQuery->whereIn('rental_id', $rentalIds);
                }

                // MOM: Strict Install-Remove Mirroring
                $installJobSns = [];
                try {
                    $installJob = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)
                        ->whereIn('type', ['install', 'Install', 'install_free', 'Install Free'])
                        ->whereIn('status', ['completed', 'done_job'])
                        ->first();
                        
                    if ($installJob) {
                        $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($installJob) {
                            $q->where('job_schedule_id', $installJob->id);
                        })->pluck('issue_number')->toArray();
                        
                        if (!empty($materialIssues)) {
                            // Using DB join for absolute certainty in SQL execution
                            $installJobSns = \DB::table('inventory_issuing_items')
                                ->join('inventory_issuings', 'inventory_issuing_items.inventory_issuing_id', '=', 'inventory_issuings.id')
                                ->whereIn('inventory_issuings.reference_no', $materialIssues)
                                ->whereNotNull('inventory_issuing_items.serial_number_id')
                                ->pluck('inventory_issuing_items.serial_number_id')
                                ->toArray();
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to fetch Install Job SNs: " . $e->getMessage());
                }

                // MOM: Strict Install-Remove Mirroring
                // If we found specific SNs that were installed via the related install job in this JA, 
                // we should ONLY show those SNs for the remove job. This prevents "stray" SNs from other
                // jobs in the same room from appearing.
                if (!empty($installJobSns)) {
                    $displayUnitStatuses = in_array(strtolower($jobSchedule->status), ['completed', 'done_job', 'done job'])
                        ? array_merge($activeUnitOnWallStatuses, ['removed'])
                        : $activeUnitOnWallStatuses;

                    $unitOnWalls = \App\Models\UnitOnWall::whereIn('serial_number_id', $installJobSns)
                        ->where('customer_id', $jobAdvice->customer_id)
                        ->where('building_id', $jobSchedule->building_id)
                        ->whereIn('status', $displayUnitStatuses)
                        ->when(!empty($roomIds), fn ($query) => $query->whereIn('room_id', $roomIds))
                        ->when(!empty($rentalIds), fn ($query) => $query->whereIn('rental_id', $rentalIds))
                        ->with(['serialNumber.masterProduct.productType', 'serialNumber.warehouse'])
                        ->get();
                    
                    \Log::info("Job {$jobSchedule->job_number}: Filtered UnitOnWall by " . count($installJobSns) . " SNs from related Install Job.");
                } else {
                    // Fallback to room-based if no install job found (e.g. manually created remove job)
                    $unitOnWalls = $unitOnWallQuery
                        ->whereNotNull('serial_number_id')
                        ->with(['serialNumber.masterProduct.productType', 'serialNumber.warehouse'])
                        ->get();
                    
                    \Log::info("Job {$jobSchedule->job_number}: No related Install Job SNs found. Falling back to room-based UnitOnWall query.");
                }
                
                // Extract serial numbers from unit on walls
                $serialNumbers = $unitOnWalls->map(function($unit) {
                    return $unit->serialNumber;
                })->filter(); // Remove null values
                
                // If no serial_number_id found, try using serial_number string field directly
                if ($serialNumbers->isEmpty()) {
                    $unitOnWallsDirect = $unitOnWallQuery
                        ->whereNotNull('serial_number')
                        ->get();
                    
                    // Try to find serial numbers by serial_number string
                    $serialNumberStrings = $unitOnWallsDirect->pluck('serial_number')->filter()->unique()->toArray();
                    if (!empty($serialNumberStrings)) {
                        $serialNumbers = \App\Models\SerialNumber::whereIn('serial_number', $serialNumberStrings)
                            ->with(['masterProduct.productType', 'warehouse'])
                            ->get();
                    }
                }
            } else {
                $serialNumbers = collect(); // Empty collection if no customer
            }
        } else {
            // Other jobs: Get serial numbers from inventory_issuing_items that were registered during material check via apps
            // Only show serial numbers that were actually scanned/registered during material verification
            $materialIssueNumbers = $materialIssues->pluck('issue_number')->toArray();
            
            $serialNumbers = \App\Models\SerialNumber::whereHas('inventoryIssuingItems.inventoryIssuing', function($q) use ($materialIssueNumbers) {
                    $q->whereIn('reference_no', $materialIssueNumbers)
                      ->whereIn('status', ['processed', 'received', 'sent']); // Only from verified issuings
                })
                ->whereNotNull('serial_number') // Ensure serial_number is not null
                ->with(['masterProduct.productType', 'warehouse'])
                ->get();
            
            // If no serial numbers found via inventory_issuing_items, return empty collection
            // This ensures we only show serial numbers that were registered during material check
            if ($serialNumbers->isEmpty()) {
                $serialNumbers = collect();
            }
        }

        $serialNumbers = collect($serialNumbers)
            ->filter()
            ->unique(fn ($serialNumber) => strtoupper(trim((string) $serialNumber->serial_number)) ?: $serialNumber->id)
            ->values();
        
        // Get team location history for this job schedule (and siblings in Job View)
        $teamLocations = \App\Models\JobTeamLocation::whereIn('job_schedule_id', $siblingJobIds)
            ->with(['user', 'team'])
            ->orderBy('recorded_at', 'desc')
            ->get();
        
        // Get photos for this job schedule from JobPhoto table (and siblings in Job View)
        $jobPhotos = \App\Models\JobPhoto::whereIn('job_schedule_id', $siblingJobIds)
            ->with(['uploadedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Debug logging
        // Also get photos from JobReport (for backward compatibility and verification photos)
        $jobReports = \App\Models\JobReport::whereIn('job_schedule_id', $siblingJobIds)
            ->where(function ($query) {
                $query->whereNotNull('photos')
                    ->orWhereNotNull('photo_pic')
                    ->orWhereNotNull('signature_file');
            })
            ->get();
        
        // Buat lookup: job_schedule_id -> room_name (dari sibling JS yang memiliki report ini)
        // Dipakai untuk melabeli PIC Photo & Digital Signature dengan room yang benar
        $jsRoomNameMap = [];
        foreach ($siblingJobIds as $sibJsId) {
            $sibJsr = \App\Models\JobScheduleRoom::where('job_schedule_id', $sibJsId)->first();
            if ($sibJsr) {
                $jsRoomNameMap[$sibJsId] = $sibJsr->room_name;
            }
        }

        // Merge photos from JobReport into JobPhotos collection
        foreach ($jobReports as $report) {
            // Dapatkan room name berdasarkan job_schedule_id report
            $reportRoomName = $jsRoomNameMap[$report->job_schedule_id] ?? null;
            $roomSuffix = $reportRoomName ? ' - Room: ' . $reportRoomName : '';

            $photos = is_array($report->photos) ? $report->photos : json_decode($report->photos, true);
            if (is_array($photos)) {
                foreach ($photos as $photoPath) {
                    // Check if photo already exists in JobPhoto
                    $exists = $jobPhotos->contains(function($photo) use ($photoPath) {
                        return $photo->photo_path === $photoPath;
                    });
                    
                    if (!$exists) {
                        $createdAt = $report->completed_at ?? $report->created_at;
                        if ($createdAt && is_string($createdAt)) {
                            $createdAt = \Carbon\Carbon::parse($createdAt);
                        }
                        $jobPhotos->push(new \App\Models\JobPhoto([
                            'job_schedule_id' => $report->job_schedule_id,
                            'photo_path' => $photoPath,
                            'photo_type' => 'Work Photo',
                            'description' => 'Foto dari Job Report' . $roomSuffix,
                            'uploaded_by' => $report->technician_id,
                            'created_at' => $createdAt ?? now(),
                        ]));
                    }
                }
            }
            
            // Add PIC photo from JobReport
            if ($report->photo_pic) {
                $exists = $jobPhotos->contains(function($photo) use ($report) {
                    return $photo->photo_path === $report->photo_pic;
                });
                
                if (!$exists) {
                    $createdAt = $report->completed_at ?? $report->created_at;
                    if ($createdAt && is_string($createdAt)) {
                        $createdAt = \Carbon\Carbon::parse($createdAt);
                    }
                    $jobPhotos->push(new \App\Models\JobPhoto([
                        'job_schedule_id' => $report->job_schedule_id,
                        'job_schedule_room_id' => \App\Models\JobScheduleRoom::where('job_schedule_id', $report->job_schedule_id)
                            ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                            ->orderByDesc('completed_at')
                            ->orderByDesc('updated_at')
                            ->value('id'),
                        'photo_path' => $report->photo_pic,
                        'photo_type' => 'PIC Photo',
                        'description' => 'PIC Photo' . $roomSuffix, // Sertakan room name agar view bisa identifikasi room
                        'uploaded_by' => $report->technician_id,
                        'created_at' => $createdAt ?? now(),
                    ]));
                }
            }
            
            // Add signature from JobReport
            if ($report->signature_file) {
                $exists = $jobPhotos->contains(function($photo) use ($report) {
                    return $photo->photo_path === $report->signature_file;
                });
                
                if (!$exists) {
                    $createdAt = $report->signature_at ?? $report->completed_at ?? $report->created_at;
                    if ($createdAt && is_string($createdAt)) {
                        $createdAt = \Carbon\Carbon::parse($createdAt);
                    }
                    $jobPhotos->push(new \App\Models\JobPhoto([
                        'job_schedule_id' => $report->job_schedule_id,
                        'job_schedule_room_id' => \App\Models\JobScheduleRoom::where('job_schedule_id', $report->job_schedule_id)
                            ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                            ->orderByDesc('completed_at')
                            ->orderByDesc('updated_at')
                            ->value('id'),
                        'photo_path' => $report->signature_file,
                        'photo_type' => 'Digital Signature',
                        'description' => 'Digital Signature' . $roomSuffix, // Sertakan room name agar view bisa identifikasi room
                        'uploaded_by' => $report->technician_id,
                        'created_at' => $createdAt ?? now(),
                    ]));
                }
            }
        }
        
        // Sort by created_at descending
        $jobPhotos = $jobPhotos->sortByDesc(function($photo) {
            return $photo->created_at ?? now();
        })->values();

        // For re-work after unpost, keep historical files in storage/database but
        // only show the latest active PIC/signature snapshot per job room in the
        // main Photos tab so users do not see duplicate verification rows.
        $latestSnapshotKeys = [];
        $jobPhotos = $jobPhotos->filter(function ($photo) use (&$latestSnapshotKeys) {
            if (!in_array($photo->photo_type, ['PIC Photo', 'Digital Signature'])) {
                return true;
            }

            $snapshotKey = implode('|', [
                $photo->photo_type,
                $photo->job_schedule_room_id ?: 'js-' . ($photo->job_schedule_id ?? '0'),
            ]);

            if (isset($latestSnapshotKeys[$snapshotKey])) {
                return false;
            }

            $latestSnapshotKeys[$snapshotKey] = true;

            return true;
        })->values();
        
        // Return JSON for AJAX requests
        if (request()->ajax() || request()->expectsJson() || request()->header('Accept') === 'application/json') {
            // Format schedule_date to ensure it's a string
            $jobScheduleData = $jobSchedule->toArray();
            if (isset($jobScheduleData['schedule_date']) && $jobScheduleData['schedule_date']) {
                $jobScheduleData['schedule_date'] = $jobSchedule->schedule_date ? $jobSchedule->schedule_date->format('Y-m-d') : null;
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $jobScheduleData
            ]);
        }
        
        // Fetch all JobScheduleRoom records related to the same JobAdvice and same Type for a Unified View
        $query = \App\Models\JobScheduleRoom::whereHas('jobSchedule', function($q) use ($jobSchedule) {
            $q->where('job_advice_id', $jobSchedule->job_advice_id)
              ->where('type', $jobSchedule->type);
        });

        // MOM: Apply filtering based on view mode parameters
        $viewMode = request('view_mode', 'job');
        $filterRoomId = request('room_id');
        $filterBuildingId = request('building_id');

        if ($viewMode === 'room' && $filterRoomId) {
            // Room Mode: Only show the specific room
            $query->where('room_id', $filterRoomId);
        } elseif ($filterBuildingId) {
            // Job Mode with specific building (though usually job schedule is already per building)
            // Ensure we only show rooms for this building (via MasterRoom relationship)
            $query->whereHas('room', function($q) use ($filterBuildingId) {
                $q->where('building_id', $filterBuildingId);
            });
        }
        
        $relatedJobScheduleRooms = $query->with([
            'jobSchedule',
            'jobAdviceRoom.contractRoom.room.building',
            'jobAdviceRoom.quotationRoom.room.building',
            'jobAdviceRoom.rentalProduct',
            'rentals.jobAdviceRoom.rentalProduct',
            'roomAssignment.team',
            'roomAssignment.jobAssignSchedule.team',
            'materialReturn',
            'room'
        ])
        ->get();

        // [MOM] Aggregate BA Files for all related sibling jobs to ensure they all show up in Job View
        $baFiles = \App\Models\JobScheduleBaFile::whereIn('job_schedule_id', $siblingJobIds)
            ->with(['uploader'])
            ->get();

        // [FIX Masalah 1 & 3] Ambil semua JobReport dari sibling JS, di-index by job_schedule_id
        // Digunakan oleh Basic Info untuk menampilkan PIC Photo & TTD per room (multi-room job)
        $allJobReportsPerJS = \App\Models\JobReport::whereIn('job_schedule_id', $siblingJobIds)
            ->get()
            ->keyBy('job_schedule_id');

        // [VALIDASI INVOICE] Check if job has an active invoice (Contract-based)
        $hasActiveInvoice = $this->hasActiveInvoice($jobSchedule);

        // [UNIT DETAILS] Fetch unit technical details from job_schedule_units and job_reports
        $unitDetailsFromUnits = \DB::table('job_schedule_units')
            ->whereIn('job_schedule_id', $siblingJobIds)
            ->get();

        $unitDetailsFromReports = \DB::table('job_reports')
            ->whereIn('job_schedule_id', $siblingJobIds)
            ->get();

        // Merge logic: Prioritize job_schedule_units, fallback to job_reports
        $mergedDetails = collect();
        $processedMacs = [];
        $processedReportIds = [];

        // 1. Process from job_schedule_units (Record scanned by technician)
        foreach ($unitDetailsFromUnits as $unit) {
            $snapshot = $unit->device_snapshot ? (json_decode($unit->device_snapshot, true) ?: []) : [];
            $unit->snapshot = is_array($snapshot) ? $snapshot : [];
            $mergedDetails->push($unit);
            if ($unit->mac) $processedMacs[] = $unit->mac;
        }

        // 2. Process from job_reports (Record at verification step)
        foreach ($unitDetailsFromReports as $report) {
            // Check if this report's mac is already processed via job_schedule_units
            $macAlreadyProcessed = $report->unit_mac_address && in_array($report->unit_mac_address, $processedMacs);
            
            if (!$macAlreadyProcessed) {
                // Determine room name
                $roomName = '-';
                
                // Fallback to JobScheduleRoom since JobReport doesn't have a direct room link
                $jsr = \App\Models\JobScheduleRoom::where('job_schedule_id', $report->job_schedule_id)->first();
                $roomName = $jsr ? ($jsr->room_name ?? ($jsr->jobAdviceRoom?->room_name ?? '-')) : '-';

                // Prepare snapshot from JobReport columns or JSON
                $snapshot = $report->device_snapshot ? (json_decode($report->device_snapshot, true) ?: []) : [];
                if (!is_array($snapshot)) {
                    $snapshot = [
                        'liquidLevel' => $report->device_liquid_level,
                        'fanLevel' => $report->device_fan_level,
                        'status' => $report->device_online_status,
                    ];
                }

                $mergedDetails->push((object)[
                    'room_name' => $roomName,
                    'mac' => $report->unit_mac_address ?: '-',
                    'unit_serial_number' => $report->unit_serial_number ?: '-',
                    'device_type' => $report->job_type ?: 'Unknown',
                    'snapshot' => $snapshot,
                    'scanned_at' => $report->completed_at ?? $report->created_at,
                    'notes' => $report->notes,
                    'source' => 'JobReport'
                ]);
                
                if ($report->unit_mac_address) {
                    $processedMacs[] = $report->unit_mac_address;
                }
            }
        }

        $unitDetails = $mergedDetails;

        return view('operational.job-schedules.show', compact(
            'jobSchedule', 
            'materialIssues', 
            'materialIssueItems',
            'serialNumbers', 
            'teamLocations', 
            'jobPhotos', 
            'teams', 
            'warehouses', 
            'materialReturns',
            'baFiles',
            'relatedJobScheduleRooms',
            'viewMode',
            'filterRoomId',
            'filterBuildingId',
            'hasActiveInvoice',
            'allJobReportsPerJS',
            'unitDetails'
        ));
    }

    public function edit(JobSchedule $jobSchedule)
    {
        $jobSchedule->load(['jobAdvice.customer', 'building', 'room', 'assignedTechnician']);
        
        $job_advices = \App\Models\JobAdvice::with(['customer', 'contract'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
        $buildings = Building::orderBy('nama_gedung')->get();
        $rooms = \App\Models\MasterRoom::orderBy('room_name')->get();
        $technicians = User::where('is_active', true)->where('roles', 'technician')->orderBy('name')->get();

        return view('operational.job-schedules.edit', compact('jobSchedule', 'job_advices', 'buildings', 'rooms', 'technicians'));
    }

    public function update(Request $request, JobSchedule $jobSchedule)
    {
        // Check if job is terminated - cannot be edited or assigned
        if ($jobSchedule->status === 'terminated') {
            \Log::warning("Attempt to update terminated job: {$jobSchedule->id}");
            return response()->json([
                'status' => 'error',
                'message' => 'Job ini sudah di-terminate karena Contract Termination dan tidak dapat diubah.'
            ], 403);
        }

        // Check if this is a partial update (only schedule_date, internal_notes, or status change)
        // Partial update: only has schedule_date, internal_notes, or status, but NOT job_advice_id, type, building_id, etc.
        $hasPartialFields = $request->has('schedule_date') || $request->has('internal_notes') || $request->has('status') || $request->has('assigned_technician_id') || $request->has('team_id');
        $hasFullFields = $request->has('job_advice_id') || $request->has('type') || $request->has('building_id');
        
        // Partial update: has partial fields but NOT full fields (for form submission like "Done Job" button or AJAX)
        $isPartialUpdate = $hasPartialFields && !$hasFullFields;

        if ($isPartialUpdate) {
            // Partial update validation (only for schedule_date, internal_notes, and status)
            $validator = Validator::make($request->all(), [
                'schedule_date' => 'nullable|date',
                'internal_notes' => 'nullable|string',
                'status' => 'nullable|in:scheduled,in_progress,completed,cancelled,pending,new_job,assign_team,assign_material,barang_dipersiapkan,barang_siap_diambil,barang_diambil,teknisi_tiba_dilokasi,meninggalkan_lokasi,teknisi_sedang_pengerjaan,teknisi_selesai_pengerjaan,done_job,suspend,dpf,undone',
                'assigned_technician_id' => 'nullable|exists:users,id',
                'team_id' => 'nullable|exists:teams,id'
            ]);

            if ($validator->fails()) {
                \Log::error("Validation failed for JobSchedule {$jobSchedule->id}", $validator->errors()->toArray());
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // MOM15: Allow schedule_date change as long as status is not done_job or completed
            if ($request->has('schedule_date') && $request->schedule_date != $jobSchedule->schedule_date) {
                $finalStatuses = ['done_job', 'completed', 'suspend', 'dpf', 'cancelled'];
                if (in_array($jobSchedule->status, $finalStatuses)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Maaf sudah tidak dapat merubah schedule date karena pekerjaan sudah selesai atau dibatalkan'
                    ], 422);
                }
            }

            // Perform partial update
            $updateData = [];
            if ($request->has('schedule_date')) {
                $updateData['schedule_date'] = $request->schedule_date;
            }
            if ($request->has('internal_notes')) {
                $updateData['internal_notes'] = $request->internal_notes;
            }
            if ($request->has('assigned_technician_id')) {
                $updateData['assigned_technician_id'] = $request->assigned_technician_id;
            }

            // MOM: Handle Team Assignment (Create JobAssignSchedule)
            if ($request->has('team_id') && $request->team_id) {
                // NEW VALIDATION: Check if we can assign team
                $validation = $this->validateMakeAssignTeam($jobSchedule);
                if ($validation !== true) {
                    return response()->json($validation, 422);
                }

                // Determine siblings for propagation
                // Priority 1: Use target_job_ids from frontend confirmation dialog
                // Priority 2: Auto-discover siblings based on job_advice_id
                $siblingJobSchedules = collect([$jobSchedule]);
                
                if ($request->has('target_job_ids') && is_array($request->target_job_ids) && count($request->target_job_ids) > 0) {
                    // Use explicit target IDs (excluding self)
                    $siblingIds = array_filter($request->target_job_ids, fn($id) => $id != $jobSchedule->id);
                    if (!empty($siblingIds)) {
                        $siblings = \App\Models\JobSchedule::whereIn('id', $siblingIds)->get();
                        $siblingJobSchedules = $siblingJobSchedules->merge($siblings);
                    }
                } elseif ($jobSchedule->job_advice_id) {
                    // Fallback: Auto-discover siblings (same JA + Building + Type + Period)
                    // Remove the whereNull('job_number') condition to allow re-assignment
                    $siblingsQuery = \App\Models\JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                        ->where('building_id', $jobSchedule->building_id)
                        ->where('type', $jobSchedule->type)
                        ->where('id', '!=', $jobSchedule->id);
                        
                    if ($jobSchedule->period !== null) {
                        $siblingsQuery->where('period', $jobSchedule->period);
                    } else {
                        $siblingsQuery->whereNull('period');
                    }
                    
                    $siblings = $siblingsQuery->get();
                    $siblingJobSchedules = $siblingJobSchedules->merge($siblings);
                }

                $documentNumberService = new DocumentNumberService();
                $documentTypeMap = [
                    'install' => 'installation_report',
                    'install_free' => 'installation_free',
                    'service' => 'customer_service_report',
                    'service_first' => 'customer_service_report',
                    'service_routine' => 'customer_service_report',
                    'remove' => 'remove',
                    'remove_free' => 'remove_free',
                    'remove free' => 'remove_free',
                    'maintenance' => 'job_schedule',
                    'extra' => 'job_schedule',
                    'change' => 'job_schedule',
                    'complain' => 'job_schedule',
                ];

                // MOM: Shared Job Number Logic
                // Group jobs by date. Jobs on the same date should share the same Job Number.
                // 1. Prepare list of jobs with their effective dates
                $jobsByDate = [];
                
                foreach ($siblingJobSchedules as $job) {
                    // Determine effective date: if it's the primary job being updated, use request date, else use existing date
                    $isPrimary = ($job->id == $jobSchedule->id);
                    $effectiveDate = $isPrimary && $request->has('schedule_date') 
                        ? $request->schedule_date 
                        : ($job->schedule_date instanceof \DateTime ? $job->schedule_date->format('Y-m-d') : $job->schedule_date);
                    
                    // Normalize date format
                    if ($effectiveDate) {
                        $effectiveDate = substr($effectiveDate, 0, 10);
                        if (!isset($jobsByDate[$effectiveDate])) {
                            $jobsByDate[$effectiveDate] = collect();
                        }
                        $jobsByDate[$effectiveDate]->push($job);
                    }
                }
                
                ksort($jobsByDate); // Process in date order
                
                // 2. Process each date group
                foreach ($jobsByDate as $date => $groupJobs) {
                    // A. Check External DB for existing Job Number (Same Context + Same Team + Same Date)
                    $refJob = $groupJobs->first();
                    $targetTeamId = $request->team_id;

                    // NEW VALIDATION: Check if we can assign team
                    $validation = $this->validateMakeAssignTeam($refJob);
                    if ($validation !== true) {
                        return response()->json($validation, 422);
                    }

                    $excludeIds = $siblingJobSchedules->pluck('id')->toArray();
                    
                    $existingExternalJob = \App\Models\JobSchedule::where('job_advice_id', $refJob->job_advice_id)
                        ->where('building_id', $refJob->building_id)
                        ->where('type', $refJob->type)
                        ->whereDate('schedule_date', $date)
                        ->whereNotIn('id', $excludeIds)
                        ->whereHas('jobAssignSchedules', function($q) use ($targetTeamId) {
                            $q->where('team_id', $targetTeamId)
                              ->where('status', '!=', 'cancelled');
                        })
                        ->whereNotNull('job_number')
                        ->first();
                        
                    $sharedJobNumber = $existingExternalJob ? $existingExternalJob->job_number : null;
                    
                    // B. If not found externally, check internal batch for valid existing number (Reuse case)
                    if (!$sharedJobNumber) {
                        foreach ($groupJobs as $job) {
                            // Check if this job ALREADY fits the criteria (Same Team + Same Date)
                            // We check the DB state of this job before we modify it
                            $hasSameTeam = $job->jobAssignSchedules()
                                ->where('team_id', $targetTeamId)
                                ->where('status', '!=', 'cancelled')
                                ->exists();

                            // Use loose comparison for date string vs object
                            $jobDate = $job->schedule_date instanceof \DateTime ? $job->schedule_date->format('Y-m-d') : $job->schedule_date;
                            $isSameDate = substr($jobDate, 0, 10) === $date;
                            
                            if ($job->job_number && $hasSameTeam && $isSameDate) {
                                $sharedJobNumber = $job->job_number;
                                break;
                            }
                        }
                    }

                    // C. If still not found, generate NEW
                    if (!$sharedJobNumber) {
                        $documentType = $documentTypeMap[$refJob->type] ?? 'job_schedule';
                        $sharedJobNumber = $documentNumberService->generate(
                            $documentType,
                            null,
                            $refJob->building_id,
                            $refJob->jobAdvice?->contract_id ?? null,
                            $refJob->jobAdvice?->quotation_id ?? null,
                            null,
                            null
                        );
                    }
                    
                    // 3. Apply updates to all jobs in this date group
                    foreach ($groupJobs as $sibling) {
                        // Protect custom assignments
                        $hasCustom = $sibling->jobScheduleRooms()->whereHas('roomAssignment', function($q) {
                             $q->where('is_custom', true)->where('status', '!=', 'cancelled');
                        })->exists();

                        if ($hasCustom) {
                             continue;
                        }

                        // Cancel existing assignments
                        \App\Models\JobAssignSchedule::where('job_schedule_id', $sibling->id)
                             ->where('status', '!=', 'cancelled')
                             ->where('team_id', '!=', $request->team_id)
                             ->update(['status' => 'cancelled']);
                        
                        // Create Assignment
                        $jobAssignSchedule = \App\Models\JobAssignSchedule::firstOrCreate(
                            [
                                'job_schedule_id' => $sibling->id,
                                'team_id' => $request->team_id,
                            ],
                            [
                                'assigned_by' => auth()->id(),
                                'assigned_date' => now()->toDateString(),
                                'status' => 'assigned',
                                'created_by' => auth()->id()
                            ]
                        );

                        if ($jobAssignSchedule->status === 'cancelled') {
                            $jobAssignSchedule->update(['status' => 'assigned']);
                        }

                        app(\App\Services\Warehouse\InventoryIssuingService::class)
                            ->syncRoomAssignmentsForJobSchedule(
                                $sibling,
                                (int) $request->team_id,
                                (int) $jobAssignSchedule->id,
                                $jobAssignSchedule->assigned_date?->toDateString()
                            );

                        // Update Job Attributes. Do not overwrite an existing job number:
                        // re-assigning a team must not mutate historical IF/RF/IR numbers.
                        $siblingUpdates = [
                            'status' => 'assign_team',
                            'assign_date' => now()->toDateString(),
                            'job_number' => $sibling->job_number ?: $sharedJobNumber
                        ];
                        
                        $sibling->update($siblingUpdates);

                        // Auto-create Material Issue items
                        $jobType = strtolower($sibling->type ?? '');
                        if (!in_array($jobType, ['remove', 'remove_free', 'remove free', 'check'])) {
                            $this->autoCreateMaterialIssue($jobAssignSchedule);
                        }
                    }
                }
                
                // Update primary job status
                $updateData['status'] = 'assign_team';
            }
            
            // Handle status change with auto-creation logic
            $oldStatus = $jobSchedule->status;
            if ($request->has('status')) {
                $transitionValidation = $this->validateWebCompletionTransition($jobSchedule, $request->status);
                if ($transitionValidation !== true) {
                    return response()->json($transitionValidation, 422);
                }

                $updateData['status'] = $request->status;
                
                // MOM13: Auto-fill dates based on status changes
                $newStatus = $request->status;
                
                // 1. Assign Date: ketika status berubah ke assign_team
                // This is now handled in the propagation loop above for team assignments.
                // Keep this check for cases where status is set to assign_team without team_id being present in request.
                if ($newStatus === 'assign_team' && !$jobSchedule->assign_date) {
                    $updateData['assign_date'] = now()->toDateString();
                }
                
                // 2. Issue Date: ketika status berubah ke barang_diambil
                if ($newStatus === 'barang_diambil' && !$jobSchedule->issue_date) {
                    $updateData['issue_date'] = now()->toDateString();
                }
                
                // 3. BA Date & BA Number: ketika status berubah ke completion statuses
                $completionStatuses = ['completed', 'done_job', 'suspend', 'dpf'];
                if (in_array($newStatus, $completionStatuses)) {
                    // Set BA Date jika belum ada
                    if (!$jobSchedule->ba_date) {
                        $updateData['ba_date'] = now()->toDateString();
                    }
                    
                    // Generate BA Number jika belum ada
                    if (!$jobSchedule->ba_number) {
                        $updateData['ba_number'] = $this->generateBANumber($jobSchedule);
                    }
                }

                // 4. Transaction Code for Suspend/DPF
                if (in_array($newStatus, ['suspend', 'dpf'])) {
                     if (!$jobSchedule->job_reference_number) {
                         $codeType = $newStatus === 'suspend' ? 'SUS' : 'DPF';
                         $updateData['job_reference_number'] = $this->generateTransactionCode($jobSchedule, $codeType);
                     }
                }
                
                // 5. Auto-fill postal_code, district, sub_district from building if empty
                if ($jobSchedule->building_id && $jobSchedule->building) {
                    $building = $jobSchedule->building;
                    if (!$jobSchedule->postal_code && ($building->kode_pos || $building->postal_code)) {
                        $updateData['postal_code'] = $building->kode_pos ?? $building->postal_code;
                    }
                    if (!$jobSchedule->district && $building->district) {
                        $updateData['district'] = $building->district->name;
                    }
                    if (!$jobSchedule->sub_district && $building->subdistrict) {
                        $updateData['sub_district'] = $building->subdistrict->name;
                    }
                }
            }
            $updateData['updated_by'] = Auth::id();

            if (isset($updateData['status']) && in_array($updateData['status'], ['completed', 'done_job'], true)) {
                $this->completeScheduleRoomsFromWeb($jobSchedule);
            }

            $jobSchedule->update($updateData);

            if (
                isset($updateData['status'])
                && $oldStatus !== 'cancelled'
                && $updateData['status'] === 'cancelled'
                && in_array(strtolower((string) $jobSchedule->type), ['remove_free', 'remove free'], true)
            ) {
                $cancelledRemoveJob = $jobSchedule->fresh();
                $restoredSerials = app(\App\Services\Operational\CancelledRemoveFreeSerialRestoreService::class)
                    ->restore($cancelledRemoveJob, true);
                if ($restoredSerials->isNotEmpty()) {
                    \Log::info("Restored {$restoredSerials->count()} SN(s) to In Use after cancelling Remove Free {$jobSchedule->job_number}.");
                }

                $createdCsrCount = $this->ensureFirstServiceAfterCancelledRemoveFree($cancelledRemoveJob);
                if ($createdCsrCount > 0) {
                    \Log::info("Auto-created {$createdCsrCount} CSR schedule(s) after cancelling Remove Free {$jobSchedule->job_number}.");
                }
            }
            
            // AUTO-EXECUTE LOGIC when status changes to completion statuses
            // MOM13: completed, done_job, suspend (selesai tanpa tagih), dpf (selesai tetap tagih)
            $completionStatuses = ['done_job', 'completed', 'suspend', 'dpf', 'cancelled'];
            
            // FIX BUG 1: If the user clicked "Done Job" from the Web UI, but they clicked on a row where the first 
            // representative JobSchedule was already 'completed' (by the mobile app), we MUST STILL propagate 
            // the completion status to the OTHER sibling JobSchedules that are not yet completed!
            if (isset($updateData['status']) && in_array($updateData['status'], $completionStatuses)) {
                
                // [FIX BUG 1] Propagate completion status to ALL sibling JobSchedules
                $schedulesToComplete = collect([$jobSchedule]);
                if (in_array($updateData['status'], ['completed', 'done_job']) && $jobSchedule->job_number) {
                    $siblings = \App\Models\JobSchedule::where('job_number', $jobSchedule->job_number)
                        ->where('id', '!=', $jobSchedule->id)
                        ->whereNotIn('status', $completionStatuses)
                        ->get();
                        
                    foreach ($siblings as $sibling) {
                        $siblingValidation = $this->validateWebCompletionTransition($sibling, $updateData['status']);
                        if ($siblingValidation !== true) {
                            \Log::warning('Skipping sibling completion propagation because status flow is not ready', [
                                'job_schedule_id' => $sibling->id,
                                'job_number' => $sibling->job_number,
                                'current_status' => $sibling->status,
                                'target_status' => $updateData['status'],
                                'reason' => $siblingValidation['message'] ?? null,
                            ]);
                            continue;
                        }

                        $siblingUpdates = ['status' => $updateData['status'], 'updated_by' => Auth::id()];
                        if (isset($updateData['ba_date'])) $siblingUpdates['ba_date'] = $updateData['ba_date'];
                        if (isset($updateData['ba_number'])) $siblingUpdates['ba_number'] = $updateData['ba_number'];
                        if (isset($updateData['completed_at'])) $siblingUpdates['completed_at'] = $updateData['completed_at'];
                        
                        $this->completeScheduleRoomsFromWeb($sibling);
                        $sibling->update($siblingUpdates);
                        $schedulesToComplete->push($sibling);
                    }
                    
                }

                // Process Auto-Execution Logic securely for all completed schedules
                $jobAdvice = $jobSchedule->jobAdvice;
                
                if ($jobAdvice) {
                    // AUTO-GENERATE INVOICE when job status changes to completion statuses (Berdasarkan BRD)
                    // MOM8: Skip invoice untuk install free/trial job
                    // MOM13: completed, done_job, dpf trigger invoice. suspend = no invoice
                    $invoiceTriggerStatuses = ['completed', 'done_job', 'dpf']; // Suspend TIDAK trigger invoice
                    if (in_array($updateData['status'], $invoiceTriggerStatuses)) {
                        if ($jobAdvice->contract_id) {
                            // Check if this is install free/trial job
                            $isInstallFreeOrTrial = $this->isInstallFreeOrTrial($jobSchedule, $jobAdvice);
                            
                            if (!$isInstallFreeOrTrial) {
                                $this->triggerAutoInvoiceGeneration($jobAdvice->contract_id);
                            }
                        }
                    }
                    
                    // Process Invoice & Remove Job generation for every completed schedule.
                    // The Remove Free generator is idempotent per JobAdviceRoom, so do not stop
                    // after the first IF room: grouped/web completions can finish multiple IFs.
                    
                    foreach ($schedulesToComplete as $completedSchedule) {
                        $completedSchedule->refresh();
                        app(\App\Services\Operational\JobMaterialCompletionService::class)
                            ->finalizeForCompletedJob($completedSchedule);
                        
                        // AUTO-CREATE UNIT ON WALL (Runs per room / JobSchedule)
                        $unitOnWallCreated = false;
                        $installTypes = ['install', 'Install', 'Install Free', 'install_free', 'service', 'Service', 'change_rental', 'change rental'];
                        if (in_array($completedSchedule->type, $installTypes) && $completedSchedule->areAllRoomsCompleted()) {
                            $unitOnWallCreated = $this->autoCreateUnitOnWall($completedSchedule, $jobAdvice);
                        }
                        
                        // AUTO-CREATE REMOVE JOB only if Unit On Wall was successfully created
                        if (in_array($completedSchedule->type, $installTypes) && $jobAdvice && $jobAdvice->remove_date && $unitOnWallCreated) {
                            $isInstallFree = false;
                            if ($jobAdvice && $jobAdvice->type) {
                                $jaTypeLower = strtolower(trim($jobAdvice->type));
                                $isInstallFree = ($jaTypeLower === 'install_free' || $jaTypeLower === 'install free');
                            }
                            
                            if ($isInstallFree) {
                                // Generate per completed room, not per JA. A previous room may already
                                // have its remove job while another room finishes later.
                                $jobAdviceController = new \App\Http\Controllers\Marketing\JobAdviceController();
                                $jobAdviceController->generateRemoveFreeSchedule($jobAdvice, $completedSchedule);
                            } else if (!$isInstallFree) {
                            }
                        }
                        
                        // MOM13: When FIRST SERVICE completes, generate ALL remaining services at once
                        if (in_array($completedSchedule->type, ['service', 'service_first'])) {
                            if ($completedSchedule->period == 1) {
                                $this->generateAllRemainingServices($completedSchedule, $jobAdvice);
                            }
                            $this->checkAndCreateRemoveJobAfterAllServicesComplete($completedSchedule, $jobAdvice);
                            $this->autoUpdateUnitOnWallLastServiceDate($completedSchedule, $jobAdvice);
                        }
                    }
                    
                    // AUTO-REMOVE/HIDE UNIT ON WALL when remove job (Return) is completed
                    // "ketika remove job sudah selesai, unit on wall akan otomatis ter-hide/removed"
                    $removeTypes = ['remove', 'remove_free', 'remove free'];
                    if (
                        in_array(strtolower($jobSchedule->type), $removeTypes, true)
                        && in_array($updateData['status'], ['completed', 'done_job'], true)
                    ) {
                        $this->autoRemoveUnitOnWall($jobSchedule, $jobAdvice);
                        
                        // MOM8: Skip invoice untuk remove job dari install free/trial
                        // Check if the original install job was install free/trial
                        $isInstallFreeOrTrial = $this->isRemoveJobFromInstallFree($jobSchedule, $jobAdvice);
                        
                        if ($jobAdvice->contract_id && !$isInstallFreeOrTrial) {
                            $this->triggerAutoInvoiceGeneration($jobAdvice->contract_id);
                        }
                    } else if ($jobAdvice->contract_id) {
                        // MOM15: Real-time Invoice Trigger for Service Jobs
                        // If this job is part of a contract, check if this completion triggers an invoice
                        // (i.e., if this was the last job needed for the billing period)
                        try {
                            $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
                            $invoiceService->attemptAutoInvoiceForContract($jobAdvice->contract_id);
                        } catch (\Exception $e) {
                            \Log::error("Failed to trigger real-time invoice check: " . $e->getMessage());
                        }
                    }
                }
            }

            // If request expects JSON, return JSON response
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job schedule updated successfully',
                    'data' => $jobSchedule->fresh()
                ]);
            }
            
            // Otherwise redirect back (for form submission like "Done Job" button)
            return redirect()->route('operational.job-schedules.show', $jobSchedule->id)
                ->with('success', 'Job schedule updated successfully');
        }

        // Full update validation
        $validator = Validator::make($request->all(), [
            'job_advice_id' => 'sometimes|required|exists:job_advices,id',
            'type' => 'sometimes|required|in:install,service,maintenance,removal,trial',
            'building_id' => 'sometimes|required|exists:buildings,id',
            'room_id' => 'nullable|exists:master_rooms,id',
            'schedule_date' => 'sometimes|required|date',
            'expected_date' => 'nullable|date',
            'assigned_technician_id' => 'nullable|exists:users,id',
            'internal_notes' => 'nullable|string',
            'status' => 'sometimes|required|in:scheduled,in_progress,completed,cancelled,pending,new_job,assign_team,assign_material,barang_dipersiapkan,barang_siap_diambil,barang_diambil,teknisi_tiba_dilokasi,meninggalkan_lokasi,teknisi_sedang_pengerjaan,teknisi_selesai_pengerjaan,done_job,suspend,dpf',
            'period' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:50',
            'day' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:10',
            'district' => 'nullable|string|max:100',
            'sub_district' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get job advice details (Use existing ID if not in request)
            $jobAdviceId = $request->input('job_advice_id', $jobSchedule->job_advice_id);
            $jobAdvice = \App\Models\JobAdvice::with(['customer', 'contract'])->find($jobAdviceId);
            
            $oldStatus = $jobSchedule->status;
            $newStatus = $request->input('status', $jobSchedule->status);
            $transitionValidation = $this->validateWebCompletionTransition($jobSchedule, $newStatus);
            if ($transitionValidation !== true) {
                return response()->json($transitionValidation, 422);
            }
            
            // MOM13: Prepare auto-fill data based on status changes
            $autoFillData = [];
            
            // 1. Assign Date
            if ($newStatus === 'assign_team' && !$jobSchedule->assign_date) {
                $autoFillData['assign_date'] = now()->toDateString();
            }
            
            // 2. Issue Date  
            if ($newStatus === 'barang_diambil' && !$jobSchedule->issue_date) {
                $autoFillData['issue_date'] = now()->toDateString();
            }
            
            // 3. BA Date & BA Number
            $completionStatuses = ['completed', 'done_job', 'suspend', 'dpf'];
            if (in_array($newStatus, $completionStatuses)) {
                if (!$jobSchedule->ba_date) {
                    $autoFillData['ba_date'] = now()->toDateString();
                }
                if (!$jobSchedule->ba_number) {
                    $autoFillData['ba_number'] = $this->generateBANumber($jobSchedule);
                }
            }
            
            // 4. Auto-fill location data from building if empty
            // Only if building_id is provided or exists
            $buildingId = $request->input('building_id', $jobSchedule->building_id);
            if ($buildingId) {
                $building = \App\Models\Building::with(['district', 'subdistrict'])->find($buildingId);
                if ($building) {
                    if (!$request->postal_code && !$jobSchedule->postal_code && ($building->kode_pos || $building->postal_code)) {
                        $autoFillData['postal_code'] = $building->kode_pos ?? $building->postal_code;
                    }
                    if (!$request->district && !$jobSchedule->district && $building->district) {
                        $autoFillData['district'] = $building->district->name;
                    }
                    if (!$request->sub_district && !$jobSchedule->sub_district && $building->subdistrict) {
                        $autoFillData['sub_district'] = $building->subdistrict->name;
                    }
                }
            }
            
            // Update with fallbacks to existing values
            if (in_array($newStatus, ['completed', 'done_job'], true)) {
                $this->completeScheduleRoomsFromWeb($jobSchedule);
            }

            $jobSchedule->update(array_merge([
                'type' => $request->input('type', $jobSchedule->type),
                'job_advice_id' => $jobAdviceId,
                'building_id' => $buildingId,
                'room_id' => $request->input('room_id', $jobSchedule->room_id),
                'schedule_date' => $request->input('schedule_date', $jobSchedule->schedule_date),
                'expected_date' => $request->input('expected_date', $jobSchedule->expected_date),
                'assigned_technician_id' => $request->input('assigned_technician_id', $jobSchedule->assigned_technician_id),
                'internal_notes' => $request->input('internal_notes', $jobSchedule->internal_notes),
                'status' => $newStatus,
                'company_name' => $jobAdvice->customer->name ?? $jobSchedule->company_name,
                'contract_number' => $jobAdvice->contract->contract_number ?? $jobSchedule->contract_number,
                'period' => $request->input('period', $jobSchedule->period),
                'reference_number' => $request->input('reference_number', $jobSchedule->reference_number),
                'day' => $request->input('day', $jobSchedule->day),
                'postal_code' => $request->input('postal_code', $jobSchedule->postal_code),
                'district' => $request->input('district', $jobSchedule->district),
                'sub_district' => $request->input('sub_district', $jobSchedule->sub_district),
                'updated_by' => Auth::id()
            ], $autoFillData));

            if (
                $oldStatus !== 'cancelled'
                && $newStatus === 'cancelled'
                && in_array(strtolower((string) $jobSchedule->type), ['remove_free', 'remove free'], true)
            ) {
                $cancelledRemoveJob = $jobSchedule->fresh();
                $restoredSerials = app(\App\Services\Operational\CancelledRemoveFreeSerialRestoreService::class)
                    ->restore($cancelledRemoveJob, true);
                if ($restoredSerials->isNotEmpty()) {
                    \Log::info("Restored {$restoredSerials->count()} SN(s) to In Use after cancelling Remove Free {$jobSchedule->job_number}.");
                }

                $createdCsrCount = $this->ensureFirstServiceAfterCancelledRemoveFree($cancelledRemoveJob);
                if ($createdCsrCount > 0) {
                    \Log::info("Auto-created {$createdCsrCount} CSR schedule(s) after cancelling Remove Free {$jobSchedule->job_number}.");
                }
            }


            // MOM: Handle Team Assignment (Linked to JobAssignSchedule)
            // Fix: Explicitly handle team_id from request which is sent by frontend but ignored by JobSchedule update
            if ($request->has('team_id') && $request->team_id) {
                $jobAssignSchedule = \App\Models\JobAssignSchedule::updateOrCreate(
                    ['job_schedule_id' => $jobSchedule->id],
                    [
                        'team_id' => $request->team_id,
                        'assigned_by' => Auth::id(),
                        'assigned_date' => now(), // Default to today
                        'status' => 'assigned',
                        'notes' => $request->internal_notes,
                        'created_by' => Auth::id(), // ignored on update
                        'updated_by' => Auth::id()
                    ]
                );

                app(\App\Services\Warehouse\InventoryIssuingService::class)
                    ->syncRoomAssignmentsForJobSchedule(
                        $jobSchedule,
                        (int) $request->team_id,
                        (int) $jobAssignSchedule->id,
                        $jobAssignSchedule->assigned_date?->toDateString()
                    );
                
                // Also update job schedule assign_date if needed
                if (!$jobSchedule->assign_date) {
                    $jobSchedule->update(['assign_date' => now()]);
                }

                $this->ensureAssignedJobNumber($jobSchedule, (int) $request->team_id);
            }

            // STUDY CASE: Propagate Team Assignment and Status to Sibling Jobs (Same JA + Building + Type)
            // "When assigned in Job View, it should propagate to all rooms/siblings"
            if ($request->has('assigned_technician_id') || $request->has('status') || $request->has('team_id')) {
                // Determine target siblings:
                // Option A: If 'target_job_ids' is provided (from confirmation dialog), strict propagation to those IDs only.
                // Option B: If NOT provided, use auto-discovery logic (default behavior).
                
                $siblings = collect();
                
                if ($request->has('target_job_ids') && is_array($request->target_job_ids) && count($request->target_job_ids) > 0) {
                     // Exclude the current job from target_job_ids (it's already updated above)
                     $siblingIds = array_filter($request->target_job_ids, fn($id) => $id != $jobSchedule->id);
                     $siblings = JobSchedule::whereIn('id', $siblingIds)->get();
                } else {
                     // Find siblings: Same Job Advice, Same Building, Same Type, Same Schedule Date
                     $siblings = JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                        ->where('building_id', $jobSchedule->building_id)
                        ->where('type', $jobSchedule->type) 
                        ->where('id', '!=', $jobSchedule->id) // Exclude self
                        ->whereDate('schedule_date', $jobSchedule->schedule_date) // Ensure same day
                        ->get();
                }

                $propagationData = [];
                if ($request->has('assigned_technician_id')) {
                    $propagationData['assigned_technician_id'] = $request->assigned_technician_id;
                }
                
                // Always propagate status when team is being assigned
                if ($request->has('status')) {
                    $propagationData['status'] = $request->status;
                } elseif ($request->has('team_id')) {
                    // If team is being assigned but no explicit status, set to assign_team
                    $propagationData['status'] = 'assign_team';
                }

                if (!empty($propagationData) || $request->has('team_id')) {
                    foreach ($siblings as $sibling) {
                        if (!empty($propagationData)) {
                            if (isset($propagationData['status'])) {
                                $siblingValidation = $this->validateWebCompletionTransition($sibling, $propagationData['status']);
                                if ($siblingValidation !== true) {
                                    \Log::warning('Skipping sibling status propagation because status flow is not ready', [
                                        'job_schedule_id' => $sibling->id,
                                        'job_number' => $sibling->job_number,
                                        'current_status' => $sibling->status,
                                        'target_status' => $propagationData['status'],
                                        'reason' => $siblingValidation['message'] ?? null,
                                    ]);
                                    continue;
                                }

                                if (in_array($propagationData['status'], ['completed', 'done_job'], true)) {
                                    $this->completeScheduleRoomsFromWeb($sibling);
                                }
                            }

                            $sibling->update($propagationData);
                        }
                        
                        // Propagate Team Assignment (JobAssignSchedule)
                        if ($request->has('team_id') && $request->team_id) {
                             $siblingJobAssignSchedule = \App\Models\JobAssignSchedule::updateOrCreate(
                                ['job_schedule_id' => $sibling->id],
                                [
                                    'team_id' => $request->team_id,
                                    'assigned_by' => Auth::id(),
                                    'assigned_date' => now(),
                                    'status' => 'assigned',
                                    'notes' => $request->internal_notes,
                                    'created_by' => Auth::id(),
                                    'updated_by' => Auth::id()
                                ]
                            );

                            app(\App\Services\Warehouse\InventoryIssuingService::class)
                                ->syncRoomAssignmentsForJobSchedule(
                                    $sibling,
                                    (int) $request->team_id,
                                    (int) $siblingJobAssignSchedule->id,
                                    $siblingJobAssignSchedule->assigned_date?->toDateString()
                                );
                            
                            // Also update sibling assign_date
                            if (!$sibling->assign_date) {
                                $sibling->update(['assign_date' => now()]);
                            }

                            $this->ensureAssignedJobNumber($sibling, (int) $request->team_id);
                        }
                        
                    }
                }
            }

            // AUTO-GENERATE INVOICE when job status changes to completion statuses (Berdasarkan BRD)
            // MOM8: Skip invoice untuk install free/trial job
            // MOM13: completed, done_job, dpf trigger invoice. suspend = no invoice
            $completionStatuses = ['completed', 'done_job', 'suspend', 'dpf'];
            $invoiceTriggerStatuses = ['completed', 'done_job', 'dpf']; // Suspend TIDAK trigger invoice
            if (!in_array($oldStatus, $completionStatuses) && in_array($request->status, $completionStatuses)) {
                // Only trigger invoice for completed, done_job, and dpf (NOT for suspend)
                if (in_array($request->status, $invoiceTriggerStatuses)) {
                    // Check if this is install free/trial job
                    $isInstallFreeOrTrial = $this->isInstallFreeOrTrial($jobSchedule, $jobAdvice);
                    
                    if ($jobAdvice->contract_id && !$isInstallFreeOrTrial) {
                        $this->triggerAutoInvoiceGeneration($jobAdvice->contract_id);
                    }
                } else {
                }
            }

                // AUTO-CREATE UNIT ON WALL when install job is completed
                // "untuk unit yang sudah terpasang akan otomatis terdata di unit-on-walls"
                // MOM8: Remove job hanya dibuat SETELAH Unit On Wall berhasil dibuat
                $unitOnWallCreated = false;
                $installTypes = ['install', 'Install', 'Install Free', 'install_free'];
                if (in_array($jobSchedule->type, $installTypes) && $jobSchedule->areAllRoomsCompleted()) {
                    $unitOnWallCreated = $this->autoCreateUnitOnWall($jobSchedule, $jobAdvice);
                }
                
                // MOM8: AUTO-CREATE REMOVE JOB only if Unit On Wall was successfully created
                // "remove job ada ketika memang terpasang. karena banyak kejadian trial tapi unit ga kepasang"
                // "harus setelah unit on wall terpasang baru generate remove job, jika unit on wall tidak ada maka jangan auto generate remove job"
                // UPDATE: Untuk Install Free → langsung create remove job setelah install selesai
                // Untuk Install biasa → remove job baru dibuat setelah semua service period selesai (di cek di service job completion)
                if (in_array($jobSchedule->type, $installTypes) && $jobAdvice && $jobAdvice->remove_date && $unitOnWallCreated) {
                    // Cek apakah ini Install Free atau Install biasa
                    // IMPORTANT: Check from jobAdvice->type, not jobSchedule->type
                    // Because jobSchedule->type is normalized to "install" for enum consistency
                    $isInstallFree = false;
                    if ($jobAdvice && $jobAdvice->type) {
                        $jaTypeLower = strtolower(trim($jobAdvice->type));
                        $isInstallFree = ($jaTypeLower === 'install_free' || $jaTypeLower === 'install free');
                    }
                    
                    if ($isInstallFree) {
                        // Install Free: Langsung create remove_free job using the correct method
                        $jobAdviceController = new \App\Http\Controllers\Marketing\JobAdviceController();
                        $jobAdviceController->generateRemoveFreeSchedule($jobAdvice, $jobSchedule);
                    } else {
                        // Install biasa: Remove job akan dibuat setelah semua service period selesai
                        // MOM13: First service sudah dibuat bersamaan dengan install job
                    }
                } elseif (in_array($jobSchedule->type, $installTypes) && $jobAdvice && $jobAdvice->remove_date && !$unitOnWallCreated) {
                } elseif (in_array($jobSchedule->type, $installTypes) && $jobAdvice && !$jobAdvice->remove_date) {
                    \Log::warning("Skipping remove job creation for install job {$jobSchedule->job_number} (type: {$jobSchedule->type}) because remove_date is not set in Job Advice {$jobAdvice->job_advice_number}");
                }
                
                // AUTO-REMOVE/HIDE UNIT ON WALL when remove job is completed
                // "ketika remove job sudah selesai, unit on wall akan otomatis ter-hide/removed"
                if (
                    in_array($jobSchedule->type, ['remove', 'remove_free', 'remove free'], true)
                    && in_array($newStatus, ['completed', 'done_job'], true)
                ) {
                    $this->autoRemoveUnitOnWall($jobSchedule, $jobAdvice);
                    
                    // MOM8: Skip invoice untuk remove job dari install free/trial
                    // Check if the original install job was install free/trial
                    $isInstallFreeOrTrial = $this->isRemoveJobFromInstallFree($jobSchedule, $jobAdvice);
                    
                    if ($jobAdvice->contract_id && !$isInstallFreeOrTrial) {
                        $this->triggerAutoInvoiceGeneration($jobAdvice->contract_id);
                    }
                }
                
                // MOM13: When FIRST SERVICE completes, generate ALL remaining services at once
                if (in_array($jobSchedule->type, ['service', 'service_first'])) {
                    if ($jobSchedule->period == 1) {
                        // First service completed - generate all remaining services
                        $this->generateAllRemainingServices($jobSchedule, $jobAdvice);
                    }
                    
                    // Check if this is the LAST service and create remove job
                    $this->checkAndCreateRemoveJobAfterAllServicesComplete($jobSchedule, $jobAdvice);
                    
                    // Auto-update last_service_date in UnitOnWall when service job is completed
                    $this->autoUpdateUnitOnWallLastServiceDate($jobSchedule, $jobAdvice);
                }
                
                // AUTO-REMOVE/HIDE UNIT ON WALL when remove job is completed
                // "ketika remove job sudah selesai, unit on wall akan otomatis ter-hide/removed"
                if (
                    in_array($jobSchedule->type, ['remove', 'remove_free', 'remove free'], true)
                    && in_array($newStatus, ['completed', 'done_job'], true)
                ) {
                    $this->autoRemoveUnitOnWall($jobSchedule, $jobAdvice);
                }

            return response()->json([
                'status' => 'success',
                'message' => 'Job schedule updated successfully',
                'data' => $jobSchedule->load(['jobAdvice.customer', 'building', 'room', 'assignedTechnician'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating job schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(JobSchedule $jobSchedule)
    {
        try {
            $jobSchedule->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Job schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting job schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Assignments for Sibling Jobs
     * Used for Team Assignment Confirmation Dialog
     */
    public function checkAssignments($id)
    {
        try {
            $jobSchedule = JobSchedule::with(['jobAssignSchedules.team', 'room'])->find($id);
            if (!$jobSchedule) {
                return response()->json(['status' => 'error', 'message' => 'Job not found'], 404);
            }
            
            // Find siblings
            $siblings = JobSchedule::with(['jobAssignSchedules.team', 'room', 'jobScheduleRooms.roomAssignment.team'])
                ->where('job_advice_id', $jobSchedule->job_advice_id)
                ->where('building_id', $jobSchedule->building_id)
                ->where('type', $jobSchedule->type)
                ->whereDate('schedule_date', $jobSchedule->schedule_date)
                ->get();
                
            $willAssign = [];
            $alreadyAssigned = [];
            
            // The team we are intending to assign
            $targetTeamId = request()->query('team_id');
            
            foreach ($siblings as $sibling) {
                // Check for custom assignment first (STUDY CASE B2)
                $customAssignment = $sibling->jobScheduleRooms->first()?->roomAssignment;
                $isCustom = $customAssignment ? $customAssignment->is_custom : false;
                
                // Get current assigned team
                $currentAssignment = $sibling->jobAssignSchedules()->where('status', '!=', 'cancelled')->orderBy('id', 'desc')->first();
                $currentTeamId = $currentAssignment ? $currentAssignment->team_id : null;
                $currentTeamName = $currentAssignment && $currentAssignment->team ? $currentAssignment->team->team_name : null;
                
                // Override with custom team if exists
                if ($isCustom && $customAssignment->team) {
                    $currentTeamId = $customAssignment->team_id;
                    $currentTeamName = $customAssignment->team->team_name;
                }

                $roomName = $sibling->room ? $sibling->room->room_name : ($sibling->room_name ?? 'Unknown Room');
                
                $item = [
                    'id' => $sibling->id,
                    'job_number' => $sibling->job_number,
                    'room_name' => $roomName,
                    'current_team_name' => $currentTeamName ?? 'Unassigned',
                    'current_team_id' => $currentTeamId,
                    'is_custom' => $isCustom
                ];
                
                // Classification Logic
                if ($isCustom) {
                    // Custom assignments are ALWAYS protected from global overwrite
                    $alreadyAssigned[] = $item;
                } elseif (!$currentTeamId) {
                    $willAssign[] = $item;
                } elseif ($targetTeamId && $currentTeamId == $targetTeamId) {
                    $willAssign[] = $item;
                } else {
                    $alreadyAssigned[] = $item;
                }
            }
            
            return response()->json([
                'status' => 'success',
                'will_assign' => $willAssign,
                'already_assigned' => $alreadyAssigned
            ]);
            
        } catch (\Exception $e) {
             return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if job has an active invoice (based on Contract and Period)
     * This logic is used to block Unpost BA / Undone Job
     */
    private function hasActiveInvoice(JobSchedule $jobSchedule)
    {
        $contractNumber = $jobSchedule->contract_number;
        $scheduleDate = $jobSchedule->schedule_date
            ? \Carbon\Carbon::parse($jobSchedule->schedule_date)
            : null;
        
        // Fallback to get contract from relations if column is null
        if (!$contractNumber) {
            if ($jobSchedule->jobAdvice && $jobSchedule->jobAdvice->contract) {
                $contractNumber = $jobSchedule->jobAdvice->contract->contract_number;
            } elseif ($jobSchedule->periodicJob && $jobSchedule->periodicJob->contract) {
                $contractNumber = $jobSchedule->periodicJob->contract->contract_number;
            }
        }

        if ($contractNumber) {
            $contract = \App\Models\Contract::with('billingGroup')->where('contract_number', $contractNumber)->first();

            // 1. Rental period invoice (period_invoice based)
            if ($contract && $contract->start_date && $scheduleDate) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $diffInMonths = $startDate->diffInMonths($scheduleDate);
                $periodName = "Period " . ($diffInMonths + 1);
                
                $hasInvoiceForPeriod = \App\Models\Invoice::where('contract_number', $contractNumber)
                    ->where('period_invoice', $periodName)
                    ->where('invoice_status', '!=', 'cancelled')
                    ->exists();
                    
                if ($hasInvoiceForPeriod) {
                    return true;
                }
            }

            // 2. Billing-group / monthly invoice (month-year based, period_invoice can be null)
            if ($scheduleDate) {
                $billingGroupId = $contract?->billingGroup?->id;

                $hasMonthlyInvoice = \App\Models\Invoice::where('invoice_status', '!=', 'cancelled')
                    ->whereMonth('invoice_date', $scheduleDate->month)
                    ->whereYear('invoice_date', $scheduleDate->year)
                    ->where(function ($query) use ($contractNumber, $billingGroupId) {
                        $query->where('contract_number', $contractNumber);

                        if ($billingGroupId) {
                            $query->orWhere('billing_group_id', $billingGroupId);
                        }
                    })
                    ->exists();

                if ($hasMonthlyInvoice) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Undone job - rollback from done_job status to undone
     * This clears ba_date and allows BA Date to be re-filled
     */
    public function undoneJob(Request $request, JobSchedule $jobSchedule)
    {
        try {
            DB::beginTransaction();
            
            // Only allow undone from done_job or completed status
            if (!in_array($jobSchedule->status, ['done_job', 'completed'])) {
                $msg = 'Undone hanya bisa dilakukan dari status done_job atau completed';
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            // [VALIDASI INVOICE] Check if job has an active invoice (Contract & Period based)
            if ($this->hasActiveInvoice($jobSchedule)) {
                $msg = 'Maaf, Job ini sudah memiliki Invoice yang aktif untuk periode kontrak ini. Silakan hapus atau batalkan invoice tersebut terlebih dahulu di menu Finance.';
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }
            
            // Store old status for logging
            $oldStatus = $jobSchedule->status;
            $oldBaDate = $jobSchedule->ba_date;
            
            // Cancel BA must not touch verification evidence. Job photos, PIC photo,
            // signature, JobReport.completed_at, and JobSchedule.completed_at are
            // historical proof of work and must stay visible after BA date correction.
            // The workflow is reopened by status + ba_date only.
            $jobSchedule->update([
                'status' => 'undone',
                'ba_date' => null,
                'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[UNDONE] Status changed from {$oldStatus} to undone. Previous BA Date: " . ($oldBaDate ? $oldBaDate->format('Y-m-d') : 'N/A') . ". User can now refill BA Date.",
                'updated_by' => Auth::id()
            ]);
            
            DB::commit();
            
            \Log::info("🔄 Job {$jobSchedule->job_number} status changed from {$oldStatus} to undone by user " . Auth::id());
            
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Status berhasil diubah menjadi Undone.']);
            }
            
            return redirect()->back()->with('success', 'Status berhasil diubah menjadi Undone. Anda sekarang dapat mengisi ulang BA Date.');
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ Error undone job: " . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    /**
     * MOM15: Update BA Date only (without changing status)
     * Requires permission: operational.job-schedules.ba-date
     * Cannot edit if invoice already exists for this job's contract period
     */
    public function updateBaDate(Request $request, JobSchedule $jobSchedule)
    {
        try {
            // Check permission
            $user = Auth::user();
            $hasBaDatePermission = $user && (
                $user->hasPermission('operational.job-schedules.ba-date.update') ||
                $user->hasPermission('operational.job-schedules.ba-date')
            );
            
            if (!$hasBaDatePermission) {
                $msg = 'Anda tidak memiliki permission untuk mengubah BA Date.';
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 403);
                }
                return redirect()->back()->with('error', $msg);
            }
            
            // Only allow editing BA Date for done_job, completed, atau undone (setelah Unpost BA, user bisa isi ulang BA date)
            if (!in_array($jobSchedule->status, ['done_job', 'completed', 'undone'])) {
                $msg = 'BA Date hanya bisa diubah untuk job yang sudah berstatus Done Job atau Completed.';
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }
            
            // Check if invoice exists
            if ($this->hasActiveInvoice($jobSchedule)) {
                $msg = 'BA Date tidak bisa diubah karena sudah ada Invoice aktif untuk periode kontrak ini. Silakan batalkan invoice terlebih dahulu.';
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }
            
            // Validate request
            $request->validate([
                'ba_date' => 'required|date'
            ]);
            
            $oldBaDate = $jobSchedule->ba_date;
            $newBaDate = $request->ba_date;
            
            DB::beginTransaction();
            
            $updatePayload = [
                'ba_date' => $newBaDate,
                'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[BA_DATE_EDIT] BA Date changed from " . ($oldBaDate ? \Carbon\Carbon::parse($oldBaDate)->format('Y-m-d') : 'N/A') . " to " . \Carbon\Carbon::parse($newBaDate)->format('Y-m-d') . " by User ID: " . Auth::id() . " at " . now()->format('Y-m-d H:i:s'),
                'updated_by' => Auth::id()
            ];

            if ($jobSchedule->status === 'undone') {
                $restoreStatus = 'done_job';
                if (preg_match_all('/\[UNDONE\] Status changed from ([a-zA-Z0-9_]+) to undone/', $jobSchedule->internal_notes ?? '', $matches) && !empty($matches[1])) {
                    $lastPreviousStatus = end($matches[1]);
                    if (in_array($lastPreviousStatus, ['done_job', 'completed'], true)) {
                        $restoreStatus = $lastPreviousStatus;
                    }
                }

                $updatePayload['status'] = $restoreStatus;
            }

            $jobSchedule->update($updatePayload);

            $jobSchedule->refresh();
            $shouldAttemptInvoiceGeneration = in_array($jobSchedule->status, ['done_job', 'completed', 'dpf'], true)
                && $jobSchedule->jobAdvice
                && $jobSchedule->jobAdvice->contract_id
                && !$this->hasActiveInvoice($jobSchedule);
            $invoiceContractId = $shouldAttemptInvoiceGeneration ? $jobSchedule->jobAdvice->contract_id : null;
            
            DB::commit();

            if ($invoiceContractId) {
                $this->triggerAutoInvoiceGeneration($invoiceContractId);
            }
            
            \Log::info("📅 Job {$jobSchedule->job_number} BA Date changed from " . ($oldBaDate ? \Carbon\Carbon::parse($oldBaDate)->format('Y-m-d') : 'N/A') . " to " . \Carbon\Carbon::parse($newBaDate)->format('Y-m-d') . " by user " . Auth::id());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success', 
                    'message' => 'BA Date berhasil diubah.',
                    'ba_date' => \Carbon\Carbon::parse($newBaDate)->format('Y-m-d')
                ]);
            }
            
            return redirect()->back()->with('success', 'BA Date berhasil diubah.');
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ Error updating BA Date: " . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal mengubah BA Date: ' . $e->getMessage());
        }
    }

    public function dashboard()
    {
        $total_jobs = JobSchedule::count();
        $scheduled_jobs = JobSchedule::where('status', 'scheduled')->count();
        $in_progress_jobs = JobSchedule::where('status', 'in_progress')->count();
        $completed_jobs = JobSchedule::where('status', 'completed')->count();
        $cancelled_jobs = JobSchedule::where('status', 'cancelled')->count();

        $today_jobs = JobSchedule::whereDate('schedule_date', today())->count();
        $this_week_jobs = JobSchedule::whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])->count();

        $recent_jobs = JobSchedule::with(['company', 'building'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $jobs_by_type = JobSchedule::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get();

        $jobs_by_status = [
            'scheduled' => $scheduled_jobs,
            'in_progress' => $in_progress_jobs,
            'completed' => $completed_jobs,
            'cancelled' => $cancelled_jobs
        ];

        return view('operational.dashboard', compact(
            'total_jobs',
            'scheduled_jobs',
            'in_progress_jobs',
            'completed_jobs',
            'cancelled_jobs',
            'today_jobs',
            'this_week_jobs',
            'recent_jobs',
            'jobs_by_type',
            'jobs_by_status'
        ));
    }

    /**
     * Report force majeure for a job schedule
     */
    public function reportForceMajeure(Request $request, $id)
    {
        $request->validate([
            'force_majeure_status' => 'required|in:technician_unavailable,material_shortage,weather,emergency,equipment_failure,other',
            'force_majeure_reason' => 'required|string|max:1000',
            'material_status' => 'nullable|in:none,pending_return,returned,lost,damaged',
            'material_return_notes' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_notes' => 'nullable|string|max:500',
        ]);

        try {
            $jobSchedule = JobSchedule::findOrFail($id);

            if (!$jobSchedule->canReportForceMajeure()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot report force majeure for this job schedule'
                ], 422);
            }

            $jobSchedule->reportForceMajeure(
                $request->force_majeure_status,
                $request->force_majeure_reason,
                Auth::id()
            );

            // Update additional fields
            $jobSchedule->update([
                'material_status' => $request->material_status ?? 'none',
                'material_return_notes' => $request->material_return_notes,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'emergency_notes' => $request->emergency_notes,
            ]);

            // Create material return issue if needed
            if ($request->material_status === 'pending_return') {
                $this->createMaterialReturnIssue($jobSchedule);
            }

            // Send notifications
            $this->sendForceMajeureNotifications($jobSchedule);

            return response()->json([
                'status' => 'success',
                'message' => 'Force majeure reported successfully',
                'data' => $jobSchedule->load(['assignedTechnician', 'backupTechnician'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error reporting force majeure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reassign job to backup technician
     */
    public function reassignToBackupTechnician(Request $request, $id)
    {
        $request->validate([
            'backup_technician_id' => 'required|exists:users,id',
            'reassignment_notes' => 'nullable|string|max:500',
        ]);

        try {
            $jobSchedule = JobSchedule::findOrFail($id);

            if (!$jobSchedule->canReassign()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot reassign this job schedule'
                ], 422);
            }

            $jobSchedule->reassignToBackupTechnician(
                $request->backup_technician_id,
                Auth::id()
            );

            // Update reassignment notes
            if ($request->reassignment_notes) {
                $jobSchedule->update(['notes' => $request->reassignment_notes]);
            }

            // Send reassignment notifications
            $this->sendReassignmentNotifications($jobSchedule);

            return response()->json([
                'status' => 'success',
                'message' => 'Job reassigned successfully',
                'data' => $jobSchedule->load(['assignedTechnician', 'backupTechnician'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error reassigning job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reschedule job
     */
    public function rescheduleJob(Request $request, $id)
    {
        $request->validate([
            'reschedule_date' => 'required|date|after:today',
            'reschedule_time' => 'nullable|date_format:H:i',
            'reschedule_reason' => 'required|string|max:500',
        ]);

        try {
            $jobSchedule = JobSchedule::findOrFail($id);

            if (!$jobSchedule->canReschedule()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot reschedule this job schedule'
                ], 422);
            }

            $jobSchedule->rescheduleJob(
                $request->reschedule_date,
                $request->reschedule_time,
                $request->reschedule_reason,
                Auth::id()
            );

            // Send reschedule notifications
            $this->sendRescheduleNotifications($jobSchedule);

            return response()->json([
                'status' => 'success',
                'message' => 'Job rescheduled successfully',
                'data' => $jobSchedule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error rescheduling job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle material return
     */
    public function handleMaterialReturn(Request $request, $id)
    {
        $request->validate([
            'material_status' => 'required|in:returned,lost,damaged',
            'material_return_notes' => 'nullable|string|max:500',
        ]);

        try {
            $jobSchedule = JobSchedule::findOrFail($id);

            $jobSchedule->handleMaterialReturn(
                $request->material_status,
                $request->material_return_notes,
                Auth::id()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Material return handled successfully',
                'data' => $jobSchedule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error handling material return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve force majeure
     */
    public function resolveForceMajeure(Request $request, $id)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:500',
        ]);

        try {
            $jobSchedule = JobSchedule::findOrFail($id);

            $jobSchedule->resolveForceMajeure(
                $request->resolution_notes,
                Auth::id()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Force majeure resolved successfully',
                'data' => $jobSchedule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error resolving force majeure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get force majeure statistics
     */
    public function getForceMajeureStats()
    {
        try {
            $stats = [
                'total_force_majeure' => JobSchedule::forceMajeure()->count(),
                'technician_unavailable' => JobSchedule::byForceMajeureStatus('technician_unavailable')->count(),
                'material_shortage' => JobSchedule::byForceMajeureStatus('material_shortage')->count(),
                'weather' => JobSchedule::byForceMajeureStatus('weather')->count(),
                'emergency' => JobSchedule::byForceMajeureStatus('emergency')->count(),
                'equipment_failure' => JobSchedule::byForceMajeureStatus('equipment_failure')->count(),
                'other' => JobSchedule::byForceMajeureStatus('other')->count(),
                'pending_resolution' => JobSchedule::pendingResolution()->count(),
                'resolved_count' => JobSchedule::where('resolution_status', 'resolved')->count(),
                'escalated_count' => JobSchedule::where('resolution_status', 'escalated')->count(),
                'resolved_today' => JobSchedule::where('resolution_status', 'resolved')
                    ->whereDate('resolved_at', today())->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting force majeure statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * STUDY CASE B1: Create material return for a room
     */
    public function createMaterialReturn(Request $request, JobSchedule $jobSchedule, $roomId)
    {
        $request->validate([
            'return_reason' => 'nullable|string|max:1000',
            'return_date' => 'nullable|date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // MOM: Determine sibling job IDs (same JA + Building + Type) for validation
            $siblingJobIds = [$jobSchedule->id];
            if ($jobSchedule->job_advice_id) {
                $siblingJobIds = \App\Models\JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                    ->where('building_id', $jobSchedule->building_id)
                    ->where('type', $jobSchedule->type)
                    ->pluck('id')
                    ->toArray();
            }

            // Find job schedule room in current job OR siblings
            $jobScheduleRoom = \App\Models\JobScheduleRoom::whereIn('job_schedule_id', $siblingJobIds)
                ->where('id', $roomId)
                ->firstOrFail();

            // Use the ACTUAL job schedule from the room for subsequent logic
            $actualJobSchedule = $jobScheduleRoom->jobSchedule;

            if (in_array($actualJobSchedule->status, ['done_job', 'completed', 'selesai'], true)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material return tidak bisa dibuat karena job sudah Done BA/Completed. Lakukan unpost BA terlebih dahulu jika memang perlu return material.',
                ], 422);
            }

            if ($jobScheduleRoom->material_return_id) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material return untuk room ini sudah pernah dibuat.',
                ], 422);
            }

            $roomName = trim(strtolower($jobScheduleRoom->room_name));
            $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($q) use ($actualJobSchedule) {
                    $q->where('job_schedule_id', $actualJobSchedule->id);
                })
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->with(['items.product', 'warehouse'])
                ->get();

            $returnItems = collect();
            foreach ($materialIssues as $materialIssue) {
                foreach ($materialIssue->items as $item) {
                    if (trim(strtolower((string) $item->room_name)) !== $roomName) {
                        continue;
                    }

                    if (!$item->product || (float) $item->quantity <= 0) {
                        continue;
                    }

                    $returnItems->push([
                        'material_issue' => $materialIssue,
                        'item' => $item,
                    ]);
                }
            }

            if ($returnItems->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada material issue item untuk room ini yang bisa direturn.',
                ], 422);
            }

            $warehouseId = $request->warehouse_id ?: $returnItems->first()['material_issue']->warehouse_id;
            if (!$warehouseId) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Warehouse material return tidak dapat ditentukan dari material issue.',
                ], 422);
            }

            // Generate return number using DocumentNumberService with ADS-RTR code
            $returnNumber = \App\Models\MaterialReturn::generateReturnNumber($actualJobSchedule->id, $actualJobSchedule->building_id);

            // Get team from actual job assign schedule
            $team = null;
            $jobAssignSchedule = $actualJobSchedule->jobAssignSchedules()->first();
            if ($jobAssignSchedule) {
                $team = $jobAssignSchedule->team;
            }

            // Create material return linked to ACTUAL job
            $materialReturn = \App\Models\MaterialReturn::create([
                'return_number' => $returnNumber,
                'job_schedule_id' => $actualJobSchedule->id,
                'job_schedule_room_id' => $jobScheduleRoom->id,
                'job_advice_room_id' => $jobScheduleRoom->job_advice_room_id,
                'warehouse_id' => $warehouseId,
                'team_id' => $team ? $team->id : null,
                'status' => \App\Models\MaterialReturn::STATUS_PENDING,
                'return_date' => $request->return_date ?: now()->toDateString(),
                'return_reason' => $request->return_reason ?: 'Auto return semua material issue untuk room',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Create material return items from DB only. Request item edits are ignored.
            foreach ($returnItems as $returnItem) {
                $item = $returnItem['item'];
                $product = $item->product;
                if (!$product) continue;

                \App\Models\MaterialReturnItem::create([
                    'material_return_id' => $materialReturn->id,
                    'material_issue_item_id' => $item->id,
                    'product_id' => $product->id,
                    'room_name' => $jobScheduleRoom->room_name,
                    'room_id' => $jobScheduleRoom->room_id,
                    'quantity' => $item->quantity,
                    'convert' => $item->convert ?? 1,
                    'bom_quantity' => $item->bom_quantity ?? 0,
                    'unit_price' => $product->last_unit_price ?? 0,
                    'total_price' => ($product->last_unit_price ?? 0) * ($item->quantity ?? 0),
                    'return_reason' => $request->return_reason ?: 'Auto return semua material issue untuk room',
                    'notes' => 'Auto-created from Material Issue Item #' . $item->id,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            // Update job schedule room material return status
            $jobScheduleRoom->update([
                'material_return_status' => \App\Models\JobScheduleRoom::MATERIAL_RETURN_PENDING,
                'material_return_id' => $materialReturn->id,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            \Log::info("✅ STUDY CASE B1: Created material return {$returnNumber} for Room {$jobScheduleRoom->room_name} in JobSchedule {$jobSchedule->job_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Material return created successfully.',
                'data' => $materialReturn->load(['items.product', 'warehouse', 'team'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ STUDY CASE B1: Failed to create material return: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create material return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a room manual via web interface
     */
    public function completeRoomManual(Request $request, JobSchedule $jobSchedule, $roomId)
    {
        try {
            DB::beginTransaction();
            
            $jobScheduleRoom = \App\Models\JobScheduleRoom::findOrFail($roomId);

            $roomCompletionValidation = $this->validateWebRoomCompletion($jobSchedule);
            if ($roomCompletionValidation !== true) {
                DB::rollBack();
                return response()->json($roomCompletionValidation, 422);
            }

            if ((int) $jobScheduleRoom->job_schedule_id !== (int) $jobSchedule->id) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Room ini tidak terhubung dengan job schedule yang sedang dibuka.',
                ], 422);
            }
            
            // Mark as completed using existing model method
            $jobScheduleRoom->markAsCompleted(Auth::id(), 'Completed manually via web admin');
            
            // Optional: Check if all rooms are now completed and update job schedule status
            if ($jobSchedule->areAllRoomsCompleted()) {
                if (!in_array($jobSchedule->status, ['done_job', 'completed', 'selesai', 'teknisi_selesai_pengerjaan'])) {
                    $jobSchedule->status = 'teknisi_selesai_pengerjaan';
                    $jobSchedule->updated_by = Auth::id();
                    $jobSchedule->save();
                    \Log::info("JobSchedule {$jobSchedule->job_number} automagically moved to teknisi_selesai_pengerjaan via manual room completion");
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Room status updated to Completed'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error manual completing room {$roomId}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * STUDY CASE B1: Approve material return
     */
    public function approveMaterialReturn(Request $request, JobSchedule $jobSchedule, $returnId)
    {
        $user = auth()->user();
        if (!$user->hasPermission('operational.job-schedules.approve-material-return') && 
            !$user->hasPermission('operational.job-schedules.approve-material-return.view') && 
            !$user->hasPermission('operational.job-schedules.approve')) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Unauthorized. Missing permission to approve material return.'
             ], 403);
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $materialReturn = \App\Models\MaterialReturn::where('job_schedule_id', $jobSchedule->id)
                ->where('id', $returnId)
                ->firstOrFail();

            if ($materialReturn->status !== \App\Models\MaterialReturn::STATUS_PENDING) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material return can only be approved when status is pending.'
                ], 422);
            }

            $materialReturn->approve(Auth::id(), $request->approval_notes);

            DB::commit();

            \Log::info("✅ STUDY CASE B1: Approved material return {$materialReturn->return_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Material return approved successfully.',
                'data' => $materialReturn->load(['items.product', 'warehouse', 'team', 'approvedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ STUDY CASE B1: Failed to approve material return: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve material return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * STUDY CASE B1: Complete material return (mark as returned)
     */
    public function completeMaterialReturn(Request $request, JobSchedule $jobSchedule, $returnId)
    {
        try {
            DB::beginTransaction();

            $materialReturn = \App\Models\MaterialReturn::where('job_schedule_id', $jobSchedule->id)
                ->where('id', $returnId)
                ->with(['items.product', 'warehouse'])
                ->firstOrFail();

            if ($materialReturn->status !== \App\Models\MaterialReturn::STATUS_APPROVED) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material return must be approved before it can be completed.'
                ], 422);
            }

            // Mark as returned
            $materialReturn->markAsReturned(Auth::id());

            // Update warehouse stock (increase stock)
            $warehouse = $materialReturn->warehouse;
            if ($warehouse) {
                foreach ($materialReturn->items as $item) {
                    $product = $item->product;
                    if (!$product) continue;

                    $qtyToReturn = $item->quantity ?? 0;
                    if ($qtyToReturn <= 0) continue;

                    // Find or create warehouse product record
                    $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
                        ->where('master_product_id', $product->id)
                        ->first();

                    if (!$warehouseProduct) {
                        $warehouseProduct = \App\Models\WarehouseProduct::create([
                            'warehouse_id' => $warehouse->id,
                            'master_product_id' => $product->id,
                            'quantity' => 0,
                            'minimum_stock' => 0,
                            'maximum_stock' => 1000,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                    }

                    // Update stock quantity (increase)
                    $newQuantity = $warehouseProduct->quantity + $qtyToReturn;
                    $warehouseProduct->update([
                        'quantity' => $newQuantity,
                        'updated_by' => Auth::id(),
                    ]);

                    \Log::info("Stock updated on material return: Product {$product->name}, Warehouse {$warehouse->name}, Qty: {$warehouseProduct->quantity} → {$newQuantity} (+{$qtyToReturn})");
                    
                    // Create Inventory Movement record (stock masuk kembali dari return)
                    try {
                        $documentNumberService = app(\App\Services\DocumentNumberService::class);
                        $movementNo = $documentNumberService->generate('material_return', $warehouse->branch_id ? \App\Models\Branch::find($warehouse->branch_id)?->code : null);
                        
                        $movementData = [
                            'warehouse_id' => $warehouse->id,
                            'master_product_id' => $product->id,
                            'movement_type' => 'in', // Stock masuk kembali
                            'quantity' => abs($qtyToReturn), // Positif untuk stock masuk
                            'notes' => "Material return dari teknisi. Return Number: {$materialReturn->return_number}, Product: {$product->name}, Job: {$jobSchedule->job_number}",
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ];
                        
                        // Add optional columns if they exist
                        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('inventory_movements');
                        
                        if (in_array('movement_date', $columns)) {
                            $movementData['movement_date'] = $materialReturn->return_date ?? now()->toDateString();
                        }
                        
                        if (in_array('reference_no', $columns)) {
                            $movementData['reference_no'] = $materialReturn->return_number;
                        }
                        
                        if (in_array('reference_type', $columns)) {
                            $movementData['reference_type'] = 'material_return';
                        }
                        
                        if (in_array('reference_id', $columns)) {
                            $movementData['reference_id'] = $materialReturn->id;
                        }
                        
                        if (in_array('movement_no', $columns)) {
                            $movementData['movement_no'] = $movementNo;
                        }
                        
                        if (in_array('unit_price', $columns) && isset($item->unit_price) && $item->unit_price > 0) {
                            $movementData['unit_price'] = $item->unit_price;
                            if (in_array('total_value', $columns)) {
                                $movementData['total_value'] = $qtyToReturn * $item->unit_price;
                            }
                        }
                        
                        \App\Models\InventoryMovement::create($movementData);
                        \Log::info("✅ Inventory Movement created for material return: Movement No: {$movementNo}, Product: {$product->name}, Quantity: +{$qtyToReturn}, Warehouse: {$warehouse->name}");
                    } catch (\Exception $e) {
                        \Log::error("Failed to create Inventory Movement for material return: " . $e->getMessage());
                        // Don't throw - continue with other items
                    }
                }
            }

            // Update job schedule room material return status
            if ($materialReturn->jobScheduleRoom) {
                $materialReturn->jobScheduleRoom->update([
                    'material_return_status' => \App\Models\JobScheduleRoom::MATERIAL_RETURN_RETURNED,
                    'material_return_at' => now(),
                    'material_return_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            \Log::info("✅ STUDY CASE B1: Completed material return {$materialReturn->return_number} and updated warehouse stock");

            return response()->json([
                'status' => 'success',
                'message' => 'Material return completed successfully and warehouse stock updated.',
                'data' => $materialReturn->load(['items.product', 'warehouse', 'team', 'returnedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ STUDY CASE B1: Failed to complete material return: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete material return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * STUDY CASE B1: Get material returns for a job schedule
     */
    public function getMaterialReturns(JobSchedule $jobSchedule)
    {
        // MOM: Determine sibling job IDs (same JA + Building + Type)
        $siblingJobIds = [$jobSchedule->id];
        if ($jobSchedule->job_advice_id) {
            $siblingJobIds = \App\Models\JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                ->where('building_id', $jobSchedule->building_id)
                ->where('type', $jobSchedule->type)
                ->pluck('id')
                ->toArray();
        }

        $materialReturns = \App\Models\MaterialReturn::whereIn('job_schedule_id', $siblingJobIds)
            ->with([
                'items.product',
                'warehouse',
                'team',
                'jobScheduleRoom',
                'approvedBy',
                'returnedBy',
                'createdBy'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $materialReturns
        ]);
    }

    /**
     * STUDY CASE B1: Get material issue items for a room (for material return form)
     */
    public function getMaterialIssueItemsForRoom(JobSchedule $jobSchedule, $roomId)
    {
        try {
            // MOM: Determine sibling job IDs (same JA + Building + Type)
            $siblingJobIds = [$jobSchedule->id];
            if ($jobSchedule->job_advice_id) {
                $siblingJobIds = \App\Models\JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                    ->where('building_id', $jobSchedule->building_id)
                    ->where('type', $jobSchedule->type)
                    ->pluck('id')
                    ->toArray();
            }

            // Find job schedule room in current job OR siblings
            $jobScheduleRoom = \App\Models\JobScheduleRoom::whereIn('job_schedule_id', $siblingJobIds)
                ->where('id', $roomId)
                ->firstOrFail();

            $roomName = trim(strtolower($jobScheduleRoom->room_name));

            // MOM: Determine sibling job IDs (same JA + Building + Type) for Job View context
            $siblingJobIds = [$jobSchedule->id];
            if ($jobSchedule->job_advice_id) {
                $siblingJobIds = \App\Models\JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                    ->where('building_id', $jobSchedule->building_id)
                    ->where('type', $jobSchedule->type)
                    ->pluck('id')
                    ->toArray();
            }

            // Get material issues related to this job or its siblings
            $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($siblingJobIds) {
                $q->whereIn('job_schedule_id', $siblingJobIds);
            })
            ->with(['items.product', 'warehouse'])
            ->get();

            // Filter items by room name (case-insensitive & trim)
            $items = [];
            foreach ($materialIssues as $materialIssue) {
                foreach ($materialIssue->items as $item) {
                    $itemRoomName = trim(strtolower($item->room_name));
                    if ($itemRoomName === $roomName) {
                        $items[] = [
                            'id' => $item->id,
                            'material_issue_id' => $materialIssue->id,
                            'material_issue_number' => $materialIssue->issue_number,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name ?? 'Unknown Product',
                            'quantity' => $item->quantity,
                            'convert' => $item->convert ?? 1,
                            'bom_quantity' => $item->bom_quantity ?? 0,
                            'room_name' => $item->room_name,
                            'warehouse_id' => $materialIssue->warehouse_id,
                            'warehouse_name' => $materialIssue->warehouse->name ?? 'Unknown Warehouse',
                        ];
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $items
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ STUDY CASE B1: Failed to get material issue items for room: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get material issue items: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Create material return issue (legacy method - kept for backward compatibility)
     */
    private function createMaterialReturnIssue($jobSchedule)
    {
        // This would integrate with MaterialIssue model
        // For now, we'll just log it
        \Log::info('Material return issue created for job schedule: ' . $jobSchedule->id);
    }

    /**
     * Send force majeure notifications
     */
    private function sendForceMajeureNotifications($jobSchedule)
    {
        try {
            // Get all admin users
            $admins = User::whereHas('roles', function($q) {
                $q->where('name', 'like', '%admin%')
                  ->orWhere('name', 'like', '%supervisor%')
                  ->orWhere('name', 'like', '%manager%');
            })->where('is_active', true)->get();

            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Force Majeure Reported',
                    'message' => "Force majeure reported for Job Schedule #{$jobSchedule->job_number} - {$jobSchedule->force_majeure_status}",
                    'platform' => 'web',
                    'type' => 'warning',
                    'action_url' => "/operational/job-schedules/{$jobSchedule->id}",
                    'is_read' => false,
                    'created_by' => Auth::id(),
                    'pada' => now()
                ]);
            }

            \Log::info('Force majeure notifications sent for job schedule: ' . $jobSchedule->id);
        } catch (\Exception $e) {
            \Log::error('Error sending force majeure notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send reassignment notifications
     */
    private function sendReassignmentNotifications($jobSchedule)
    {
        // This would integrate with notification system
        \Log::info('Reassignment notification sent for job schedule: ' . $jobSchedule->id);
    }

    /**
     * Send reschedule notifications
     */
    private function sendRescheduleNotifications($jobSchedule)
    {
        // This would integrate with notification system
        \Log::info('Reschedule notification sent for job schedule: ' . $jobSchedule->id);
    }


    // ========================================
    // TEAM ASSIGNMENT MANAGEMENT
    // ========================================

    /**
     * Get job assignments (API endpoint)
     */
    public function getAssignments(JobSchedule $jobSchedule)
    {
        $assignments = JobAssignment::where('job_number', $jobSchedule->job_number)
            ->with(['team', 'assignedBy', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $assignments
        ]);
    }

    /**
     * Accept job assignment (for team members)
     */
    public function acceptAssignment(Request $request, JobAssignment $assignment)
    {
        try {
            $assignment->accept(Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Job assignment accepted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error accepting assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start job assignment
     */
    public function startAssignment(Request $request, JobAssignment $assignment)
    {
        try {
            $assignment->start(Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Job started successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error starting job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete job assignment
     */
    public function completeAssignment(Request $request, JobAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'completion_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignment->complete($request->completion_notes, Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Job completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error completing job: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // MATERIAL MANAGEMENT
    // ========================================

    /**
     * Get job materials
     */
    public function getMaterials(JobSchedule $jobSchedule)
    {
        $materials = $jobSchedule->jobMaterials()
            ->with(['masterProduct', 'issuedBy', 'receivedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $materials
        ]);
    }

    /**
     * Add material requirement
     */
    public function addMaterial(Request $request, JobSchedule $jobSchedule)
    {
        $validator = Validator::make($request->all(), [
            'master_product_id' => 'required|exists:master_products,id',
            'required_quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $material = JobMaterial::create([
                'job_schedule_id' => $jobSchedule->id,
                'master_product_id' => $request->master_product_id,
                'required_quantity' => $request->required_quantity,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Material requirement added successfully',
                'data' => $material->load(['masterProduct', 'createdBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error adding material requirement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Issue material
     */
    public function issueMaterial(Request $request, JobMaterial $material)
    {
        $validator = Validator::make($request->all(), [
            'issued_quantity' => 'required|integer|min:1|max:' . $material->required_quantity,
            'received_by' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $material->update([
                'issued_quantity' => $request->issued_quantity,
                'status' => 'issued',
                'issued_by' => Auth::id(),
                'received_by' => $request->received_by,
                'issued_at' => now(),
                'received_at' => now(),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Material issued successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error issuing material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return material
     */
    public function returnMaterial(Request $request, JobMaterial $material)
    {
        $validator = Validator::make($request->all(), [
            'returned_quantity' => 'required|integer|min:0|max:' . $material->issued_quantity,
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $material->update([
                'returned_quantity' => $request->returned_quantity,
                'status' => 'returned',
                'returned_at' => now(),
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Material returned successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error returning material: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // PERIODIC JOB MANAGEMENT
    // ========================================

    /**
     * Create periodic job
     */
    public function createPeriodicJob(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'building_id' => 'required|exists:buildings,id',
            'master_rental_id' => 'required|exists:master_rentals,id',
            'job_type' => 'required|in:install,service,remove',
            'service_frequency_months' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'auto_generate' => 'boolean',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $periodicJob = PeriodicJob::create([
                'contract_id' => $request->contract_id,
                'building_id' => $request->building_id,
                'master_rental_id' => $request->master_rental_id,
                'job_type' => $request->job_type,
                'service_frequency_months' => $request->service_frequency_months,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'next_job_date' => $request->start_date,
                'auto_generate' => $request->auto_generate ?? true,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Periodic job created successfully',
                'data' => $periodicJob->load(['contract', 'building', 'masterRental'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating periodic job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate periodic jobs
     */
    public function generatePeriodicJobs()
    {
        try {
            $periodicJobs = PeriodicJob::dueForGeneration()->get();
            $generatedJobs = [];

            foreach ($periodicJobs as $periodicJob) {
                if ($periodicJob->canGenerateJob()) {
                    $jobSchedule = $periodicJob->generateNextJob();
                    if ($jobSchedule) {
                        $generatedJobs[] = $jobSchedule;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Periodic jobs generated successfully',
                'data' => [
                    'generated_count' => count($generatedJobs),
                    'generated_jobs' => $generatedJobs
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating periodic jobs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get periodic jobs
     */
    public function getPeriodicJobs(Request $request)
    {
        $query = PeriodicJob::with(['contract', 'building', 'masterRental', 'createdBy']);

        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $periodicJobs = $query->orderBy('next_job_date', 'asc')->paginate(25);

        return response()->json([
            'status' => 'success',
            'data' => $periodicJobs
        ]);
    }

    /**
     * Trigger auto invoice generation when job is completed (Berdasarkan BRD)
     */
    private function triggerAutoInvoiceGeneration($contractId)
    {
        try {
            $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
            $invoiceService->attemptAutoInvoiceForContract($contractId);
            \Log::info("Auto invoice check executed for contract {$contractId} using period-based invoice generation");

        } catch (\Exception $e) {
            \Log::error("Failed to trigger auto invoice generation for contract {$contractId}: " . $e->getMessage());
            // Don't throw exception to avoid breaking the main workflow
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:job_schedules,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = JobSchedule::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully hidden {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error hiding records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Suspend or DPF job schedules
     */
    public function bulkSuspendDpf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:job_schedules,id',
            'action' => 'required|in:suspend,dpf'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $jobSchedules = JobSchedule::with('jobAdvice')->whereIn('id', $request->ids)->get();
            $count = 0;
            $errors = [];
            
            foreach ($jobSchedules as $jobSchedule) {
                try {
                    // MOM: Only service_routine type can be suspended/dpf
                    // Other types (install, install_free, service_first) cannot be suspended/dpf
                    $allowedTypes = ['service_routine', 'service']; // 'service' for legacy
                    if (!in_array(strtolower($jobSchedule->type), $allowedTypes)) {
                        $errors[] = "Job {$jobSchedule->job_number}: Suspend/DPF tidak dapat diberlakukan pada job type '{$jobSchedule->display_type}'. Hanya service routine yang bisa di-suspend/dpf.";
                        continue;
                    }
                    
                    if ($request->action === 'suspend') {
                        $jobSchedule->suspend();
                        // Add note if provided
                        if ($request->has('notes')) {
                            $jobSchedule->update([
                                'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[SUSPEND] " . $request->notes,
                                'updated_by' => Auth::id()
                            ]);
                        } else {
                            $jobSchedule->update([
                                'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[SUSPEND] Applied via bulk action",
                                'updated_by' => Auth::id()
                            ]);
                        }
                    } else { // dpf
                        // Get old status before changing
                        $oldStatus = $jobSchedule->status;
                        
                        $jobSchedule->markAsDpf();
                        
                        // Reload job schedule with relationships
                        $jobSchedule->refresh();
                        $jobSchedule->load('jobAdvice');
                        
                        // Add note if provided
                        if ($request->has('notes')) {
                            $jobSchedule->update([
                                'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[DPF] " . $request->notes,
                                'updated_by' => Auth::id()
                            ]);
                        } else {
                            $jobSchedule->update([
                                'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[DPF] Applied via bulk action",
                                'updated_by' => Auth::id()
                            ]);
                        }
                        
                        // Trigger invoice generation for DPF (if applicable)
                        $jobAdvice = $jobSchedule->jobAdvice;
                        
                        if ($jobAdvice) {
                            // Check if this is Install Free (from Quotation, no contract_id)
                            $isInstallFree = ($jobAdvice->type === 'install_free' || strtolower($jobAdvice->type ?? '') === 'install free');
                            
                            if ($isInstallFree) {
                                // Install Free: Skip invoice generation (expected behavior)
                                \Log::info("DPF Bulk Action - Job: {$jobSchedule->job_number} (Install Free from Quotation) - Skipping invoice generation as per requirement (Install Free = no invoice, even for DPF)");
                            } elseif ($jobAdvice->contract_id) {
                                // Has contract_id: Check if install free/trial
                                $isInstallFreeOrTrial = $this->isInstallFreeOrTrial($jobSchedule, $jobAdvice);
                                
                                if (!$isInstallFreeOrTrial) {
                                    \Log::info("DPF Bulk Action - Job: {$jobSchedule->job_number} - Triggering invoice generation for contract {$jobAdvice->contract_id}");
                                    $this->triggerAutoInvoiceGeneration($jobAdvice->contract_id);
                                } else {
                                    \Log::info("DPF Bulk Action - Job: {$jobSchedule->job_number} - Skipping invoice generation (install free/trial)");
                                }
                            } else {
                                // No contract_id and not install free: This might be an issue
                                \Log::warning("DPF Bulk Action - Job: {$jobSchedule->job_number}, JobAdvice: {$jobAdvice->job_advice_number} - No contract_id found and not identified as Install Free. Invoice cannot be generated.");
                            }
                        } else {
                            \Log::warning("DPF Bulk Action - Job: {$jobSchedule->job_number} - No JobAdvice found. Invoice cannot be generated.");
                        }
                    }
                    
                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Job {$jobSchedule->job_number}: " . $e->getMessage();
                    \Log::error("Failed to apply {$request->action} to job {$jobSchedule->job_number}: " . $e->getMessage());
                }
            }
            
            DB::commit();
            
            $actionText = $request->action === 'suspend' ? 'suspended' : 'marked as DPF';
            
            if ($count === count($request->ids)) {
                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => "Successfully {$actionText} {$count} job schedule(s)",
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'status' => 'partial',
                    'message' => "Partially successful: {$actionText} {$count} of " . count($request->ids) . " job schedule(s)",
                    'count' => $count,
                    'errors' => $errors
                ]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error applying action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show team assignments page
     */
    public function showAssignments(JobSchedule $jobSchedule)
    {
        // Load job schedule with relationships
        $jobSchedule->load([
            'jobAdvice.customer',
            'building',
            'room',
            'createdBy'
        ]);
        
        return view('operational.job-schedules.assignments', compact('jobSchedule'));
    }

    /**
     * Show materials page
     */
    public function showMaterials(JobSchedule $jobSchedule)
    {
        // Load job schedule with relationships
        // MOM9: Also load quotation for install_free material filtering
        $jobSchedule->load([
            'jobAdvice.customer',
            'jobAdvice.quotation', // For quotation_id access in install_free
            'building',
            'room',
            'createdBy'
        ]);
        
        return view('operational.job-schedules.materials', compact('jobSchedule'));
    }

    /**
     * MOM6: Auto-create remove job after install completed
     * 
     * "ketika di aplikasi teknisi, teknisi sudah selesai job install unitnya, dan sudah oke finish di aplikasi,
     *  maka system akan otomatis create data jadwal untuk remove unitnya, tanggalnya sesuai dengan data di JA."
     */
    private function autoCreateRemoveJob(JobSchedule $installJob, $jobAdvice)
    {
        try {
            // Determine if this is install free or regular install
            $jaTypeLower = strtolower(trim($jobAdvice->type ?? ''));
            $isInstallFree = in_array($jaTypeLower, ['install_free', 'install free']);
            
            // Set correct remove type based on install type
            $removeType = $isInstallFree ? 'remove_free' : 'remove';
            
            $jobAdvice->load('rooms');

            $completedRooms = \App\Models\JobScheduleRoom::where('job_schedule_id', $installJob->id)
                ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                ->whereNotNull('job_advice_room_id')
                ->get()
                ->unique(function ($room) {
                    if ($room->job_advice_room_id) {
                        return 'ja-room:' . $room->job_advice_room_id;
                    }

                    if ($room->room_id) {
                        return 'room:' . $room->room_id;
                    }

                    return 'name:' . strtolower(trim((string) $room->room_name));
                })
                ->values();

            if ($completedRooms->isEmpty()) {
                \Log::warning("Skipping remove job creation for install job {$installJob->job_number}: no completed rooms found.");
                return;
            }

            $completedJobAdviceRoomIds = $completedRooms->pluck('job_advice_room_id')->unique()->values();
            $completedPhysicalRoomKeys = $completedRooms->map(function ($room) {
                if ($room->room_id) {
                    return 'room:' . $room->room_id;
                }

                return 'name:' . strtolower(trim((string) $room->room_name));
            })->filter()->values();

            $roomsNeedingRemove = $jobAdvice->rooms
                ->whereIn('id', $completedJobAdviceRoomIds)
                ->unique(function ($room) {
                    if ($room->id) {
                        return 'ja-room:' . $room->id;
                    }

                    $roomId = $room->room_id
                        ?? $room->contractRoom?->room_id
                        ?? $room->quotationRoom?->room_id;

                    if ($roomId) {
                        return 'room:' . $roomId;
                    }

                    return 'name:' . strtolower(trim((string) $room->room_name));
                })
                ->filter(function ($room) use ($completedPhysicalRoomKeys) {
                    $roomId = $room->room_id
                        ?? $room->contractRoom?->room_id
                        ?? $room->quotationRoom?->room_id;
                    $key = $roomId
                        ? 'room:' . $roomId
                        : 'name:' . strtolower(trim((string) $room->room_name));

                    return $completedPhysicalRoomKeys->contains($key)
                        && !$this->activeRemoveRoomExistsForJobAdviceRoom((int) $room->id);
                })
                ->values();

            if ($roomsNeedingRemove->isEmpty()) {
                \Log::info("Remove job already linked for all completed rooms in Job Advice {$jobAdvice->job_advice_number}. Skipping.");
                return;
            }
            
            // Generate job number using DocumentNumberService with RV/RF remove codes
            $documentNumberService = app(\App\Services\DocumentNumberService::class);
            $jobNumber = $documentNumberService->generate(
                $isInstallFree ? 'remove_free' : 'remove',
                null, // Branch code will be determined from building
                $installJob->building_id
            );
            
            // MOM13: Build room list for internal_notes
            $roomNames = $roomsNeedingRemove->pluck('room_name')->filter()->toArray();
            $roomListNote = count($roomNames) > 0 
                ? "\n[Rooms: " . implode(', ', $roomNames) . "]"
                : '';
            
            // Create remove job with same details as install job
            $removeJob = JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => $removeType, // Dynamic: remove or remove_free
                'status' => 'new_job',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $installJob->building_id,
                'building_name' => $installJob->building_name,
                'company_name' => $installJob->company_name,
                'contract_number' => $installJob->contract_number,
                'quotation_number' => $installJob->quotation_number,
                'room_id' => null, // MOM13: All rooms via job_advice_id
                'schedule_date' => $jobAdvice->remove_date, // Use remove date from Job Advice
                'expected_date' => $jobAdvice->remove_date,
                'internal_notes' => "Auto-created {$removeType} job after install {$installJob->job_number} completed. JA: {$jobAdvice->job_advice_number}{$roomListNote}",
                'material_checked' => true, // Remove job: no material verification needed (units from Unit On Wall)
                'material_checked_at' => now(), // Auto-set material checked
                'created_by' => \App\Models\User::first()?->id ?? null, // Use first user or null
                'updated_by' => \App\Models\User::first()?->id ?? null
            ]);
            
            // MOM13: Link remove job to all JA Rooms
            foreach ($roomsNeedingRemove as $jaRoom) {
                $jaRoom->update(['remove_job_schedule_id' => $removeJob->id]);

                \App\Models\JobScheduleRoom::firstOrCreate(
                    [
                        'job_schedule_id' => $removeJob->id,
                        'job_advice_room_id' => $jaRoom->id,
                    ],
                    [
                        'room_name' => $jaRoom->room_name,
                        'room_id' => $jaRoom->room_id
                            ?? $jaRoom->contractRoom?->room_id
                            ?? $jaRoom->quotationRoom?->room_id,
                        'status' => \App\Models\JobScheduleRoom::STATUS_PENDING,
                        'created_by' => \App\Models\User::first()?->id ?? null,
                        'updated_by' => \App\Models\User::first()?->id ?? null,
                    ]
                );
            }
            
            \Log::info("Auto-created remove job {$removeJob->job_number} for JA {$jobAdvice->job_advice_number} with material_checked = true (units from Unit On Wall)");
            
            return $removeJob;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-create remove job for JA {$jobAdvice->job_advice_number}: " . $e->getMessage());
            // Don't throw - non-critical error
        }
    }

    private function activeRemoveRoomExistsForJobAdviceRoom(int $jobAdviceRoomId): bool
    {
        return \App\Models\JobScheduleRoom::where('job_advice_room_id', $jobAdviceRoomId)
            ->whereHas('jobSchedule', function ($query) {
                $query->whereIn('type', ['remove', 'remove_free', 'remove free'])
                    ->whereNotIn('status', ['cancelled', 'undone']);
            })
            ->exists();
    }

    private function activeRemoveRoomExistsForPhysicalRoom($roomId, ?string $roomName = null): bool
    {
        $normalizedRoomName = strtolower(trim((string) $roomName));
        if (!$roomId && $normalizedRoomName === '') {
            return false;
        }

        return \App\Models\JobScheduleRoom::where(function ($query) use ($roomId, $normalizedRoomName) {
                if ($roomId) {
                    $query->where('room_id', $roomId);
                }

                if ($normalizedRoomName !== '') {
                    $method = $roomId ? 'orWhereRaw' : 'whereRaw';
                    $query->{$method}('LOWER(TRIM(room_name)) = ?', [$normalizedRoomName]);
                }
            })
            ->whereHas('jobSchedule', function ($query) {
                $query->whereIn('type', ['remove', 'remove_free', 'remove free'])
                    ->whereNotIn('status', ['cancelled', 'undone']);
            })
            ->exists();
    }

    /**
     * Check if all service periods are completed and create remove job if needed
     * Only for install type (not install free)
     * 
     * "untuk install, remove job baru dibuat setelah semua service period selesai"
     * Contoh: jika service period 12x, maka remove job baru dibuat setelah service ke-12 selesai
     */
    private function checkAndCreateRemoveJobAfterAllServicesComplete(JobSchedule $completedServiceJob, $jobAdvice)
    {
        try {
            // Skip if no job advice or remove_date
            if (!$jobAdvice || !$jobAdvice->remove_date) {
                return;
            }
            
            // Cek apakah ada install job yang completed untuk job advice ini
            $installJob = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->whereIn('type', ['install', 'Install'])
                ->whereIn('status', ['completed', 'done_job'])
                ->first();
            
            if (!$installJob) {
                \Log::info("No completed install job found for Job Advice {$jobAdvice->job_advice_number}. Skipping remove job check.");
                return;
            }
            
            // Cek apakah ini Install Free atau Install biasa
            $isInstallFree = false;
            $installFreeJob = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->whereIn('type', ['Install Free', 'install_free'])
                ->whereIn('status', ['completed', 'done_job'])
                ->first();
            
            if ($installFreeJob) {
                $isInstallFree = true;
            }
            
            // Jika Install Free, skip (remove job sudah dibuat saat install selesai)
            if ($isInstallFree) {
                \Log::info("This is Install Free. Remove job should already be created. Skipping.");
                return;
            }
            
            // Cek apakah remove job sudah ada
            $existingRemoveJob = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->where('type', 'remove')
                ->first();
            
            if ($existingRemoveJob) {
                \Log::info("Remove job already exists for Job Advice {$jobAdvice->job_advice_number}. Skipping.");
                return;
            }
            
            // Hitung total service yang seharusnya ada berdasarkan contract
            $totalExpectedServices = $this->getTotalExpectedServices($jobAdvice);
            
            if ($totalExpectedServices === null) {
                \Log::warning("Cannot determine total expected services for Job Advice {$jobAdvice->job_advice_number}. Skipping remove job creation.");
                return;
            }
            
            // Hitung service yang sudah completed (termasuk change_rental)
            $serviceTypes = ['service', 'servis', 'change_rental', 'change rental'];
            $completedServicesCount = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->whereIn('type', $serviceTypes)
                ->whereIn('status', ['completed', 'done_job'])
                ->count();
            
            \Log::info("Checking remove job creation for Job Advice {$jobAdvice->job_advice_number}: Completed services/replacement = {$completedServicesCount}, Total expected = {$totalExpectedServices}");
            
            // Jika semua service sudah completed, create remove job
            if ($completedServicesCount >= $totalExpectedServices) {
                \Log::info("All service/replacement periods ({$completedServicesCount}/{$totalExpectedServices}) are completed. Creating remove job...");
                $this->autoCreateRemoveJob($installJob, $jobAdvice);
            } else {
                \Log::info("Not all service periods completed yet ({$completedServicesCount}/{$totalExpectedServices}). Remove job will be created later.");
            }
            
        } catch (\Exception $e) {
            \Log::error("Failed to check and create remove job after all services complete for JA {$jobAdvice->job_advice_number}: " . $e->getMessage());
            // Don't throw - non-critical error
        }
    }

    /**
     * Get total expected services for a job advice based on rental frequency and contract period
     */
    private function getTotalExpectedServices($jobAdvice)
    {
        try {
            // Load rooms with rental product
            $jobAdvice->load(['rooms.rentalProduct.serviceFrequency', 'contract']);
            
            if (!$jobAdvice->rooms || $jobAdvice->rooms->isEmpty()) {
                return null;
            }
            
            // Ambil dari room pertama (asumsi semua room punya service frequency yang sama)
            $firstRoom = $jobAdvice->rooms->first();
            $rental = $firstRoom->rentalProduct;
            
            if (!$rental || !$rental->serviceFrequency) {
                return null;
            }
            
            $serviceFrequency = $rental->serviceFrequency;
            $frequencyTimesPerMonth = $serviceFrequency->frequency_times_per_month ?? 1;
            
            // Get contract period
            $contract = $jobAdvice->contract;
            if (!$contract || !$contract->start_date || !$contract->end_date) {
                return null;
            }
            
            $startDate = \Carbon\Carbon::parse($contract->start_date);
            $endDate = \Carbon\Carbon::parse($contract->end_date);
            $rentalPeriodMonths = $startDate->diffInMonths($endDate) + 1; // +1 to include both start and end month
            
            // Total service = frequency_times_per_month × rental_period_months
            $totalServices = $frequencyTimesPerMonth * $rentalPeriodMonths;
            
            return $totalServices;
            
        } catch (\Exception $e) {
            \Log::error("Failed to get total expected services for Job Advice {$jobAdvice->job_advice_number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Auto-create Unit On Wall when install job is completed
     * 
     * "untuk unit yang sudah terpasang akan otomatis terdata di unit-on-walls"
     */
    private function autoCreateUnitOnWall(JobSchedule $installJob, $jobAdvice)
    {
        try {
            
            // Load job advice with rooms and relationships (support both Contract and Quotation)
            $jobAdvice->load([
                'customer',
                'rooms.contractRoom.room',
                'rooms.quotationRoom.room',
                'rooms.rentalProduct.rentalComponents.preferredProducts'
            ]);
            
            if (!$jobAdvice || !$jobAdvice->rooms || $jobAdvice->rooms->isEmpty()) {
                \Log::warning("No rooms found for Job Advice {$jobAdvice->job_advice_number}. Skipping Unit On Wall creation.");
                return false; // Return false if no rooms found
            }
            
            $unitsCreated = 0;
            $usedInventoryIssuingItemIds = []; // MOM: Track used items to prevent duplicates/reuse
            
            // MOM: Get specific rooms assigned to this job schedule to prevent duplicate/incorrect creation from other rooms in the same JA
            // FIX: Use pivot table (JobScheduleRoomRental) to get ALL assigned JA Rooms (multi-unit support)
            $jobScheduleRoomIds = $installJob->jobScheduleRooms()->pluck('id');
            $assignedJobAdviceRoomIds = \App\Models\JobScheduleRoomRental::whereIn('job_schedule_room_id', $jobScheduleRoomIds)
                ->pluck('job_advice_room_id')
                ->toArray();
            
            // Fallback for legacy (if pivot empty, use direct column)
            if (empty($assignedJobAdviceRoomIds)) {
                $assignedJobAdviceRoomIds = $installJob->jobScheduleRooms()->pluck('job_advice_room_id')->toArray();
            }
                
            \Log::info("Assigned Room IDs for this job: " . implode(', ', $assignedJobAdviceRoomIds));
            
            // Create Unit On Wall for each room in Job Advice
            foreach ($jobAdvice->rooms as $jaRoom) {
                // Skip if this room is NOT assigned to the current job schedule
                if (!empty($assignedJobAdviceRoomIds) && !in_array($jaRoom->id, $assignedJobAdviceRoomIds)) {
                    continue;
                }

                // MOM: Only create Unit On Wall for completed rooms in this job schedule
                // This prevents creating UOW for cancelled/unfinished rooms when cannot_complete_all_rooms is true
                $jsr = \App\Models\JobScheduleRoom::where('job_schedule_id', $installJob->id)
                    ->where('job_advice_room_id', $jaRoom->id)
                    ->first();

                if (!$jsr || $jsr->status !== \App\Models\JobScheduleRoom::STATUS_COMPLETED) {
                    continue;
                }

                $roomSource = null;
                $roomId = null;
                $roomName = null;

                if ($jaRoom->contractRoom && $jaRoom->contractRoom->room) {
                    $roomSource = 'contract';
                    $roomId = $jaRoom->contractRoom->room_id;
                    $roomName = $jaRoom->contractRoom->room->room_name ?? $jaRoom->room_name;
                } elseif ($jaRoom->quotationRoom && $jaRoom->quotationRoom->room) {
                    $roomSource = 'quotation';
                    $roomId = $jaRoom->quotationRoom->room_id;
                    $roomName = $jaRoom->quotationRoom->room->room_name ?? $jaRoom->room_name;
                } elseif ($jaRoom->room_id) {
                    $roomSource = 'direct';
                    $roomId = $jaRoom->room_id;
                    $roomName = $jaRoom->room_name;
                }

                if (!$roomId) {
                    \Log::warning("No room found for JA Room {$jaRoom->id}. Contract Room ID: " . ($jaRoom->contract_room_id ?? 'null') . ", Quotation Room ID: " . ($jaRoom->quotation_room_id ?? 'null') . ". Skipping.");
                    continue;
                }

                $rental = $jaRoom->rentalProduct;
                if (!$rental && $jaRoom->quotationRoom) {
                    $rental = $jaRoom->quotationRoom->rental ?? $jaRoom->quotationRoom->rentalProduct ?? null;
                }

                if (!$rental) {
                    \Log::warning("Rental not found for JA Room {$jaRoom->id}. Rental Product ID: " . ($jaRoom->rental_product_id ?? 'null') . ". Skipping.");
                    continue;
                }

                $productId = null;
                $serialNumberId = null;
                $serialNumberString = null;

                $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($q) use ($installJob) {
                    $q->where('job_schedule_id', $installJob->id);
                })->pluck('issue_number')->toArray();

                if (!empty($materialIssues)) {
                    $inventoryIssuingIds = \App\Models\InventoryIssuing::whereIn('reference_no', $materialIssues)
                        ->whereIn('status', ['processed', 'received', 'sent'])
                        ->pluck('id')
                        ->toArray();

                    if (!empty($inventoryIssuingIds)) {
                        $inventoryIssuingItems = \App\Models\InventoryIssuingItem::whereIn('inventory_issuing_id', $inventoryIssuingIds)
                            ->join('master_products as mp', 'inventory_issuing_items.product_id', '=', 'mp.id')
                            ->join('product_categories as pc', 'mp.product_category_id', '=', 'pc.id')
                            ->where('pc.is_unit', true)
                            ->whereNotNull('inventory_issuing_items.serial_number_id')
                            ->whereNotIn('inventory_issuing_items.id', $usedInventoryIssuingItemIds)
                            ->select('inventory_issuing_items.*')
                            ->with(['serialNumber'])
                            ->get();

                        $inventoryIssuingItem = $inventoryIssuingItems->first(function ($item) use ($roomName) {
                            if (!$roomName) {
                                return true;
                            }

                            return str_contains(strtolower($item->notes ?? ''), strtolower(trim($roomName)));
                        }) ?? $inventoryIssuingItems->first();

                        if ($inventoryIssuingItem) {
                            $productId = $inventoryIssuingItem->product_id;
                            $serialNumberId = $inventoryIssuingItem->serial_number_id;
                            $serialNumberString = $inventoryIssuingItem->serialNumber->serial_number ?? null;
                            $usedInventoryIssuingItemIds[] = $inventoryIssuingItem->id;
                        }
                    }
                }

                // STEP 3.5: Fallback to Technician Scanned Unit (from job_schedule_units)
                // This is factual data from the field - highly reliable for matching specific rooms
                if (!$productId && $installJob) {
                    
                    $scannedUnit = \DB::table('job_schedule_units')
                        ->where('job_schedule_id', $installJob->id)
                        ->where(function($q) use ($jaRoom) {
                            $q->where('job_advice_room_id', $jaRoom->id)
                              ->orWhere('mac', 'LIKE', "%{$jaRoom->room_name}%"); // Fallback to room name match in mac field if room_id not set
                        })
                        ->first();
                        
                    if ($scannedUnit && $scannedUnit->mac) {
                        $snRecord = \App\Models\SerialNumber::where('serial_number', $scannedUnit->mac)->first();
                        
                        // Try fallback via MAC Address in units table
                        if (!$snRecord) {
                            $unitRecord = \DB::table('units')->where('mac', $scannedUnit->mac)->first();
                            if ($unitRecord && $unitRecord->serial_number) {
                                $snRecord = \App\Models\SerialNumber::where('serial_number', $unitRecord->serial_number)->first();
                            }
                        }
                        
                        if ($snRecord) {
                            $productId = $snRecord->master_product_id;
                            $serialNumberId = $snRecord->id;
                            $serialNumberString = $snRecord->serial_number;
                        }
                    }
                }
                
                // STEP 4: Fallback to Material Issue Items (if no inventory issuing items found)
                if (!$productId) {
                    
                    // Use join instead of whereHas for better performance and reliability
                    $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($installJob) {
                        $q->where('job_schedule_id', $installJob->id);
                    })
                    ->join('master_products as mp', 'material_issue_items.product_id', '=', 'mp.id')
                    ->join('product_categories as pc', 'mp.product_category_id', '=', 'pc.id')
                    ->where('pc.is_unit', true) // Only unit products, not liquids/cleaners
                    ->where(function($query) use ($roomName) {
                        // Match by room name if available
                        if ($roomName) {
                            $query->where('material_issue_items.room_name', $roomName);
                        }
                    })
                    ->select('material_issue_items.*') // Select only material_issue_items columns
                    ->with(['product.productType'])
                    ->first();
                    
                    if ($materialIssueItems && $materialIssueItems->product_id) {
                        $productId = $materialIssueItems->product_id;
                    } else {
                        // Try without room filter
                        $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($installJob) {
                            $q->where('job_schedule_id', $installJob->id);
                        })
                        ->join('master_products as mp', 'material_issue_items.product_id', '=', 'mp.id')
                        ->join('product_categories as pc', 'mp.product_category_id', '=', 'pc.id')
                        ->where('pc.is_unit', true) // Only unit products
                        ->select('material_issue_items.*') // Select only material_issue_items columns
                        ->with(['product.productType'])
                        ->first();
                        
                        if ($materialIssueItems && $materialIssueItems->product_id) {
                            $productId = $materialIssueItems->product_id;
                        }
                    }
                }
                
                // Skip if no product found (MUST come from material issue items or inventory issuing items)
                // We DO NOT fallback to rental_components anymore - product MUST be from what was actually issued
                if (!$productId) {
                    \Log::warning("JA Room {$jaRoom->id}: No unit product found from material issue items or inventory issuing items for Rental {$rental->rental_name}. Skipping Unit On Wall creation.");
                    \Log::warning("JA Room {$jaRoom->id}: NOTE: Unit On Wall can only be created if unit product exists in material issue items (from what was actually issued).");
                    continue;
                }
                
                // Verify product is actually a unit (double-check by querying database directly)
                $product = \App\Models\MasterProduct::with('productType')->find($productId);
                
                // Also check directly from database using join to be absolutely sure
                $productTypeCheck = \DB::table('master_products as mp')
                    ->join('product_categories as pc', 'mp.product_category_id', '=', 'pc.id')
                    ->where('mp.id', $productId)
                    ->select('pc.is_unit', 'pc.name as product_type_name')
                    ->first();
                
                $isUnitFromDB = $productTypeCheck && $productTypeCheck->is_unit == 1;
                $isUnitFromRelationship = $product && $product->productType && $product->productType->is_unit;
                $isUnit = $isUnitFromDB || $isUnitFromRelationship;
                
                if (!$product || !$isUnit) {
                    \Log::warning("JA Room {$jaRoom->id}: Product ID {$productId} is not a unit (is_unit = false). Skipping Unit On Wall creation.");
                    continue;
                }
                
                // Serial number should already be found from inventory_issuing_items above
                // MANDATORY FIX: Skip if no serial number found.
                // Creating UnitOnWall without SN causes data corruption and makes it hard to track assets.
                if (!$serialNumberId) {
                    \Log::warning("JA Room {$jaRoom->id}: ⚠️ No serial number found from verified inventory issuing items/materials for product {$productId}. Unit On Wall creation SKIPPED to prevent data corruption.");
                    continue; 
                }
                
                // Check if Unit On Wall already exists for this room and rental
                $existingUnit = \App\Models\UnitOnWall::where('room_id', $roomId)
                    ->where('rental_id', $rental->id)
                    ->where('building_id', $installJob->building_id)
                    ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall'])
                    ->first();
                
                if ($existingUnit) {
                    // FIX: strict check on serial_number_id
                    // Only skip if it's the EXACT SAME unit (same serial number)
                    if ($existingUnit->serial_number_id == $serialNumberId) {
                        continue;
                    } else {
                        
                        // MOM: Deactivate the old unit so it doesn't "poison" future jobs (ghost unit)
                        // This ensures that the Wall only reflects the LATEST installation for this room/rental
                        $existingUnit->update([
                            'status' => 'removed',
                            'notes' => ($existingUnit->notes ?? '') . "\n[AUTO-DEACTIVATED at " . now()->format('Y-m-d H:i:s') . "] Replaced by new installation via job " . $installJob->job_number,
                            'updated_by' => \Auth::id() ?? \App\Models\User::first()?->id ?? null
                        ]);
                        
                        // If it has a serial number, return IT to ready status as well (as it was just replaced)
                        // Requirement 5: Also set location back to warehouse
                        if ($existingUnit->serial_number_id) {
                            $oldSn = \App\Models\SerialNumber::find($existingUnit->serial_number_id);
                            if ($oldSn) {
                                $oldSn->update([
                                    'status' => 'on_hand_remove',
                                    'location_type' => 'technician',
                                    'location_id' => \Auth::id() ?? 1,
                                    'updated_by' => \Auth::id() ?? 1
                                ]);
                            }
                        }
                    }
                }
                
                // Create Unit On Wall for quantity specified in JA Room
                $quantity = $jaRoom->quantity ?? 1;
                
                // Get company_name
                $companyName = $installJob->company_name ?? null;
                if (!$companyName && $jobAdvice->customer) {
                    $companyName = $jobAdvice->customer->name ?? null;
                }
                if (!$companyName) {
                    $companyName = 'N/A'; // Fallback value
                }
                
                for ($i = 0; $i < $quantity; $i++) {
                    $unitData = [
                        'customer_id' => $jobAdvice->customer_id,
                        'building_id' => $installJob->building_id,
                        'room_id' => $roomId,
                        'rental_id' => $rental->id,
                        'product_id' => $productId,
                        'serial_number_id' => $serialNumberId,
                        'serial_number' => $serialNumberString, // Add serial_number string field
                        'install_date' => $installJob->schedule_date ?? now()->toDateString(),
                        'status' => 'active',
                        'notes' => "Auto-created from Install Job {$installJob->job_number}. JA: {$jobAdvice->job_advice_number} ({$roomSource})",
                        'company_name' => $companyName,
                        'room_name' => $roomName ?? 'N/A',
                        'rental_name' => $rental->rental_name ?? $jaRoom->rental_name ?? 'N/A',
                        'product_name' => \App\Models\MasterProduct::find($productId)->name ?? 'N/A', // Add product_name
                        'created_by' => \App\Models\User::first()?->id ?? null, // Use first user or null
                        'updated_by' => \App\Models\User::first()?->id ?? null
                    ];
                    
                    $unit = \App\Models\UnitOnWall::create($unitData);
                    
                    // Update serial number status if assigned
                    if ($serialNumberId) {
                        $serialNumber = \App\Models\SerialNumber::find($serialNumberId);
                        if ($serialNumber) {
                            $serialNumber->update([
                                'status' => 'in_use', // Update main status field
                                'location_type' => 'customer', // Set location type to customer (installed at customer location)
                                'location_id' => $jobAdvice->customer_id, // Set customer ID as location
                            ]);
                        }
                    }
                    
                    $unitsCreated++;
                    \Log::info("Auto-created Unit On Wall {$unit->id} for Install Job {$installJob->job_number}, JA {$jobAdvice->job_advice_number}" . ($serialNumberString ? " with serial number {$serialNumberString}" : " without serial number"));
                    
                    // Only use the first serial number for the first unit
                    // Reset for next iteration if quantity > 1
                    if ($i === 0 && $quantity > 1) {
                        // Find next available serial number for subsequent units
                        $serialNumberId = null;
                        $serialNumberString = null;
                    }
                }
            }
            
            if ($unitsCreated > 0) {
                return true; // Return true if at least one unit was created
            } else {
                return false; // Return false if no unit was created
            }
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-create Unit On Wall for Install Job {$installJob->job_number}: " . $e->getMessage());
            // Don't throw - non-critical error
            return false; // Return false on error
        }
    }

    /**
     * Check if remove job should skip invoice generation
     * MOM8: "untuk remove job dari install free atau trial jangan generate invoice"
     * 
     * Logic:
     * - remove_free / remove free → ALWAYS skip invoice (from Install Free, no contract)
     * - remove → Check if from Install Free Job Advice, if yes skip invoice
     */
    private function isRemoveJobFromInstallFree(JobSchedule $removeJob, $jobAdvice): bool
    {
        $jobTypeLower = strtolower($removeJob->type);
        
        // CASE 1: Job type is 'remove_free' or 'remove free' → ALWAYS skip invoice
        // These are auto-generated from Install Free jobs, no invoice needed
        if ($jobTypeLower === 'remove_free' || $jobTypeLower === 'remove free') {
            \Log::info("Remove Job Schedule {$removeJob->job_number} is REMOVE FREE type - skipping invoice generation");
            return true;
        }
        
        // CASE 2: Job type is 'remove' (regular) → Check if from Install Free context
        if ($jobTypeLower === 'remove') {
            // Check Job Advice TYPE to see if original install was install free
            // If Job Advice type is install_free, this remove should also skip invoice
            if ($jobAdvice && $jobAdvice->type) {
                $jaTypeLower = strtolower(trim($jobAdvice->type));
                if ($jaTypeLower === 'install_free' || $jaTypeLower === 'install free') {
                    \Log::info("Remove Job Schedule {$removeJob->job_number} is from INSTALL FREE Job Advice (type: '{$jobAdvice->type}') - skipping invoice generation");
                    return true;
                }
            }
            
            // Regular remove from Contract - can generate invoice
            \Log::info("Remove Job Schedule {$removeJob->job_number} is regular REMOVE (type: '{$removeJob->type}') - invoice generation allowed");
            return false;
        }
        
        // Other job types - not a remove job
        return false;
    }

    /**
     * Check if install job is free or trial
     * MOM8: "untuk JA yang install free atau trial jangan generate invoice"
     */
    private function isInstallFreeOrTrial(JobSchedule $jobSchedule, $jobAdvice): bool
    {
        // Check if job type is any install type
        $installTypes = ['install', 'Install', 'Install Free', 'install_free'];
        if (!in_array($jobSchedule->type, $installTypes)) {
            return false;
        }
        
        // CRITICAL: Check Job Advice TYPE first (most important check)
        // Job Advice type can be "install_free" or "Install Free" (case insensitive)
        if ($jobAdvice && $jobAdvice->type) {
            $jaTypeLower = strtolower(trim($jobAdvice->type));
            if ($jaTypeLower === 'install_free' || $jaTypeLower === 'install free') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Auto-remove/hide Unit On Wall when remove job is completed
     * 
     * "ketika remove job sudah selesai, unit on wall akan otomatis ter-hide/removed"
     */
    private function autoRemoveUnitOnWall(JobSchedule $removeJob, $jobAdvice)
    {
        try {
            
            // Load job advice with rooms and relationships
            $jobAdvice->load([
                'rooms.contractRoom.room',
                'rooms.rentalProduct'
            ]);
            
            if (!$jobAdvice || !$jobAdvice->rooms || $jobAdvice->rooms->isEmpty()) {
                \Log::warning("No rooms found for Job Advice {$jobAdvice->job_advice_number}. Skipping Unit On Wall removal.");
                return;
            }
            
            $unitsRemoved = 0;
            
            // MOM: Get specific rooms assigned to this job schedule to prevent incorrect removal
            // FIX: Use pivot table (JobScheduleRoomRental) to get ALL assigned JA Rooms (multi-unit support)
            $jobScheduleRoomIds = $removeJob->jobScheduleRooms()->pluck('id');
            $assignedJobAdviceRoomIds = \App\Models\JobScheduleRoomRental::whereIn('job_schedule_room_id', $jobScheduleRoomIds)
                ->pluck('job_advice_room_id')
                ->toArray();
            
            // Fallback for legacy (if pivot empty, use direct column)
            if (empty($assignedJobAdviceRoomIds)) {
                $assignedJobAdviceRoomIds = $removeJob->jobScheduleRooms()->pluck('job_advice_room_id')->toArray();
            }
                
            \Log::info("Assigned Room IDs for this removal job: " . implode(', ', $assignedJobAdviceRoomIds));
            
            // MOM: Strict Install-Remove Mirroring - Find serial numbers from the related install job
            $installJobSns = [];
            try {
                $installJob = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)
                    ->whereIn('type', ['install', 'Install', 'install_free', 'Install Free'])
                    ->whereIn('status', ['completed', 'done_job', 'done job'])
                    ->first();
                    
                if ($installJob) {
                    $materialIssueNumbers = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($installJob) {
                        $q->where('job_schedule_id', $installJob->id);
                    })->pluck('issue_number')->toArray();
                    
                    if (!empty($materialIssueNumbers)) {
                        $installJobSns = \DB::table('inventory_issuing_items')
                            ->join('inventory_issuings', 'inventory_issuing_items.inventory_issuing_id', '=', 'inventory_issuings.id')
                            ->whereIn('inventory_issuings.reference_no', $materialIssueNumbers)
                            ->whereNotNull('inventory_issuing_items.serial_number_id')
                            ->pluck('inventory_issuing_items.serial_number_id')
                            ->toArray();
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Failed to fetch Install Job SNs in autoRemoveUnitOnWall: " . $e->getMessage());
            }

            // Remove/hide Unit On Wall for each room in Job Advice
            foreach ($jobAdvice->rooms as $jaRoom) {
                // Skip if this room is NOT assigned to the current job schedule
                if (!empty($assignedJobAdviceRoomIds) && !in_array($jaRoom->id, $assignedJobAdviceRoomIds)) {
                    continue;
                }
                
                // Get room data - support both Contract and Quotation
                $roomId = null;
                
                if ($jaRoom->contractRoom && $jaRoom->contractRoom->room) {
                    $roomId = $jaRoom->contractRoom->room_id;
                } elseif ($jaRoom->quotationRoom && $jaRoom->quotationRoom->room) {
                    $roomId = $jaRoom->quotationRoom->room_id;
                } elseif ($jaRoom->room_id) {
                    $roomId = $jaRoom->room_id;
                }
                
                if (!$roomId) {
                    \Log::warning("No room found for JA Room {$jaRoom->id}. Skipping.");
                    continue;
                }
                
                // Get rental data
                $rental = $jaRoom->rentalProduct;
                if (!$rental) {
                    \Log::warning("Rental not found for JA Room {$jaRoom->id}. Skipping.");
                    continue;
                }
                
                // Find active Unit On Wall for this room and rental
                $unitsQuery = \App\Models\UnitOnWall::where('room_id', $roomId)
                    ->where('rental_id', $rental->id)
                    ->where('building_id', $removeJob->building_id)
                    ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall']);
                
                // MOM: Apply strict SN filter if available to prevent removing units from other jobs
                if (!empty($installJobSns)) {
                    $unitsQuery->whereIn('serial_number_id', $installJobSns);
                }

                $units = $unitsQuery->get();
                
                if ($units->isEmpty()) {
                    continue;
                }
                
                // Update status to 'removed' for each unit
                foreach ($units as $unit) {
                    $unit->update([
                        'status' => 'removed',
                        'notes' => ($unit->notes ?? '') . "\n\n[SYSTEM AUTO-REMOVED at " . now()->format('Y-m-d H:i:s') . "] " . 
                                  "Unit removed after Remove Job {$removeJob->job_number} completed. JA: {$jobAdvice->job_advice_number}",
                        'updated_by' => \App\Models\User::first()?->id ?? null
                    ]);
                    
                    // FIX: Update Serial Number status back to 'ready' so it can be reused for new Install jobs
                    if ($unit->serial_number_id) {
                        $sn = \App\Models\SerialNumber::find($unit->serial_number_id);
                        if ($sn) {
                            $sn->update([
                                'status' => 'ready',
                                'location_type' => 'warehouse', // Return to warehouse
                                'location_id' => $sn->warehouse_id, // Requirement 4
                                'updated_by' => \Auth::id() ?? \App\Models\User::first()?->id ?? null
                            ]);

                            // STOCK UPDATE: Increment warehouse stock when unit is removed
                            if ($sn->master_product_id && $sn->warehouse_id) {
                                // Find or create warehouse product record
                                $warehouseProduct = \App\Models\WarehouseProduct::firstOrCreate(
                                    [
                                        'warehouse_id' => $sn->warehouse_id,
                                        'master_product_id' => $sn->master_product_id,
                                    ],
                                    [
                                        'quantity' => 0,
                                        'minimum_stock' => 0,
                                        'maximum_stock' => 0,
                                        'created_by' => \Auth::id() ?? \App\Models\User::first()?->id ?? null,
                                        'updated_by' => \Auth::id() ?? \App\Models\User::first()?->id ?? null,
                                    ]
                                );

                                // Increment quantity
                                $warehouseProduct->increment('quantity', 1);

                                // Create inventory movement record
                                $movementData = [
                                    'warehouse_id' => $sn->warehouse_id,
                                    'master_product_id' => $sn->master_product_id,
                                    'movement_type' => 'in',
                                    'quantity' => 1,
                                    'notes' => "Unit returned to warehouse after Remove Job {$removeJob->job_number} completed. SN: {$sn->serial_number}",
                                    'created_by' => \Auth::id() ?? \App\Models\User::first()?->id ?? null,
                                    'updated_by' => \Auth::id() ?? \App\Models\User::first()?->id ?? null,
                                ];

                                // Simplified column mapping for InventoryMovement
                                $movementData['reference_no'] = $removeJob->job_number;
                                $movementData['reference_type'] = 'unit_removal';
                                $movementData['movement_no'] = 'RET-' . ($removeJob->job_number ?? now()->format('YmdHis'));
                                $movementData['movement_date'] = now()->toDateString();

                                try {
                                    \App\Models\InventoryMovement::create($movementData);
                                } catch (\Exception $e) {
                                    \Log::warning("Could not record Inventory Movement: " . $e->getMessage());
                                }

                                // 4. Create History
                                try {
                                    \App\Models\UnitOnWallHistory::create([
                                        'unit_on_wall_id' => $unit->id,
                                        'action' => 'remove',
                                        'customer_id' => $unit->customer_id,
                                        'customer_name' => $unit->company_name ?? ($unit->customer?->name),
                                        'location' => $unit->room_name,
                                        'action_date' => now(),
                                        'technician_id' => $removeJob->assigned_technician_id,
                                        'technician_name' => $removeJob->assignedTechnician?->name,
                                        'job_schedule_id' => $removeJob->id,
                                        'job_schedule_number' => $removeJob->job_number,
                                        'notes' => "Unit removed and returned to warehouse. Stock incremented. [SYSTEM AUTO-REMOVED]",
                                        'created_by' => \Auth::id() ?? \App\Models\User::first()?->id ?? null
                                    ]);
                                } catch (\Exception $e) {
                                    \Log::warning("Could not record Unit On Wall History: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    $unitsRemoved++;
                }
            }
            
            if ($unitsRemoved > 0) {
            }
            
            return $unitsRemoved;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-remove Unit On Wall for Remove Job {$removeJob->job_number}: " . $e->getMessage());
            // Don't throw - non-critical error
        }
    }

    /**
     * Auto-update last_service_date in UnitOnWall when service job is completed
     * 
     * "pada http://localhost:8000/warehouse/unit-on-walls/6 ada last service date, 
     *  itu adalah kapan job schedule job service terakhir di lakukan untuk unit itu, 
     *  akan tercatat di sana."
     */
    private function autoUpdateUnitOnWallLastServiceDate(JobSchedule $serviceJob, $jobAdvice)
    {
        try {
            
            if (!$jobAdvice || !$serviceJob->building_id) {
                \Log::warning("Missing required data for Service Job {$serviceJob->job_number}. Building ID: {$serviceJob->building_id}, JobAdvice ID: " . ($jobAdvice->id ?? 'N/A'));
                return;
            }
            
            // Get service date (use completed_at if available, otherwise use schedule_date)
            $serviceDate = $serviceJob->completed_at 
                ? \Carbon\Carbon::parse($serviceJob->completed_at)->toDateString()
                : ($serviceJob->schedule_date 
                    ? $serviceJob->schedule_date->toDateString() 
                    : null);
            
            if (!$serviceDate) {
                \Log::warning("No service date found for Service Job {$serviceJob->job_number}. Skipping last_service_date update.");
                return;
            }
            
            $serviceRooms = collect();
            if ($serviceJob->room_id) {
                $serviceRooms->push([
                    'room_id' => $serviceJob->room_id,
                    'room_name' => $serviceJob->room_name,
                ]);
            }

            if ($serviceRooms->isEmpty()) {
                $serviceJob->loadMissing('jobScheduleRooms');
                $serviceRooms = $serviceJob->jobScheduleRooms
                    ->map(fn ($room) => [
                        'room_id' => $room->room_id,
                        'room_name' => $room->room_name,
                    ])
                    ->filter(fn ($room) => !empty($room['room_id']) || !empty($room['room_name']))
                    ->values();
            }

            if ($serviceRooms->isEmpty()) {
                \Log::warning("Missing room data for Service Job {$serviceJob->job_number}. Cannot update Unit On Wall last_service_date safely.");
                return;
            }

            // Find UnitOnWall records that match this service job.
            // Multi-room jobs often store room data in job_schedule_rooms, not job_schedules.room_id.
            $units = \App\Models\UnitOnWall::where('customer_id', $jobAdvice->customer_id)
                ->where('building_id', $serviceJob->building_id)
                ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall'])
                ->where(function ($query) use ($serviceRooms) {
                    foreach ($serviceRooms as $room) {
                        $query->orWhere(function ($roomQuery) use ($room) {
                            if (!empty($room['room_id'])) {
                                $roomQuery->where('room_id', $room['room_id']);
                            }

                            if (!empty($room['room_name'])) {
                                $roomQuery->orWhereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim($room['room_name']))]);
                            }
                        });
                    }
                })
                ->get();
            
            if ($units->isEmpty()) {
                return;
            }
            
            $unitsUpdated = 0;
            
            // Update last_service_date for each unit
            foreach ($units as $unit) {
                // Only update if service date is newer than current last_service_date
                if (!$unit->last_service_date || $serviceDate > $unit->last_service_date->toDateString()) {
                    $unit->update([
                        'last_service_date' => $serviceDate,
                        'updated_by' => \App\Models\User::first()?->id ?? null
                    ]);
                    
                    $unitsUpdated++;
                } else {
                }
            }
            
            if ($unitsUpdated > 0) {
            }
            
            return $unitsUpdated;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-update last_service_date for Service Job {$serviceJob->job_number}: " . $e->getMessage());
            // Don't throw - non-critical error
        }
    }

    private function ensureFirstServiceAfterCancelledRemoveFree(JobSchedule $removeJob): int
    {
        try {
            $removeJob->loadMissing([
                'jobAdvice.quotation',
                'jobScheduleRooms.rentals.jobAdviceRoom',
            ]);

            $quotationNumber = $removeJob->quotation_number
                ?: $removeJob->jobAdvice?->quotation?->quotation_number;

            $roomIds = $removeJob->jobScheduleRooms
                ->pluck('room_id')
                ->push($removeJob->room_id)
                ->filter()
                ->unique()
                ->values();

            if (!$quotationNumber || $roomIds->isEmpty()) {
                \Log::warning('Cannot auto-create CSR after Remove Free cancellation: missing quotation number or room scope.', [
                    'job_schedule_id' => $removeJob->id,
                    'job_number' => $removeJob->job_number,
                    'quotation_number' => $quotationNumber,
                    'room_ids' => $roomIds->all(),
                ]);

                return 0;
            }

            $contract = \App\Models\Contract::with([
                    'quotation',
                    'contractRooms.room.building',
                ])
                ->whereHas('quotation', function ($query) use ($quotationNumber) {
                    $query->where('quotation_number', $quotationNumber);
                })
                ->latest('id')
                ->first();

            if (!$contract) {
                return 0;
            }

            $jobAdvices = \App\Models\JobAdvice::with([
                    'contract.quotation',
                    'rooms.contractRoom.room.building',
                    'rooms.rentalProduct.serviceFrequency',
                ])
                ->where('contract_id', $contract->id)
                ->where('status', 'approved')
                ->whereIn(DB::raw('LOWER(type)'), ['install', 'service'])
                ->orderBy('id')
                ->get();

            $createdCount = 0;

            foreach ($jobAdvices as $jobAdvice) {
                $roomsByPhysicalRoom = $jobAdvice->rooms
                    ->filter(function ($jaRoom) use ($roomIds, $jobAdvice) {
                        $roomId = $jaRoom->contractRoom?->room_id;

                        if (!$roomId || !$roomIds->contains($roomId)) {
                            return false;
                        }

                        return \App\Models\UnitOnWall::where('customer_id', $jobAdvice->customer_id)
                            ->where('room_id', $roomId)
                            ->whereIn('status', $this->activeUnitOnWallStatusesForScheduling())
                            ->whereNotNull('serial_number_id')
                            ->exists();
                    })
                    ->groupBy(function ($jaRoom) {
                        return $jaRoom->contract_room_id
                            ? 'contract-room:' . $jaRoom->contract_room_id
                            : 'room:' . ($jaRoom->contractRoom?->room_id ?? $jaRoom->room_name);
                    });

                foreach ($roomsByPhysicalRoom as $roomGroup) {
                    $jaRoomIds = $roomGroup->pluck('id')->all();

                    if ($this->activeServiceScheduleExistsForJobAdviceRooms($jobAdvice->id, $jaRoomIds)) {
                        continue;
                    }

                    DB::transaction(function () use ($jobAdvice, $roomGroup, &$createdCount) {
                        $primaryRoom = $roomGroup->first();
                        $contractRoom = $primaryRoom->contractRoom;
                        $room = $contractRoom?->room;
                        $building = $room?->building;
                        $roomId = $contractRoom?->room_id;
                        $hasServiceMaterials = $roomGroup->contains(function ($item) {
                            $rentalType = strtolower((string) ($item->rentalProduct?->rental_type ?? 'unit_refill'));
                            return $rentalType !== 'unit_only';
                        });
                        $rental = $primaryRoom->rentalProduct;
                        $serviceFrequencyObj = $rental?->serviceFrequency;
                        $serviceFrequency = $serviceFrequencyObj?->frequency_times_per_month
                            ?? $serviceFrequencyObj?->frequency_months
                            ?? null;

                        $schedule = JobSchedule::create([
                            'job_number' => null,
                            'type' => 'service_first',
                            'status' => 'scheduled',
                            'job_advice_id' => $jobAdvice->id,
                            'building_id' => $building?->id,
                            'building_name' => $building?->nama_gedung ?? $building?->name,
                            'room_id' => $roomId,
                            'room_name' => $primaryRoom->room_name,
                            'company_name' => $jobAdvice->company_name,
                            'contract_number' => $jobAdvice->contract?->contract_number,
                            'quotation_number' => $jobAdvice->contract?->quotation?->quotation_number,
                            'schedule_date' => $jobAdvice->first_service_date ?? $jobAdvice->expected_date,
                            'expected_date' => $jobAdvice->first_service_date ?? $jobAdvice->expected_date,
                            'period' => 1,
                            'service_frequency' => $serviceFrequency,
                            'service_period_type' => $serviceFrequencyObj?->name ?? 'monthly',
                            'reference_number' => $jobAdvice->job_advice_number,
                            'internal_notes' => "Auto-generated first CSR because related Remove Free was cancelled while Unit On Wall remains installed. JA: {$jobAdvice->job_advice_number}",
                            'material_checked' => !$hasServiceMaterials,
                            'material_checked_at' => !$hasServiceMaterials ? now() : null,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);

                        $jobScheduleRoom = \App\Models\JobScheduleRoom::create([
                            'job_schedule_id' => $schedule->id,
                            'job_advice_room_id' => $primaryRoom->id,
                            'room_name' => $primaryRoom->room_name,
                            'room_id' => $roomId,
                            'status' => \App\Models\JobScheduleRoom::STATUS_PENDING,
                            'material_return_status' => \App\Models\JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                            'notes' => 'Auto-generated first CSR after Remove Free cancellation.',
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);

                        $isPrimary = true;
                        foreach ($roomGroup as $rentalItem) {
                            \App\Models\JobScheduleRoomRental::create([
                                'job_schedule_room_id' => $jobScheduleRoom->id,
                                'job_advice_room_id' => $rentalItem->id,
                                'is_primary' => $isPrimary,
                            ]);

                            $rentalItem->update([
                                'service_job_schedule_id' => $schedule->id,
                                'rental_has_service' => true,
                                'unit_already_installed' => true,
                                'updated_by' => Auth::id(),
                            ]);

                            $isPrimary = false;
                        }

                        $createdCount++;
                    });
                }
            }

            return $createdCount;
        } catch (\Exception $e) {
            \Log::error("Failed to auto-create CSR after cancelling Remove Free {$removeJob->job_number}: " . $e->getMessage());

            return 0;
        }
    }

    private function activeServiceScheduleExistsForJobAdviceRooms(int $jobAdviceId, array $jobAdviceRoomIds): bool
    {
        return JobSchedule::where('job_advice_id', $jobAdviceId)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->whereNotIn('status', ['cancelled', 'undone'])
            ->where(function ($query) use ($jobAdviceRoomIds) {
                $query->whereHas('jobScheduleRooms', function ($roomQuery) use ($jobAdviceRoomIds) {
                    $roomQuery->whereIn('job_advice_room_id', $jobAdviceRoomIds);
                })->orWhereHas('jobScheduleRooms.rentals', function ($rentalQuery) use ($jobAdviceRoomIds) {
                    $rentalQuery->whereIn('job_advice_room_id', $jobAdviceRoomIds);
                });
            })
            ->exists();
    }

    private function activeUnitOnWallStatusesForScheduling(): array
    {
        return ['active', 'installed', 'on_wall', 'on wall', 'onwall'];
    }

    /**
     * MOM6: Auto-cancel remove job if customer continues to contract
     * 
     * "nah jika unit remove tapi sudah lanjut ke contract, maka job untuk removenya tercancel secara otomatis.
     *  statusnya jadi canceled. dan ini system yang membuatnya."
     * 
     * This method should be called from ContractController when a contract is created from trial
     */
    public static function autoCancelRemoveJobForContract($jobAdviceId, $reason = null)
    {
        try {
            // Find all pending remove jobs for this job advice
            $removeJobs = JobSchedule::where('job_advice_id', $jobAdviceId)
                ->where('type', 'remove')
                ->whereIn('status', ['scheduled', 'pending'])
                ->get();
            
            if ($removeJobs->isEmpty()) {
                \Log::info("No pending remove jobs found for Job Advice ID {$jobAdviceId}");
                return 0;
            }
            
            $cancelledCount = 0;
            
            foreach ($removeJobs as $removeJob) {
                $removeJob->update([
                    'status' => 'cancelled',
                    'internal_notes' => ($removeJob->internal_notes ?? '') . "\n\n[SYSTEM AUTO-CANCELLED at " . now()->format('Y-m-d H:i:s') . "] " . 
                                       ($reason ?? 'Customer continued to contract. Unit will remain at location.'),
                    'updated_by' => \App\Models\User::first()?->id ?? null // Use first user or null
                ]);
                
                $cancelledCount++;
                
                \Log::info("Auto-cancelled remove job {$removeJob->job_number} - Customer continued to contract");
            }
            
            return $cancelledCount;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-cancel remove jobs for JA ID {$jobAdviceId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Suspend job (Job diselesaikan tapi TIDAK ditagih)
     * Requirement: job-operational.md line 16
     */
    public function suspend(Request $request, JobSchedule $jobSchedule)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobSchedule->suspend();
            
            if ($request->has('notes')) {
                $jobSchedule->update([
                    'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[SUSPEND] " . $request->notes,
                    'updated_by' => Auth::id()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Job suspended successfully (will NOT be invoiced)',
                'data' => $jobSchedule->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error suspending job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark job as DPF (Done but Force-charged - Job diselesaikan tapi TETAP ditagih)
     * Requirement: job-operational.md line 16
     */
    public function markAsDpf(Request $request, JobSchedule $jobSchedule)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobSchedule->markAsDpf();
            
            if ($request->has('notes')) {
                $jobSchedule->update([
                    'internal_notes' => ($jobSchedule->internal_notes ?? '') . "\n[DPF] " . $request->notes,
                    'updated_by' => Auth::id()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Job marked as DPF successfully (will be invoiced)',
                'data' => $jobSchedule->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error marking job as DPF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MOM13: Create first service job together with install job
     * "service pertama itu di buat bersama dengan install"
     */
    private function createFirstServiceWithInstall(JobSchedule $installJob, $jobAdvice, $request)
    {
        try {
            // Check if first service already exists
            $existingFirstService = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->where('type', 'service')
                ->where('period', 1)
                ->first();
            
            if ($existingFirstService) {
                \Log::info("First service already exists for Job Advice {$jobAdvice->job_advice_number}. Skipping.");
                return null;
            }
            
            // Load rental product to get service frequency
            $jobAdvice->load(['rooms.rentalProduct.serviceFrequency', 'contract']);
            $installJob->loadMissing('jobScheduleRooms');
            $eligibleRoomIds = $installJob->jobScheduleRooms
                ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                ->pluck('room_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($eligibleRoomIds)) {
                \Log::warning("No completed install rooms found for {$installJob->job_number}. Skipping first service generation.");
                return;
            }
            
            // Get service frequency from rental
            $serviceFrequency = null;
            $servicePeriodType = 'monthly';
            $frequencyMonths = 1;
            $frequencyTimes = 1;
            
            if ($jobAdvice->rooms && $jobAdvice->rooms->isNotEmpty()) {
                $firstRoom = $jobAdvice->rooms->first();
                if ($firstRoom->rentalProduct && $firstRoom->rentalProduct->serviceFrequency) {
                    $sf = $firstRoom->rentalProduct->serviceFrequency;
                    $serviceFrequency = $sf->frequency_times_per_month ?? 1;
                    $frequencyMonths = $sf->frequency_months ?? 1;
                    $frequencyTimes = $sf->frequency_times_per_month ?? 1;
                    
                    if ($frequencyMonths == 1) $servicePeriodType = 'monthly';
                    elseif ($frequencyMonths == 2) $servicePeriodType = 'bi_monthly';
                    elseif ($frequencyMonths == 3) $servicePeriodType = 'quarterly';
                    elseif ($frequencyMonths == 6) $servicePeriodType = 'semi_annually';
                    elseif ($frequencyMonths >= 12) $servicePeriodType = 'annually';
                }
            }
            
            // Calculate first service date
            $installDate = $request->schedule_date ?? now();
            $baseInstallDate = \Carbon\Carbon::parse($installDate);
            
            if ($frequencyTimes > 1) {
                // High Frequency (e.g. 1 Month 4x) - Interval in Days
                // Formula: (30 days * frequency_months) / frequency_times
                $daysInPeriod = 30 * $frequencyMonths;
                $intervalDays = floor($daysInPeriod / $frequencyTimes);
                // First service is 1 interval after install
                $firstServiceDate = $baseInstallDate->copy()->addDays($intervalDays);
            } else {
                // Standard Frequency (e.g. Monthly) - Interval in Months
                $firstServiceDate = $baseInstallDate->copy()->addMonths($frequencyMonths);
            }
            
            // MOM14 Fix: New Job should not have a Job Number until assigning team
            $jobNumber = null;
            
            // Build room list
            $roomNames = $jobAdvice->rooms
                ->filter(fn ($jaRoom) => in_array((int) $this->getJobAdviceRoomPhysicalRoomId($jaRoom), array_map('intval', $eligibleRoomIds), true))
                ->pluck('room_name')
                ->filter()
                ->toArray();
            $roomListNote = count($roomNames) > 0 
                ? "\n[Rooms: " . implode(', ', $roomNames) . "]"
                : '';
            
            // Create first service schedule
            $firstService = JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => 'service',
                'status' => 'scheduled',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $installJob->building_id,
                'company_name' => $installJob->company_name,
                'contract_number' => $installJob->contract_number,
                'quotation_number' => $installJob->quotation_number,
                'room_id' => null,
                'schedule_date' => $firstServiceDate,
                'expected_date' => $firstServiceDate,
                'period' => 1,
                'service_frequency' => $serviceFrequency,
                'service_period_type' => $servicePeriodType,
                'internal_notes' => "First service (created with install job {$installJob->job_number}){$roomListNote}",
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            
            $this->syncJobScheduleRoomsFromJobAdvice($firstService, $jobAdvice, 'service_job_schedule_id');
            
            \Log::info("MOM13: Created first service {$firstService->job_number} together with install {$installJob->job_number}");
            
            return $firstService;
            
        } catch (\Exception $e) {
            \Log::error("Failed to create first service with install: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * MOM13: Generate ALL remaining services when first service completes
     * "setelah service pertama selesai dia akan generate sisa 11 service nya"
     */
    private function generateAllRemainingServices(JobSchedule $completedFirstService, $jobAdvice)
    {
        try {
            // Only run for period 1 (first service)
            if ($completedFirstService->period != 1) {
                \Log::info("Not first service (period {$completedFirstService->period}). Skipping bulk service generation.");
                return;
            }
            
            // Get total expected services
            $totalServices = $this->getTotalExpectedServices($jobAdvice);
            
            if (!$totalServices || $totalServices <= 1) {
                \Log::info("Total services is {$totalServices}. No remaining services to generate.");
                return;
            }
            
            // Check how many services already exist
            $existingServicesCount = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->whereIn('type', ['service', 'service_first', 'service_routine'])
                ->count();
            
            if ($existingServicesCount >= $totalServices) {
                \Log::info("All {$totalServices} services already exist. Skipping.");
                return;
            }
            
            // Get service frequency info
            $serviceFrequency = $completedFirstService->service_frequency; // Usually times per month (e.g. 4)
            $servicePeriodType = $completedFirstService->service_period_type;
            $completedFirstService->loadMissing('jobScheduleRooms');
            $eligibleRoomIds = $completedFirstService->jobScheduleRooms
                ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                ->pluck('room_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($eligibleRoomIds)) {
                \Log::warning("No completed rooms found for first service {$completedFirstService->job_number}. Skipping remaining service generation.");
                return;
            }
            
            $frequencyMonths = match($servicePeriodType) {
                'monthly' => 1,
                'bi_monthly' => 2,
                'quarterly' => 3,
                'semi_annually' => 6,
                'annually' => 12,
                default => 1
            };
            
            // Base date is the first service date
            $baseDate = \Carbon\Carbon::parse($completedFirstService->schedule_date);
            
            // Build room list
            $roomNames = $jobAdvice->rooms
                ->filter(fn ($jaRoom) => in_array((int) $this->getJobAdviceRoomPhysicalRoomId($jaRoom), array_map('intval', $eligibleRoomIds), true))
                ->pluck('room_name')
                ->filter()
                ->toArray();
            $roomListNote = count($roomNames) > 0 
                ? "\n[Rooms: " . implode(', ', $roomNames) . "]"
                : '';
            
            $servicesCreated = [];
            
            // Determince Interval Logic
            $frequencyTimes = is_numeric($serviceFrequency) ? (int)$serviceFrequency : 1;
            $useDayInterval = $frequencyTimes > 1;
            $intervalDays = 0;
            
            if ($useDayInterval) {
                 // Formula: (30 days * frequency_months) / frequency_times
                 $daysInPeriod = 30 * $frequencyMonths;
                 $intervalDays = floor($daysInPeriod / $frequencyTimes);
            }
            
            // Generate remaining services (period 2 to totalServices)
            for ($period = 2; $period <= $totalServices; $period++) {
                // Check if this period already exists
                $existing = JobSchedule::where('job_advice_id', $jobAdvice->id)
                    ->whereIn('type', ['service', 'service_first', 'service_routine'])
                    ->where('period', $period)
                    ->first();
                
                if ($existing) {
                    continue;
                }
                
                // Calculate service date
                if ($useDayInterval) {
                     // Interval based on days
                     $serviceDate = $baseDate->copy()->addDays(($period - 1) * $intervalDays);
                } else {
                     // Interval based on months
                     $serviceDate = $baseDate->copy()->addMonths(($period - 1) * $frequencyMonths);
                }
                
                // MOM14 Fix: New Job should not have a Job Number until assigning team
                $jobNumber = null;
                
                $service = JobSchedule::create([
                    'job_number' => $jobNumber,
                    'type' => 'service',
                    'status' => 'scheduled',
                    'job_advice_id' => $jobAdvice->id,
                    'building_id' => $completedFirstService->building_id,
                    'company_name' => $completedFirstService->company_name,
                    'contract_number' => $completedFirstService->contract_number,
                    'quotation_number' => $completedFirstService->quotation_number,
                    'room_id' => null,
                    'schedule_date' => $serviceDate,
                    'expected_date' => $serviceDate,
                    'period' => $period,
                    'service_frequency' => $serviceFrequency,
                    'service_period_type' => $servicePeriodType,
                    'internal_notes' => "Service period {$period}/{$totalServices} (auto-generated after first service){$roomListNote}",
                    'created_by' => Auth::id() ?? \App\Models\User::first()?->id,
                    'updated_by' => Auth::id() ?? \App\Models\User::first()?->id
                ]);
                
                $this->syncJobScheduleRoomsFromJobAdvice($service, $jobAdvice, 'service_job_schedule_id', $eligibleRoomIds);
                
                $servicesCreated[] = $service;
            }
            
            \Log::info("MOM13: Generated " . count($servicesCreated) . " remaining services (period 2-{$totalServices}) for JA {$jobAdvice->job_advice_number}");
            
            return $servicesCreated;
            
        } catch (\Exception $e) {
            \Log::error("Failed to generate remaining services: " . $e->getMessage());
        }
    }
    
    /**
     * DEPRECATED: Auto-generate first service schedule when install job is completed
     * Now first service is created together with install job
     */
    private function autoGenerateFirstServiceSchedule(JobSchedule $completedInstallJob, $jobAdvice)
    {
        try {
            // Check if first service already exists
            $existingFirstService = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->where('type', 'service')
                ->where('period', 1)
                ->first();
            
            if ($existingFirstService) {
                \Log::info("First service schedule already exists for Job Advice {$jobAdvice->job_advice_number}. Skipping.");
                return;
            }
            
            // Load rental product to get service frequency
            $jobAdvice->load(['rooms.rentalProduct.serviceFrequency', 'contract']);
            
            // Get service frequency from rental
            $serviceFrequency = null;
            $servicePeriodType = 'monthly'; // default
            
            if ($jobAdvice->rooms && $jobAdvice->rooms->isNotEmpty()) {
                $firstRoom = $jobAdvice->rooms->first();
                if ($firstRoom->rentalProduct && $firstRoom->rentalProduct->serviceFrequency) {
                    $sf = $firstRoom->rentalProduct->serviceFrequency;
                    $serviceFrequency = $sf->frequency_times_per_month ?? 1;
                    
                    // Determine period type based on frequency_months
                    if ($sf->frequency_months == 1) {
                        $servicePeriodType = 'monthly';
                    } elseif ($sf->frequency_months == 2) {
                        $servicePeriodType = 'bi_monthly';
                    } elseif ($sf->frequency_months == 3) {
                        $servicePeriodType = 'quarterly';
                    } elseif ($sf->frequency_months == 6) {
                        $servicePeriodType = 'semi_annually';
                    } elseif ($sf->frequency_months >= 12) {
                        $servicePeriodType = 'annually';
                    }
                }
            }
            
            // Calculate first service date based on service period type
            $installCompletedDate = $completedInstallJob->completed_at ?? $completedInstallJob->schedule_date ?? now();
            $firstServiceDate = match($servicePeriodType) {
                'monthly' => \Carbon\Carbon::parse($installCompletedDate)->addMonth(),
                'bi_monthly' => \Carbon\Carbon::parse($installCompletedDate)->addMonths(2),
                'quarterly' => \Carbon\Carbon::parse($installCompletedDate)->addMonths(3),
                'semi_annually' => \Carbon\Carbon::parse($installCompletedDate)->addMonths(6),
                'annually' => \Carbon\Carbon::parse($installCompletedDate)->addYear(),
                default => \Carbon\Carbon::parse($installCompletedDate)->addMonth()
            };
            
            // Generate job number
            $jobNumber = $this->generateJobNumber('service', $jobAdvice);
            
            // Build room list for internal_notes
            $roomNames = $jobAdvice->rooms->pluck('room_name')->filter()->toArray();
            $roomListNote = count($roomNames) > 0 
                ? "\n[Rooms: " . implode(', ', $roomNames) . "]"
                : '';
            
            // Create first service schedule
            $firstServiceSchedule = JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => 'service',
                'status' => 'scheduled',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $completedInstallJob->building_id,
                'building_name' => $completedInstallJob->building_name,
                'company_name' => $completedInstallJob->company_name,
                'contract_number' => $completedInstallJob->contract_number,
                'quotation_number' => $completedInstallJob->quotation_number,
                'room_id' => null, // All rooms via job_advice_id
                'schedule_date' => $firstServiceDate,
                'expected_date' => $firstServiceDate,
                'period' => 1, // First service
                'service_frequency' => $serviceFrequency,
                'service_period_type' => $servicePeriodType,
                'internal_notes' => "Auto-generated first service from install job {$completedInstallJob->job_number}.{$roomListNote}",
                'created_by' => Auth::id() ?? \App\Models\User::first()?->id,
                'updated_by' => Auth::id() ?? \App\Models\User::first()?->id
            ]);
            
            // Link only rooms that were actually completed by this install job.
            foreach ($jobAdvice->rooms as $jaRoom) {
                $physicalRoomId = $this->getJobAdviceRoomPhysicalRoomId($jaRoom);
                if (! $physicalRoomId || ! in_array((int) $physicalRoomId, array_map('intval', $eligibleRoomIds), true)) {
                    continue;
                }

                if (!$jaRoom->service_job_schedule_id) {
                    $jaRoom->update(['service_job_schedule_id' => $firstServiceSchedule->id]);
                }
            }
            
            // Calculate service interval
            if ($firstServiceSchedule->service_frequency) {
                $firstServiceSchedule->calculateServiceInterval();
            }
            
            \Log::info("MOM13: Auto-generated first service schedule {$firstServiceSchedule->job_number} for Job Advice {$jobAdvice->job_advice_number} on {$firstServiceDate->format('Y-m-d')}");
            
            return $firstServiceSchedule;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-generate first service schedule for install job {$completedInstallJob->job_number}: " . $e->getMessage());
            // Don't throw - non-critical error
        }
    }
    
    /**
     * Auto-generate next service schedule when service job is completed
     * 
     * "ketika service period nya misal sebulan sekali, dia akan otomatis generate service selanjutnya jika bulannya sudah tiba"
     */
    private function autoGenerateNextServiceSchedule(JobSchedule $completedServiceJob, $jobAdvice)
    {
        try {
            // Check if next service schedule already exists
            $nextServiceDate = $this->calculateNextServiceDate($completedServiceJob);
            
            if (!$nextServiceDate) {
                \Log::info("Cannot calculate next service date for service job {$completedServiceJob->job_number}. Skipping.");
                return;
            }

            // Check if service schedule already exists for this date and job advice
            $existingServiceSchedule = JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->where('type', 'service')
                ->whereDate('schedule_date', $nextServiceDate->toDateString())
                ->first();
            
            if ($existingServiceSchedule) {
                \Log::info("Next service schedule already exists for Job Advice {$jobAdvice->job_advice_number} on {$nextServiceDate->format('Y-m-d')}. Skipping.");
                return;
            }

            // Generate job number with proper CSR code
            $jobNumber = $this->generateJobNumber('service', $jobAdvice);
            
            // MOM8: Calculate next period number (urutan service ke berapa)
            // Period = urutan service (1, 2, 3, dst), bukan service_frequency
            $nextPeriod = null;
            if ($completedServiceJob->period) {
                // If current period is numeric, increment it
                $nextPeriod = is_numeric($completedServiceJob->period) ? ((int)$completedServiceJob->period) + 1 : null;
            } else {
                // If no period set, count existing service jobs for same contract/room
                $contractRoom = null;
                if ($jobAdvice->contract_id && $completedServiceJob->room_id) {
                    // Try to find contract room by room_id
                    $contractRoom = \App\Models\ContractRoom::where('room_id', $completedServiceJob->room_id)
                        ->whereHas('contract', function($q) use ($jobAdvice) {
                            $q->where('id', $jobAdvice->contract_id);
                        })
                        ->first();
                }
                
                if ($contractRoom) {
                    $existingServiceCount = \App\Models\JobSchedule::whereHas('jobAdvice', function($q) use ($jobAdvice) {
                            $q->where('contract_id', $jobAdvice->contract_id);
                        })
                        ->whereHas('jobAdvice.rooms', function($q) use ($contractRoom) {
                            $q->where('contract_room_id', $contractRoom->id);
                        })
                        ->where('type', 'service')
                        ->where('status', 'completed')
                        ->count();
                    $nextPeriod = $existingServiceCount + 1;
                } else {
                    // Fallback: use 1 if cannot determine
                    $nextPeriod = 1;
                }
            }

            $completedServiceJob->loadMissing('jobScheduleRooms');
            $eligibleRoomIds = $completedServiceJob->jobScheduleRooms
                ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                ->pluck('room_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($eligibleRoomIds)) {
                \Log::warning("No completed rooms found for service {$completedServiceJob->job_number}. Skipping next service generation.");
                return;
            }
            
            // Create next service schedule with same details as completed service
            $nextServiceSchedule = JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => 'service',
                'status' => 'scheduled',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $completedServiceJob->building_id,
                'building_name' => $completedServiceJob->building_name,
                'company_name' => $completedServiceJob->company_name,
                'contract_number' => $completedServiceJob->contract_number,
                'room_id' => $completedServiceJob->room_id,
                'schedule_date' => $nextServiceDate,
                'expected_date' => $nextServiceDate,
                'period' => $nextPeriod, // MOM8: Urutan service ke berapa (1, 2, 3, dst), bukan service_frequency
                'service_frequency' => $completedServiceJob->service_frequency, // MOM8: Diambil dari MasterRental
                'service_period_type' => $completedServiceJob->service_period_type, // MOM8: Diambil dari MasterRental
                'internal_notes' => "Auto-generated next service schedule from completed service {$completedServiceJob->job_number}. Service period: {$completedServiceJob->getServicePeriodTypeLabel()}",
                'created_by' => \App\Models\User::first()?->id ?? null, // Use first user or null
                'updated_by' => \App\Models\User::first()?->id ?? null
            ]);

            // Calculate service interval if service frequency is provided
            if ($nextServiceSchedule->service_frequency) {
                $nextServiceSchedule->calculateServiceInterval();
            }

            $this->syncJobScheduleRoomsFromJobAdvice($nextServiceSchedule, $jobAdvice, 'service_job_schedule_id', $eligibleRoomIds);
            
            \Log::info("Auto-generated next service schedule {$nextServiceSchedule->job_number} for JA {$jobAdvice->job_advice_number} on {$nextServiceDate->format('Y-m-d')}");
            
            return $nextServiceSchedule;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-generate next service schedule for service job {$completedServiceJob->job_number}: " . $e->getMessage());
            // Don't throw - non-critical error
        }
    }

    /**
     * Calculate next service date based on service period type or frequency
     */
    private function calculateNextServiceDate(JobSchedule $completedServiceJob)
    {
        if (!$completedServiceJob->schedule_date) {
            return null;
        }

        $baseDate = $completedServiceJob->schedule_date->copy();
        
        // Use service_period_type if available (monthly, bi_monthly, quarterly, etc.)
        if ($completedServiceJob->service_period_type) {
            switch ($completedServiceJob->service_period_type) {
                case 'monthly':
                    return $baseDate->copy()->addMonth();
                case 'bi_monthly':
                    return $baseDate->copy()->addMonths(2);
                case 'quarterly':
                    return $baseDate->copy()->addMonths(3);
                case 'semi_annually':
                    return $baseDate->copy()->addMonths(6);
                case 'annually':
                    return $baseDate->copy()->addYear();
                default:
                    return $baseDate->copy()->addMonth(); // Default to monthly
            }
        }
        
        // Fallback: Use service_frequency if service_period_type is not available
        if ($completedServiceJob->service_frequency) {
            // Calculate based on service frequency (days per service)
            $intervalDays = $completedServiceJob->calculateServiceInterval();
            if ($intervalDays) {
                return $baseDate->copy()->addDays($intervalDays);
            }
            
            // Fallback calculation based on frequency number
            // service_frequency = 1 means once per month, 2 means twice per month, etc.
            $daysInMonth = 30; // Approximate
            $intervalDays = ceil($daysInMonth / $completedServiceJob->service_frequency);
            return $baseDate->copy()->addDays($intervalDays);
        }
        
        return null;
    }
    
    /**
     * MOM13: Generate job number with proper type code
     * IR = Installation Report (install)
     * IF = Installation Free (install_free)
     * CSR = Customer Service Report (service)
     * RV = Remove
     * RF = Remove Free
     */
    private function generateJobNumber($type, $jobAdvice = null)
    {
        $typeLower = strtolower($type ?? '');
        $jobAdviceType = strtolower($jobAdvice->type ?? '');

        $documentType = match ($typeLower) {
            'install', 'install_free' => ($jobAdviceType === 'install_free' || $typeLower === 'install_free')
                ? 'installation_free'
                : 'installation_report',
            'service', 'service_first', 'service_routine' => 'customer_service_report',
            'remove', 'removal' => 'remove',
            'remove_free', 'remove free' => 'remove_free',
            default => 'job_schedule',
        };

        return app(\App\Services\DocumentNumberService::class)->generate(
            $documentType,
            null,
            null,
            $jobAdvice?->contract_id,
            $jobAdvice?->quotation_id
        );
    }
    
    /**
     * Helper to activate a job (status, job number, primary assignment) when a room is assigned a team.
     */
    private function activateJobFromRoomAssignment($job, $teamId)
    {
        // VALIDATION: Check if we can assign team
        $validation = $this->validateMakeAssignTeam($job);
        if ($validation !== true) {
            // Return validation error - caller should handle this
            return $validation;
        }

        $documentNumberService = new \App\Services\DocumentNumberService();
        $documentTypeMap = [
            'install' => 'installation_report',
            'install_free' => 'installation_free',
            'service' => 'customer_service_report',
            'service_first' => 'customer_service_report',
            'service_routine' => 'customer_service_report',
            'remove' => 'remove',
            'remove_free' => 'remove_free',
            'remove free' => 'remove_free',
            'maintenance' => 'job_schedule',
            'extra' => 'job_schedule',
            'change' => 'job_schedule',
            'complain' => 'job_schedule',
        ];

        // 1. Ensure JobAssignSchedule exists (Primary Team)
        // This is needed for autoCreateMaterialIssue and mobile visibility
        $jobAssignSchedule = \App\Models\JobAssignSchedule::where('job_schedule_id', $job->id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (!$jobAssignSchedule) {
            $jobAssignSchedule = \App\Models\JobAssignSchedule::create([
                'job_schedule_id' => $job->id,
                'team_id' => $teamId,
                'assigned_by' => auth()->id(),
                'assigned_date' => now()->toDateString(),
                'status' => 'assigned',
                'created_by' => auth()->id()
            ]);
            \Log::info("✅ Created primary JobAssignSchedule for Job #{$job->id} via Room Assignment (Team #{$teamId})");
        }

        // 2. Update status if it's new or needs to climb to assign_team
        // MOM: Handle climbing from material statuses, but PREVENT regression from advanced statuses
        $needsAssignTeamStatus = ['new_job', 'scheduled', 'assign_material', 'barang_siap_diambil', 'barang_dipersiapkan'];
        if (in_array($job->status, $needsAssignTeamStatus)) {
            $job->update(['status' => 'assign_team']);
        }

        // 3. Generate Job Number if missing or needs to be synchronized (Shared Job Number Logic)
        if (!$job->job_number) {
            $sharedJobNumber = null;
            
            // MOM: Try to find an existing Job Number from siblings (Same Context + Same Date + Same Team)
            // This enables merging of multiple rooms into one Job Number if assigned to same team on same day
            
            $date = $job->schedule_date ? $job->schedule_date->format('Y-m-d') : null;
            
            if ($date) {
                // Look for ANY job that matches context and has a job number AND is assigned to this team
                $existingJob = \App\Models\JobSchedule::where('job_advice_id', $job->job_advice_id)
                    ->where('building_id', $job->building_id)
                    ->where('type', $job->type)
                    ->whereDate('schedule_date', $date)
                    ->where('id', '!=', $job->id)
                    ->whereNotNull('job_number')
                    ->whereHas('jobAssignSchedules', function($q) use ($teamId) {
                        $q->where('team_id', $teamId)
                          ->where('status', '!=', 'cancelled');
                    })
                    ->first();
                    
                if ($existingJob) {
                    $sharedJobNumber = $existingJob->job_number;
                    \Log::info("✅ reuse existing Shared Job Number {$sharedJobNumber} from Job #{$existingJob->id} for Job #{$job->id} (Team Match)");
                }
            }
            
            // If still no shared number found, generate NEW
            if (!$sharedJobNumber) {
                $documentType = $documentTypeMap[$job->type] ?? 'job_schedule';
                $sharedJobNumber = $documentNumberService->generate(
                    $documentType,
                    null,
                    $job->building_id,
                    $job->jobAdvice?->contract_id ?? null,
                    $job->jobAdvice?->quotation_id ?? null,
                    null,
                    null
                );
                \Log::info("✅ Generated NEW Shared Job Number {$sharedJobNumber} for Job #{$job->id}");
            }
            
            $job->update(['job_number' => $sharedJobNumber, 'assign_date' => now()->toDateString()]);
        }

        // 4. Trigger Material Issue creation (idempotent inside autoCreateMaterialIssue)
        // REMOVED: Decoupled per user request. Use 'Material Assign' action instead.
        // $this->autoCreateMaterialIssue($jobAssignSchedule);

        // 5. Sync Team to Inventory Issuing
        $this->syncTeamToInventoryIssuing($job->id, $teamId);
    }

    private function ensureAssignedJobNumber(JobSchedule $job, int $teamId): void
    {
        if ($job->job_number) {
            return;
        }

        $documentNumberService = new \App\Services\DocumentNumberService();
        $documentTypeMap = [
            'install' => 'installation_report',
            'install_free' => 'installation_free',
            'service' => 'customer_service_report',
            'service_first' => 'customer_service_report',
            'service_routine' => 'customer_service_report',
            'remove' => 'remove',
            'remove_free' => 'remove_free',
            'remove free' => 'remove_free',
            'maintenance' => 'job_schedule',
            'extra' => 'job_schedule',
            'change' => 'job_schedule',
            'complain' => 'job_schedule',
        ];

        $sharedJobNumber = null;
        $date = $job->schedule_date ? $job->schedule_date->format('Y-m-d') : null;

        if ($date) {
            $existingJob = \App\Models\JobSchedule::where('job_advice_id', $job->job_advice_id)
                ->where('building_id', $job->building_id)
                ->where('type', $job->type)
                ->whereDate('schedule_date', $date)
                ->where('id', '!=', $job->id)
                ->whereNotNull('job_number')
                ->whereHas('jobAssignSchedules', function($q) use ($teamId) {
                    $q->where('team_id', $teamId)
                        ->where('status', '!=', 'cancelled');
                })
                ->first();

            if ($existingJob) {
                $sharedJobNumber = $existingJob->job_number;
                \Log::info("âœ… reuse existing Shared Job Number {$sharedJobNumber} from Job #{$existingJob->id} for Job #{$job->id} (Team Match)");
            }
        }

        if (!$sharedJobNumber) {
            $documentType = $documentTypeMap[$job->type] ?? 'job_schedule';
            $sharedJobNumber = $documentNumberService->generate(
                $documentType,
                null,
                $job->building_id,
                $job->jobAdvice?->contract_id ?? null,
                $job->jobAdvice?->quotation_id ?? null,
                null,
                null
            );
            \Log::info("âœ… Generated NEW Shared Job Number {$sharedJobNumber} for Job #{$job->id}");
        }

        $job->update([
            'job_number' => $sharedJobNumber,
            'assign_date' => $job->assign_date ?: now()->toDateString(),
        ]);
    }

    /**
     * STUDY CASE B2: Bulk Update Room Assignments
     */
    public function bulkUpdateRoomAssignment(Request $request)
    {
        $request->validate([
            'room_ids' => 'required|array',
            // 'room_ids.*' => 'exists:job_schedule_rooms,id',
            'team_id' => 'required|exists:teams,id',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $successCount = 0;
            $updatedJobs = [];
            
            // PRE-VALIDATION: Check all jobs BEFORE making any changes
            foreach ($request->room_ids as $mixedId) {
                $jobSchedule = null;
                
                if (str_starts_with($mixedId, 'job_')) {
                    $jobId = substr($mixedId, 4);
                    $jobSchedule = JobSchedule::find($jobId);
                } else {
                    $jobScheduleRoom = \App\Models\JobScheduleRoom::find($mixedId);
                    if ($jobScheduleRoom) {
                        $jobSchedule = $jobScheduleRoom->jobSchedule;
                    }
                }
                
                if ($jobSchedule) {
                    $validation = $this->validateMakeAssignTeam($jobSchedule);
                    if ($validation !== true) {
                        DB::rollback();
                        return response()->json($validation, 422);
                    }
                }
            }

            // MOM FIX: PHYSICAL JOB SPLITTING
            // If the user only selected a subset of rooms from a JobSchedule, 
            // we physically CLONE the JobSchedule into a new row and move the selected rooms.
            // This guarantees the Mobile App API doesn't share global Job state.
            $jobsToSplit = [];
            $selectedRoomIds = [];
            
            foreach ($request->room_ids as $mixedId) {
                if (!str_starts_with($mixedId, 'job_')) {
                    $selectedRoomIds[] = (int) $mixedId;
                }
            }
            
            foreach ($selectedRoomIds as $roomId) {
                $room = \App\Models\JobScheduleRoom::find($roomId);
                if ($room && $room->job_schedule_id) {
                    $jobsToSplit[$room->job_schedule_id][] = $roomId;
                }
            }
            
            foreach ($jobsToSplit as $jobId => $assignedRoomIds) {
                $jobSchedule = JobSchedule::with('jobScheduleRooms')->find($jobId);
                if (!$jobSchedule) continue;
                
                $allRoomIds = $jobSchedule->jobScheduleRooms->pluck('id')->toArray();
                $unselectedRoomIds = array_diff($allRoomIds, $assignedRoomIds);
                
                // If NOT ALL rooms are selected, split the SELECTED rooms into a Clone!
                // The parent JobSchedule safely retains all history/assignments for unselected rooms.
                if (!empty($unselectedRoomIds)) {
                    
                    // Clone the Job
                    $clonedJob = $jobSchedule->replicate();
                    $clonedJob->ba_date = null;
                    $clonedJob->ba_number = null;
                    $clonedJob->completed_at = null;
                    $clonedJob->save();
                    
                    // Move SELECTED rooms to the cloned job!
                    \App\Models\JobScheduleRoom::whereIn('id', $assignedRoomIds)
                        ->update(['job_schedule_id' => $clonedJob->id]);
                    
                }
            }

            foreach ($request->room_ids as $mixedId) {
                // 1. Handle Legacy Job Assignment (Prefix 'job_')
                if (str_starts_with($mixedId, 'job_')) {
                    $jobId = substr($mixedId, 4);
                    $jobSchedule = JobSchedule::find($jobId);
                    
                    if (!$jobSchedule) continue;
                    
                    // Logic mirrored from assignToTeam
                    $jobAssignSchedule = \App\Models\JobAssignSchedule::create([
                        'job_schedule_id' => $jobSchedule->id,
                        'team_id' => $request->team_id,
                        'assigned_by' => Auth::id(),
                        'assigned_date' => now(),
                        'status' => 'assigned',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                    
                    if (in_array($jobSchedule->status, ['new_job', 'scheduled', 'assign_material', 'barang_siap_diambil', 'barang_dipersiapkan'])) {
                        $jobSchedule->update([
                            'status' => 'assign_team',
                            'updated_by' => Auth::id()
                        ]);
                    }

                    $this->ensureAssignedJobNumber($jobSchedule, (int) $request->team_id);

                    // NEW VALIDATION: Check if we can assign team
                    // Note: We check AFTER creation because we need the Job object, but ideally we check before.
                    // For legacy jobs, they might not have external dependencies so we check if strict validation is needed.
                    $validation = $this->validateMakeAssignTeam($jobSchedule);
                    if ($validation !== true) {
                        DB::rollback();
                        return response()->json($validation, 422);
                    }
                    
                    // REMOVED: Decoupled per user request. Use 'Material Assign' action instead.
                    // $this->autoCreateMaterialIssue($jobAssignSchedule);
                    
                    $successCount++;
                    continue; 
                }

                // 2. Handle Room Assignment (Standard ID)
                $roomId = $mixedId;

                // Find job schedule room
                $jobScheduleRoom = \App\Models\JobScheduleRoom::find($roomId);
                if (!$jobScheduleRoom) continue;

                $jobSchedule = $jobScheduleRoom->jobSchedule;
                if (!$jobSchedule) continue;

                // Check if job schedule status allows editing
                // MOM: Whitelist advanced statuses to allow re-assignment
                $allowTeamsStatuses = ['assign_team', 'new_job', 'scheduled', 'barang_siap_diambil', 'barang_diambil', 'assign_material', 'barang_dipersiapkan', 'in_progress', 'teknisi_tiba_dilokasi', 'meninggalkan_lokasi', 'suspend', 'dpf'];
                if (!in_array($jobSchedule->status, $allowTeamsStatuses)) {
                    continue;
                }

                // Logic from updateRoomAssignment
                $roomAssignment = \App\Models\JobScheduleRoomAssignment::withTrashed()
                    ->where('job_schedule_id', $jobSchedule->id)
                    ->where('job_schedule_room_id', $jobScheduleRoom->id)
                    ->first();

                if ($roomAssignment) {
                    if ($roomAssignment->trashed()) $roomAssignment->restore();
                    
                    $roomAssignment->update([
                        'job_advice_room_id' => $jobScheduleRoom->job_advice_room_id,
                        'team_id' => $request->team_id,
                        'is_custom' => true,
                        'job_assign_schedule_id' => null,
                        'assigned_by' => Auth::id(),
                        'assigned_date' => now()->toDateString(),
                        'status' => 'assigned',
                        'notes' => $request->notes ?? 'Custom bulk room assignment',
                        'updated_by' => Auth::id(),
                    ]);
                } else {
                    $roomAssignment = \App\Models\JobScheduleRoomAssignment::create([
                        'job_schedule_id' => $jobSchedule->id,
                        'job_schedule_room_id' => $jobScheduleRoom->id,
                        'job_advice_room_id' => $jobScheduleRoom->job_advice_room_id,
                        'team_id' => $request->team_id,
                        'is_custom' => true,
                        'job_assign_schedule_id' => null,
                        'assigned_by' => Auth::id(),
                        'assigned_date' => now()->toDateString(),
                        'status' => 'assigned',
                        'notes' => $request->notes ?? 'Custom bulk room assignment',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }

                $successCount++;
                $updatedJobs[$jobSchedule->id] = $jobSchedule;
            }

            // Cleanup old assignments and activate jobs
            foreach ($updatedJobs as $jobId => $job) {
                $this->cleanupOldTeamAssignment($job, $request->team_id);
                $activationResult = $this->activateJobFromRoomAssignment($job, $request->team_id);
                
                // Check if activation returned validation error
                if ($activationResult !== true && is_array($activationResult)) {
                    DB::rollback();
                    return response()->json($activationResult, 422);
                }
            }

            DB::commit();

            // MOM: Bidirectional Sync - Update related Inventory Issuing records
            try {
                $jobIds = array_keys($updatedJobs);
                if (!empty($jobIds)) {
                    $service = new \App\Services\Warehouse\InventoryIssuingService();
                    $service->syncTeamFromJobSchedule($jobIds, $request->team_id);
                }
            } catch (\Exception $e) {
                \Log::error("Bidirectional Sync Error (Bulk): " . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully updated assignment for {$successCount} room(s).",
                'success' => true
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ Failed to bulk update room assignments: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk update room assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * STUDY CASE B2: Update per-room assignment (custom assignment override)
     */
    public function updateRoomAssignment(Request $request, JobSchedule $jobSchedule, $roomId)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'notes' => 'nullable|string|max:500'
        ]);

        // Check if job schedule status allows editing
        // MOM: Whitelist advanced statuses to allow re-assignment
        $allowTeamsStatuses = ['assign_team', 'new_job', 'scheduled', 'barang_siap_diambil', 'barang_diambil', 'assign_material', 'barang_dipersiapkan', 'in_progress', 'teknisi_tiba_dilokasi', 'meninggalkan_lokasi', 'suspend', 'dpf'];
        if (!in_array($jobSchedule->status, $allowTeamsStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room assignment cannot be edited for this status.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Find job schedule room
            $jobScheduleRoom = \App\Models\JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)
                ->where('id', $roomId)
                ->firstOrFail();

            // Find existing room assignment (may have been created by global assignment)
            // Use withTrashed to handle soft deleted records
            $roomAssignment = \App\Models\JobScheduleRoomAssignment::withTrashed()
                ->where('job_schedule_id', $jobSchedule->id)
                ->where('job_schedule_room_id', $jobScheduleRoom->id)
                ->first();

            if ($roomAssignment) {
                // If soft deleted, restore it first
                if ($roomAssignment->trashed()) {
                    $roomAssignment->restore();
                }
                
                // Update existing assignment (convert from global to custom if needed)
                $roomAssignment->update([
                    'job_advice_room_id' => $jobScheduleRoom->job_advice_room_id,
                    'team_id' => $request->team_id,
                    'is_custom' => true, // Mark as custom assignment (override global)
                    'job_assign_schedule_id' => null, // Remove link to global assignment
                    'assigned_by' => Auth::id(),
                    'assigned_date' => now()->toDateString(),
                    'status' => 'assigned',
                    'notes' => $request->notes ?? 'Custom room assignment',
                    'updated_by' => Auth::id(),
                ]);
            } else {
                // Create new assignment (if no global assignment exists)
                $roomAssignment = \App\Models\JobScheduleRoomAssignment::create([
                    'job_schedule_id' => $jobSchedule->id,
                    'job_schedule_room_id' => $jobScheduleRoom->id,
                    'job_advice_room_id' => $jobScheduleRoom->job_advice_room_id,
                    'team_id' => $request->team_id,
                    'is_custom' => true, // Mark as custom assignment
                    'job_assign_schedule_id' => null, // No link to global assignment
                    'assigned_by' => Auth::id(),
                    'assigned_date' => now()->toDateString(),
                    'status' => 'assigned',
                    'notes' => $request->notes ?? 'Custom room assignment',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            // MOM: After reassigning room, check if old team has no remaining rooms
            // If so, cancel their JobAssignSchedule so they lose job in mobile app
            $this->cleanupOldTeamAssignment($jobSchedule, $request->team_id);

            // Activate job (generate number, update status, create material issue)
            $this->activateJobFromRoomAssignment($jobSchedule, $request->team_id);

            DB::commit();

            \Log::info("✅ STUDY CASE B2: Updated custom room assignment for Room {$jobScheduleRoom->room_name} in JobSchedule {$jobSchedule->job_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Room assignment updated successfully.',
                'data' => $roomAssignment->load(['team', 'assignedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ STUDY CASE B2: Failed to update room assignment: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update room assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * STUDY CASE B2: Remove custom room assignment (revert to global)
     */
    public function removeRoomAssignment(Request $request, JobSchedule $jobSchedule, $roomId)
    {
        // Check if job schedule status allows editing (only if status = assign_team)
        if ($jobSchedule->status !== 'assign_team') {
            return response()->json([
                'status' => 'error',
                'message' => 'Room assignment can only be edited when job schedule status is "assign_team".'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Find job schedule room
            $jobScheduleRoom = \App\Models\JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)
                ->where('id', $roomId)
                ->firstOrFail();

            // Delete custom assignment (will revert to global assignment)
            $deleted = \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)
                ->where('job_schedule_room_id', $jobScheduleRoom->id)
                ->where('is_custom', true)
                ->delete();

            DB::commit();

            \Log::info("✅ STUDY CASE B2: Removed custom room assignment for Room {$jobScheduleRoom->room_name} in JobSchedule {$jobSchedule->job_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Custom room assignment removed. Room will use global assignment.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ STUDY CASE B2: Failed to remove room assignment: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove room assignment: ' . $e->getMessage()
            ], 500);
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

    /**
     * MOM: Cleanup old team JobAssignSchedule when room is reassigned
     * 
     * When a room is reassigned to a new team, check if the old team
     * still has any remaining room assignments. If not, cancel their
     * JobAssignSchedule so they lose the job in mobile app.
     * 
     * @param JobSchedule $jobSchedule
     * @param int $newTeamId The team ID that was just assigned
     */
    private function cleanupOldTeamAssignment(JobSchedule $jobSchedule, int $newTeamId)
    {
        // Get all current room assignments for this job
        $roomAssignments = \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)
            ->whereNull('deleted_at')
            ->get();
        
        // Get unique team IDs currently assigned to rooms
        $activeTeamIds = $roomAssignments->pluck('team_id')->filter()->unique()->toArray();
        
        // Get all JobAssignSchedule records for this job
        $jobAssignSchedules = \App\Models\JobAssignSchedule::where('job_schedule_id', $jobSchedule->id)
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->get();
        
        foreach ($jobAssignSchedules as $assignSchedule) {
            // If this team is no longer assigned to any room, cancel their assignment
            if (!in_array($assignSchedule->team_id, $activeTeamIds)) {
                $assignSchedule->update([
                    'status' => 'cancelled',
                    'notes' => ($assignSchedule->notes ?? '') . "\n[CANCELLED] Team reassigned - no remaining rooms assigned to this team",
                    'updated_by' => Auth::id()
                ]);
                
                \Log::info("📱 MOM: Old team {$assignSchedule->team_id} assignment cancelled for Job {$jobSchedule->job_number} - no remaining rooms");
            }
        }
        
        // Check if new team already has JobAssignSchedule, if not create one
        $newTeamAssignment = \App\Models\JobAssignSchedule::withTrashed()
            ->where('job_schedule_id', $jobSchedule->id)
            ->where('team_id', $newTeamId)
            ->first();
        
        if (!$newTeamAssignment) {
            // Create new JobAssignSchedule for the new team so they see job in mobile app
            \App\Models\JobAssignSchedule::create([
                'job_schedule_id' => $jobSchedule->id,
                'team_id' => $newTeamId,
                'assigned_date' => now()->toDateString(),
                'status' => 'assigned',
                'notes' => 'Auto-created from room reassignment',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            
        } elseif ($newTeamAssignment->trashed() || $newTeamAssignment->status === 'cancelled') {
            // Reactivate if previously cancelled
            if ($newTeamAssignment->trashed()) {
                $newTeamAssignment->restore();
            }

            $newTeamAssignment->update([
                'status' => 'assigned',
                'notes' => ($newTeamAssignment->notes ?? '') . "\n[REACTIVATED] Team reassigned to this job",
                'updated_by' => Auth::id()
            ]);
            
        }
    }
    private function generateTransactionCode(JobSchedule $jobSchedule, $typeCode)
    {
        $branchCode = 'JKT'; // Default branch code matching generateBANumber
        $yearMonth = date('y-m');
        $prefix = "{$branchCode}-{$typeCode}/{$yearMonth}";
        
        $count = JobSchedule::withTrashed()
            ->where('job_reference_number', 'like', "{$prefix}/%")
            ->count();
            
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}/{$sequence}";
    }

    /**
     * Assign team to job schedule and auto-create material issue
     * 
     * Flow:
     * 1. Create JobAssignSchedule
     * 2. Update job schedule status to "assigned team"
     * 3. Auto-create Material Issue items from JobAdvice materials
     * 
     * @param Request $request
     * @param JobSchedule $jobSchedule
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignToTeam(Request $request, JobSchedule $jobSchedule)
    {
        try {
            $request->validate([
                'team_id' => 'required|exists:teams,id'
            ]);

            DB::beginTransaction();


            // 1. & 2. Validation: Use shared validation method
            $validation = $this->validateMakeAssignTeam($jobSchedule);
            if ($validation !== true) {
                return response()->json($validation, 422);
            }

            // REFACTORED: Use shared validation method
            $validation = $this->validateMakeAssignTeam($jobSchedule);
            if ($validation !== true) {
                return response()->json($validation, 422);
            }

            // 3. Create JobAssignSchedule
            $jobAssignSchedule = \App\Models\JobAssignSchedule::create([
                'job_schedule_id' => $jobSchedule->id,
                'team_id' => $request->team_id,
                'assigned_by' => Auth::id(),
                'assigned_date' => now(),
                'status' => 'assigned',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            \Log::info('JobAssignSchedule created', [
                'id' => $jobAssignSchedule->id,
                'job_schedule_id' => $jobSchedule->id,
                'team_id' => $request->team_id
            ]);

            // 2. Update job schedule status
            $jobSchedule->update([
                'status' => 'assign_team',
                'updated_by' => Auth::id()
            ]);

            // 3. Auto-create Material Issue from JobAdvice materials
            // REMOVED: Decoupled per user request. Use 'Material Assign' action instead.
            // $this->autoCreateMaterialIssue($jobAssignSchedule);

            DB::commit();

            // 4. Sync Team to Inventory Issuing (in case re-assigned)
            $this->syncTeamToInventoryIssuing($jobSchedule->id, $request->team_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Team assigned successfully and material issue created',
                'data' => $jobAssignSchedule->load('team')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error assigning team to job schedule: ' . $e->getMessage(), [
                'job_schedule_id' => $jobSchedule->id,
                'team_id' => $request->team_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign team: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-create Material Issue header and items from JobAdvice materials
     * 
     * Materials are sourced from JobAdviceRoom -> rentalProduct -> rentalDetails
     * 
     * @param \App\Models\JobAssignSchedule $jobAssignSchedule
     * @param array $specificRoomIds Optional array of JobAdviceRoom IDs to filter which rooms to process
     * @return void
     */
    protected function autoCreateMaterialIssue($jobAssignSchedule, $specificRoomIds = [])
    {
        $jobSchedule = $jobAssignSchedule->jobSchedule;
        $jobAdvice = $jobSchedule->jobAdvice;
        
        // MOM: Check if material issue already exists for this assignment (idempotency)
        // If it is still editable, continue below and fill any missing rental components.
        $existingLinkedMaterialIssue = null;
        $existingLink = \App\Models\JobAssignMaterialIssue::where('job_assign_schedule_id', $jobAssignSchedule->id)->first();
        if ($existingLink) {
            $existingLinkedMaterialIssue = $existingLink->materialIssue;
            if (!$existingLinkedMaterialIssue || !in_array($existingLinkedMaterialIssue->status, ['pending', 'draft', 'approved'], true)) {
                return;
            }
        }

        if (!$jobAdvice) {
            \Log::warning('Cannot create material issue: JobAdvice not found', [
                'job_schedule_id' => $jobSchedule->id
            ]);
            return;
        }

        // MOM: Determine filtering criteria based on job type mapping
        // Rule Skip: Remove (RV/RF) jobs do NOT need any materials
        // 1. All Materials: IF (install_free), EXTRA, COMPLAIN (NR), TRIAL, CHANGE
        // 2. Units Only: IR (install)
        // 3. Non-Units Only: CSR (service / maintenance)
        $jobType = strtolower($jobSchedule->type ?? '');

        if (in_array($jobType, ['remove', 'remove_free', 'removal'])) {
            return;
        }
        
        // FIX: 'change' should generate ALL materials (Unit + Aroma + Cleaner etc), NOT just unit
        // So we remove 'change' from isUnitOnly and ensure it triggers isAllMaterials
        $isUnitOnly = in_array($jobType, ['install']); // Removed 'change'
        $isNonUnitOnly = (in_array($jobType, ['service', 'service_first', 'service_routine', 'maintenance']) || stripos($jobType, 'service') !== false);
        $isAllMaterials = in_array($jobType, ['install_free', 'extra', 'complain', 'trial', 'change']); // Added 'change'
        
        // Default to ALL if not explicitly matched
        if (!$isUnitOnly && !$isNonUnitOnly && !$isAllMaterials) {
            $isAllMaterials = true; 
        }

        // MOM: Get specific JobScheduleRooms for this schedule (instead of all JobAdvice rooms)
        $jobScheduleRooms = $jobSchedule->jobScheduleRooms;

        // FILTER: If specific room IDs provided, filter to only those JobScheduleRoom IDs (or linked JobAdviceRoom IDs)
        // FILTER: If specific room IDs provided, filter to only those JobScheduleRoom IDs (or linked JobAdviceRoom IDs)
        // MOM FIX: Also handle "job_{id}" which means "Include All Rooms for this Job" (Legacy/Job Level Selection)
        if (!empty($specificRoomIds)) {
            $includeAllForThisJob = false;
            foreach ($specificRoomIds as $sId) {
                if ($sId === 'job_' . $jobSchedule->id) {
                    $includeAllForThisJob = true;
                    break;
                }
            }

            if (!$includeAllForThisJob) {
                $jobScheduleRooms = $jobScheduleRooms->filter(function($jobScheduleRoom) use ($specificRoomIds) {
                    // 1. Direct match with JobScheduleRoom ID
                    if (in_array($jobScheduleRoom->id, $specificRoomIds)) {
                        return true;
                    }
    
                    // 2. Legacy/Fallback: Check linked JobAdviceRooms (in case Advice Room IDs were passed)
                    $linkedJobAdviceRooms = $jobScheduleRoom->jobAdviceRooms;
                    
                    // Fallback to single relationship if no pivot entries
                    if ($linkedJobAdviceRooms->isEmpty() && $jobScheduleRoom->jobAdviceRoom) {
                        $linkedJobAdviceRooms = collect([$jobScheduleRoom->jobAdviceRoom]);
                    }
                    
                    foreach ($linkedJobAdviceRooms as $jar) {
                        if (in_array($jar->id, $specificRoomIds)) {
                            return true;
                        }
                    }
                    return false;
                });
            } else {
            }
        }

        // MOM FIX: Handle Legacy Jobs (No JobScheduleRooms)
        // If jobScheduleRooms is empty, but we have JobAdvice, we should try to process based on JobAdviceRooms directly?
        // Or create a Virtual Context?
        // Current architecture relies on JobScheduleRoom for mapping.
        // If JobScheduleRoom is missing, it means the migration was incomplete or data is old.
        // However, we can try to use JobAdviceRooms directly as a fallback source for materials.
        
        $useFallback = false;
        $fallbackRooms = collect([]);

        if ($jobScheduleRooms->isEmpty()) {
            
            if ($jobAdvice && $jobAdvice->rooms) {
                 // Check if we need to filter these too
                 if (!empty($specificRoomIds) && !$includeAllForThisJob) {
                     $fallbackRooms = $jobAdvice->rooms->filter(function($room) use ($specificRoomIds) {
                         return in_array($room->id, $specificRoomIds);
                     });
                 } else {
                     $fallbackRooms = $jobAdvice->rooms;
                 }
                 $useFallback = true;
            }
            
            if ($fallbackRooms->isEmpty()) {
                return;
            }
        }

        // Collect all materials first
        $allMaterials = [];
        
        // Define items to process: either JobScheduleRooms or Fallback Rooms
        $itemsToProcess = $useFallback ? $fallbackRooms : $jobScheduleRooms;

        foreach ($itemsToProcess as $item) {
            $linkedJobAdviceRooms = collect([]);
            
            if ($useFallback) {
                // $item is a JobAdviceRoom directly
                $linkedJobAdviceRooms = collect([$item]);
                $roomName = $item->room_name;
                $roomId = $item->id;
                $sourceType = 'JobAdviceRoom';
            } else {
                // $item is a JobScheduleRoom
                $jobScheduleRoom = $item;
                $roomName = $jobScheduleRoom->room_name;
                $roomId = $jobScheduleRoom->id;
                $sourceType = 'JobScheduleRoom';

                // FIX: Get ALL linked JobAdviceRooms (one per rental unit in the room)
                // Use the hasManyThrough relationship (jobAdviceRooms) defined in JobScheduleRoom model
                $linkedJobAdviceRooms = $jobScheduleRoom->jobAdviceRooms;
                
                // Fallback: Use the single relationship if no pivot entries found (legacy support)
                if ($linkedJobAdviceRooms->isEmpty() && $jobScheduleRoom->jobAdviceRoom) {
                    $linkedJobAdviceRooms = collect([$jobScheduleRoom->jobAdviceRoom]);
                }
            }

            foreach ($linkedJobAdviceRooms as $jobAdviceRoom) {
                if (!$jobAdviceRoom) continue;

            // Get rental product (MasterRental) from JobAdviceRoom
            $rentalProduct = $jobAdviceRoom->rentalProduct;
            
            if (!$rentalProduct) {
                continue;
            }

            // Load rentalDetails (materials/products). Material Issue must mirror
            // these rows exactly; do not append components from quotation/BOM.
            $rentalProduct->load([
                'rentalDetails.productCategory',
                'rentalDetails.productType',
                'rentalDetails.masterProduct.productCategory',
                'rentalDetails.masterProduct.productType',
            ]);
            
            // Prepare for aroma substitution
            $jobAdviceRoom->load('contractRoom.contract.quotation.quotationRooms.aromaProduct', 'quotationRoom.aromaProduct');
            
            // Try to get aroma from ContractRoom first (via original QuotationRoom), fallback to QuotationRoom (direct aromaProduct)
            $aromaProduct = null;
            $sourceType = null;
            
            if ($jobAdviceRoom->contractRoom) {
                // For contract rooms, we need to find the aroma from the original quotation room
                $contract = $jobAdviceRoom->contractRoom->contract;
                if ($contract && $contract->quotation) {
                    $matchingQuotationRoom = $contract->quotation->quotationRooms
                        ->where('room_id', $jobAdviceRoom->contractRoom->room_id)
                        ->first();
                    
                    if ($matchingQuotationRoom && $matchingQuotationRoom->aromaProduct) {
                        $aromaProduct = $matchingQuotationRoom->aromaProduct;
                        $sourceType = 'ContractRoom->QuotationRoom->AromaProduct';
                    }
                }
            } elseif ($jobAdviceRoom->quotationRoom && $jobAdviceRoom->quotationRoom->aromaProduct) {
                $aromaProduct = $jobAdviceRoom->quotationRoom->aromaProduct;
                $sourceType = 'QuotationRoom->AromaProduct';
            }

            $hasSubstitutedAroma = false;

            if ($rentalProduct->rentalDetails && $rentalProduct->rentalDetails->isNotEmpty()) {
                foreach ($rentalProduct->rentalDetails as $detail) {
                    $product = $detail->masterProduct;
                    $isAromaType = $this->isAromaRentalDetail($detail, $product);

                    if (!$product && $isAromaType && $aromaProduct) {
                        $product = $aromaProduct;
                        $hasSubstitutedAroma = true;
                    }

                    if ($product) {
                        
                        // MOM: Unit marker comes from product_categories.is_unit.
                        $isUnitProduct = $this->isUnitProductByCategory($product);
                        
                        if ($isUnitOnly && !$isUnitProduct) {
                            continue;
                        }
                        
                        if ($isNonUnitOnly && $isUnitProduct) {
                            continue;
                        }

                        // Substitution Logic
                        if ($isAromaType && $aromaProduct) {
                             $product = $aromaProduct; // Swap to quotation aroma
                             $hasSubstitutedAroma = true;
                        }

                        // MOM: Default to 100ml (Packaging Size ID: 2) for Aroma/Refill products if available
                        if ($isAromaType && $product->packaging_size_id != 2) {
                            $hundredMlProduct = \App\Models\MasterProduct::where('variant_name', $product->variant_name)
                                ->where('brand_line', $product->brand_line)
                                ->where('packaging_size_id', 2)
                                ->where('is_active', true)
                                ->first();
                            
                            if ($hundredMlProduct) {
                                $product = $hundredMlProduct;
                            }
                        }

                        // Build notes in format expected by view: "Room: X, Rental: Y, ComponentID: Z"
                        $rentalName = $rentalProduct->rental_name ?? '-';
                        // Keep original component ID if not substituted, or use a marker if substituted? 
                        // Actually, if we substituted, maybe we should indicate it in notes?
                        // But sticking to convention:
                        $componentId = $detail->id ?? 0;
                        $notesText = "Room: {$jobAdviceRoom->room_name}, Rental: {$rentalName}, RentalDetailID: {$componentId}";
                        
                        if ($hasSubstitutedAroma && $aromaProduct && $product->id === $aromaProduct->id) {
                            $originalProductName = $detail->masterProduct->name ?? 'rental detail aroma slot';
                            $notesText .= " (Substituted from {$originalProductName})";
                        }
                        
                        // Qty Issue = Rental Detail Quantity (how many pieces to issue)
                        // BOM Qty = Product BOM Qty (multiplier from master product)
                        // Display "Qty BOM" = Qty Issue × BOM Qty
                        $qtyIssue = $detail->quantity ?? 1;  // From rental_details.quantity (e.g., 200)
                        $bomQty = $product->bom_qty ?? ($product->bom_quantity ?? 1);  // From substituted product if applicable
                        
                        $allMaterials[] = [
                            'product' => $product,
                            'quantity' => $qtyIssue,  // Rental detail quantity (200)
                            'room_name' => $jobAdviceRoom->room_name,
                            'notes' => $notesText,
                            'component_id' => $componentId,
                            'bom_quantity' => $bomQty
                        ];
                    }
                }
            }
            
            
            // Do not append quotation aroma/refill if there is no matching rental detail slot.
            // Install Free must follow the selected Master Rental Details exactly.
            } // END FIX Loop linkedJobAdviceRooms
        }

        if (empty($allMaterials)) {
            return;
        }

        // Get warehouse based on building location (city/province → branch → warehouse)
        $warehouse = null;
        $building = $jobSchedule->building;
        $candidateBranchIds = collect();
        
        if ($building) {
            
            // Find branch that matches building's city (priority) or province (fallback)
            $branch = null;

            if (\Schema::hasColumn('buildings', 'branch_id') && !empty($building->branch_id)) {
                $candidateBranchIds->push((int) $building->branch_id);

                $branch = \App\Models\Branch::where('id', $building->branch_id)
                    ->where('is_active', true)
                    ->first();
            }
            
            if (!$branch && $building->city_id) {
                $branch = \App\Models\Branch::where('city_id', $building->city_id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    $candidateBranchIds->push((int) $branch->id);
                }
            }
            
            $buildingProvinceId = $building->province_id ?: ($building->city?->province_id ?? null);

            if (!$branch && $buildingProvinceId) {
                $branch = \App\Models\Branch::where('province_id', $buildingProvinceId)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    $candidateBranchIds->push((int) $branch->id);
                }
            }
            
            if ($branch) {
                $warehouse = \App\Models\Warehouse::where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($warehouse) {
                }
            } else {
                \Log::warning('No branch found for building location, trying team branch', [
                    'city_id' => $building->city_id,
                    'province_id' => $buildingProvinceId,
                    'building_province_id' => $building->province_id,
                    'city_province_id' => $building->city?->province_id,
                ]);
            }
        }
        
        // PRIORITY FALLBACK: Try Team branch if building branch failed
        if (!$warehouse && $jobAssignSchedule->team && $jobAssignSchedule->team->branch_office) {
            $teamBranchId = $jobAssignSchedule->team->branch_office;
            $warehouse = \App\Models\Warehouse::where('branch_id', $teamBranchId)
                ->where('is_active', true)
                ->first();
            
            if ($warehouse) {
                $candidateBranchIds->push((int) $teamBranchId);
            }
        }

        $jobSchedule->loadMissing('jobAdvice.contract.quotation', 'jobAdvice.quotation');
        foreach ([
            $jobSchedule->jobAdvice?->contract?->quotation?->branch_id,
            $jobSchedule->jobAdvice?->quotation?->branch_id,
            Auth::user()?->branch_id,
        ] as $branchId) {
            if ($branchId) {
                $candidateBranchIds->push((int) $branchId);
            }
        }

        $candidateBranchIds = $candidateBranchIds->filter()->unique()->values();

        if (!$warehouse && $candidateBranchIds->isNotEmpty()) {
            $warehouse = \App\Models\Warehouse::whereIn('branch_id', $candidateBranchIds)
                ->where('is_active', true)
                ->orderByRaw('FIELD(branch_id, ' . $candidateBranchIds->implode(',') . ')')
                ->orderBy('id')
                ->first();
        }
        
        if (!$warehouse) {
            \Log::error('No branch warehouse available for MaterialIssue creation; refusing first-active warehouse fallback to prevent cross-branch stock/SN mix-up.', [
                'job_schedule_id' => $jobSchedule->id,
                'job_number' => $jobSchedule->job_number,
                'building_id' => $building?->id,
                'building_branch_id' => \Schema::hasColumn('buildings', 'branch_id') ? ($building->branch_id ?? null) : null,
                'building_city_id' => $building->city_id ?? null,
                'building_province_id' => $building->province_id ?? null,
                'team_id' => $jobAssignSchedule->team?->id,
                'team_branch_id' => $jobAssignSchedule->team?->branch_office,
                'candidate_branch_ids' => $candidateBranchIds->all(),
            ]);
            return;
        }

        // MOM CONSOLIDATION: Check if ANY assignment for this SAME JOB NUMBER already has a MaterialIssue
        // If yes, we reuse that MaterialIssue header instead of creating a new one (JKT-MI/...)
        $existingMaterialIssue = null;
        if ($jobSchedule->job_number) {
            $existingMaterialIssue = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule.jobSchedule', function($q) use ($jobSchedule) {
                $q->where('job_number', $jobSchedule->job_number);
            })
            ->whereIn('status', ['pending', 'draft', 'approved']) // Only reuse if not yet 'issued'
            ->where('warehouse_id', $warehouse->id)
            ->first();
        }

        $actorId = Auth::id()
            ?: $jobAssignSchedule->updated_by
            ?: $jobAssignSchedule->created_by
            ?: $jobSchedule->updated_by
            ?: $jobSchedule->created_by
            ?: $jobAssignSchedule->assigned_by
            ?: User::query()->where('is_active', true)->orderBy('id')->value('id')
            ?: User::query()->orderBy('id')->value('id')
            ?: 1;

        if ($existingLinkedMaterialIssue) {
            $materialIssue = $existingLinkedMaterialIssue;
            $issueNumber = $materialIssue->issue_number;
        } elseif ($existingMaterialIssue) {
            $materialIssue = $existingMaterialIssue;
            $issueNumber = $materialIssue->issue_number;
        } else {
            // Create MaterialIssue header (parent record)
            // Use DocumentNumberService to generate number with branch code (e.g., JKT-MI/25-12/0001)
            $documentNumberService = new \App\Services\DocumentNumberService();
            $issueNumber = $documentNumberService->generate(
                'material_issue',
                null, // Let service find branch
                $building ? $building->id : null, 
                null, // Contract
                null, // Quotation
                null, // Survey
                $warehouse ? $warehouse->id : null // Warehouse context
            );
            
            // Note: MaterialIssue is old structure, might not have job_assign_schedule_id
            // We'll create it without linking, items will link via material_issue_id
            $materialIssue = \App\Models\MaterialIssue::create([
                'issue_number' => $issueNumber,
                'warehouse_id' => $warehouse->id, // Dynamic warehouse based on building branch
                'team_id' => $jobAssignSchedule->team_id,
                'issued_by' => $actorId,
                'issue_date' => $jobAssignSchedule->assigned_date ?? now(),
                'status' => 'pending',
                'notes' => "Auto-created from Job Schedule {$jobSchedule->job_number}",
                'created_by' => $actorId
            ]);
        }

        // MOM Idempotency: Use firstOrCreate to prevent duplicate links
        \App\Models\JobAssignMaterialIssue::firstOrCreate(
            [
                'job_assign_schedule_id' => $jobAssignSchedule->id,
                'material_issue_id' => $materialIssue->id,
            ],
            [
                'created_by' => $actorId
            ]
        );

        // Create MaterialIssueItems linked to MaterialIssue
        $itemsCreated = 0;
        foreach ($allMaterials as $material) {
            try {
                // MOM Idempotency: keep separate rental components even when they use the same product in the same room.
                // Example: Rental-1 and Rental-3 can both resolve to the same aroma/refill product.
                $existsInMI = \App\Models\MaterialIssueItem::where('material_issue_id', $materialIssue->id)
                    ->where('job_assign_schedule_id', $jobAssignSchedule->id)
                    ->where('product_id', $material['product']->id)
                    ->where('room_name', $material['room_name'])
                    ->where('notes', $material['notes'])
                    ->exists();

                if (!$existsInMI) {
                    \App\Models\MaterialIssueItem::create([
                        'material_issue_id' => $materialIssue->id,
                        'job_assign_schedule_id' => $jobAssignSchedule->id,
                        'product_id' => $material['product']->id,
                        'quantity' => $material['quantity'],
                        'unit_price' => 0,
                        'total_price' => 0,
                        'room_name' => $material['room_name'],
                        'notes' => $material['notes'], // Format: "Room: X, Rental: Y, ComponentID: Z"
                        'bom_quantity' => $material['bom_quantity'] ?? 1,
                        'is_copied' => false,
                        'created_by' => $actorId
                    ]);
                    $itemsCreated++;
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create MaterialIssueItem', [
                    'material_issue_id' => $materialIssue->id,
                    'product_id' => $material['product']->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Detect whether a rental detail is an aroma/refill slot.
     * This intentionally checks the rental detail metadata first, because the
     * material structure must come from Master Rental Details, not quotation.
     */
    protected function isAromaRentalDetail($detail, $product = null): bool
    {
        $haystack = strtolower(implode(' ', array_filter([
            $detail->productCategory->name ?? null,
            $detail->productType->name ?? null,
            $product->productCategory->name ?? null,
            $product->productType->name ?? null,
            $product->name ?? null,
        ])));

        foreach (['aroma', 'refill', 'variant', 'scent', 'liquid', 'fragrance', 'oil'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Product unit/non-unit classification must follow product_categories.is_unit.
     * ProductType is only a legacy fallback when the product has no category.
     */
    protected function isUnitProductByCategory($product): bool
    {
        if (!$product) {
            return false;
        }

        if ($product->productCategory && $product->productCategory->is_unit !== null) {
            return (bool) $product->productCategory->is_unit;
        }

        if ($product->product_category_id) {
            $isUnit = \DB::table('product_categories')
                ->where('id', $product->product_category_id)
                ->value('is_unit');

            if ($isUnit !== null) {
                return (bool) $isUnit;
            }
        }

        return $product->productType
            ? (bool) $product->productType->is_unit
            : false;
    }

    /**
     * Create a single Material Issue Item (DEPRECATED - use autoCreateMaterialIssue instead)
     * 
     * @param \App\Models\JobAssignSchedule $jobAssignSchedule
     * @param \App\Models\MasterProduct $product
     * @param int $quantity
     * @param string|null $roomName
     * @return void
     */
    protected function createMaterialIssueItem($jobAssignSchedule, $product, $quantity, $roomName = null)
    {
        \App\Models\MaterialIssueItem::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => 0, // Material issue doesn't track pricing
            'total_price' => 0,
            'room_name' => $roomName,
            'notes' => 'Auto-created from Job Advice materials',
            'is_copied' => false,
            'created_by' => Auth::id()
        ]);
    }


    /**
     * Return units to warehouse for Remove Jobs
     *
     * @param JobSchedule $jobSchedule
     * @return void
     */
    private function returnUnitsToWarehouse(JobSchedule $jobSchedule)
    {
        try {
            \Log::info("🔄 Processing Return Units to Warehouse for Job {$jobSchedule->job_number} (Type: {$jobSchedule->type})");

            $jobAdvice = $jobSchedule->jobAdvice;
            if (!$jobAdvice) {
                \Log::warning("⚠️ No Job Advice found for Job {$jobSchedule->job_number}. Cannot return units.");
                return;
            }

            // Determine Warehouse (from Building -> Branch default or first active)
            $building = $jobSchedule->building;
            $warehouse = null;
            if ($building && $building->branch_id) {
                $warehouse = Warehouse::where('branch_id', $building->branch_id)->where('is_active', true)->first();
            }
            if (!$warehouse) {
                $warehouse = Warehouse::where('is_active', true)->first();
            }

            if (!$warehouse) {
                \Log::error("❌ No active warehouse found to return units to.");
                return;
            }

            $rooms = $jobAdvice->rooms;
            $totalReturned = 0;
            $customerId = $jobAdvice->customer_id ?? $jobAdvice->quotation?->customer_id ?? $jobAdvice->contract?->customer_id;

            if (!$customerId && $building) {
                 if ($building->customer_id) {
                     $customerId = $building->customer_id;
                 }
            }

            if (!$customerId) {
                \Log::warning("⚠️ Could not determine Customer ID for Unit Return.");
                return;
            }

            foreach ($rooms as $jaRoom) {
                // Determine target Room ID (Master Room)
                $masterRoomId = $jaRoom->contractRoom?->room_id ?? $jaRoom->quotationRoom?->room_id;
                
                if (!$masterRoomId) {
                    \Log::warning("⚠️ JA Room {$jaRoom->id} has no linked Room ID. Skipping.");
                    continue;
                }

                // Check for specific units (UnitOnWall) installed in this room for this customer
                $unitsOnWall = UnitOnWall::where('customer_id', $customerId)
                    ->where('room_id', $masterRoomId)
                    ->where('status', 'active')
                    ->get();

                foreach ($unitsOnWall as $unit) {
                    // 1. Update Warehouse Product Stock
                    $warehouseProduct = WarehouseProduct::firstOrCreate(
                        ['warehouse_id' => $warehouse->id, 'master_product_id' => $unit->product_id],
                        ['quantity' => 0, 'created_by' => auth()->id()]
                    );
                    
                    $warehouseProduct->increment('quantity', 1);

                    // 2. Create Inventory Movement
                    InventoryMovement::create([
                        'movement_no' => 'MV-' . now()->format('ymdHis') . '-' . mt_rand(1000, 9999),
                        'movement_type' => 'return',
                        'warehouse_id' => $warehouse->id,
                        'master_product_id' => $unit->product_id,
                        'quantity' => 1,
                        'unit_price' => 0,
                        'total_value' => 0,
                        'movement_date' => now(),
                        'reference_no' => $jobSchedule->job_number,
                        'reference_type' => 'job_schedule',
                        'notes' => "Returned from Customer: {$unit->customer_name}, Room: {$unit->room_name} (Serial: {$unit->serial_number}). Ex-installed.",
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]);

                    // 3. Update UnitOnWall Status
                    $unit->update([
                        'status' => 'removed',
                        'notes' => ($unit->notes ? $unit->notes . "\n" : "") . "Removed via Job {$jobSchedule->job_number} returned to {$warehouse->name} on " . now()->format('Y-m-d'),
                        'updated_by' => auth()->id()
                    ]);

                    // 4. Create History
                    UnitOnWallHistory::create([
                        'unit_on_wall_id' => $unit->id,
                        'action' => 'remove',
                        'action_date' => now(),
                        'job_schedule_id' => $jobSchedule->id,
                        'technician_id' => $jobSchedule->assigned_technician_id,
                        'notes' => "Unit removed and returned to warehouse {$warehouse->name}. Stock incremented.",
                        'created_by' => auth()->id()
                    ]);

                    $totalReturned++;
                }
            }

            \Log::info("✅ Successfully returned {$totalReturned} units to warehouse {$warehouse->name} for Job {$jobSchedule->job_number}");

        } catch (\Exception $e) {
            \Log::error("❌ Failed to return units to warehouse: " . $e->getMessage());
        }
    }

    /**
     * Check assignment status for multiple jobs (Bulk Action)
     * Used for Assign Team modal to show room details and existing assignments
     */
    public function checkBulkAssignments(Request $request)
    {
        try {
            $jobIds = $request->input('job_ids', []);
            $strictSelection = $request->boolean('strict_selection', false);
            $expandGroupedRows = $request->boolean('expand_grouped_rows', false);
            $selectedRoomIds = collect($request->input('selected_room_ids', []))
                ->filter(fn ($roomId) => is_numeric($roomId))
                ->map(fn ($roomId) => (int) $roomId)
                ->filter(fn ($roomId) => $roomId > 0)
                ->unique()
                ->values();
            
            if (empty($jobIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No jobs selected'
                ], 422);
            }
            
            // Get selected jobs
            $selectedJobs = JobSchedule::whereIn('id', $jobIds)->get();
            
            $allRooms = [];
            $processedJobIds = [];
            $canAssignCount = 0;
            $alreadyAssignedCount = 0;

            if ($strictSelection && $selectedRoomIds->isNotEmpty()) {
                $selectedRooms = \App\Models\JobScheduleRoom::with([
                        'jobSchedule.jobAssignSchedules.team',
                        'jobSchedule.jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
                        'roomAssignment.team',
                    ])
                    ->whereIn('id', $selectedRoomIds->all())
                    ->get();

                foreach ($selectedRooms as $room) {
                    $job = $room->jobSchedule;
                    if (!$job) {
                        continue;
                    }

                    $activeAssignment = \App\Models\JobAssignSchedule::where('job_schedule_id', $job->id)
                        ->where('status', '!=', 'cancelled')
                        ->with('team')
                        ->orderBy('id', 'desc')
                        ->first();

                    $roomAssignment = \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $job->id)
                        ->where('job_schedule_room_id', $room->id)
                        ->where('status', '!=', 'cancelled')
                        ->whereNull('deleted_at')
                        ->with('team')
                        ->latest()
                        ->first();

                    $thisRoomHasMaterial = false;
                    foreach ($job->jobAssignSchedules as $jas) {
                        foreach ($jas->jobAssignMaterialIssues as $jami) {
                            if ($jami->materialIssue && $jami->materialIssue->items->where('room_name', $room->room_name)->count() > 0) {
                                $thisRoomHasMaterial = true;
                                break 2;
                            }
                        }
                    }

                    $currentStatus = $job->status;
                    if (in_array($currentStatus, ['assign_material', 'barang_dipersiapkan', 'barang_siap_diambil', 'barang_diambil', 'material_issue'], true) && !$thisRoomHasMaterial) {
                        $currentStatus = 'scheduled';
                    }

                    $isAssigned = (bool) ($roomAssignment?->team || $activeAssignment?->team);
                    $allRooms[] = [
                        'id' => $room->id,
                        'job_id' => $job->id,
                        'room_name' => $room->room_name,
                        'job_number' => $job->job_number ?? 'No Job No',
                        'status' => $isAssigned ? (($job->material_checked) ? 'already_assigned' : 'can_reassign') : 'will_assign',
                        'team_id' => $roomAssignment?->team_id ?? ($activeAssignment?->team_id ?? null),
                        'team_name' => $roomAssignment?->team?->team_name ?? $activeAssignment?->team?->team_name ?? '-',
                        'job_status' => $currentStatus,
                        'material_checked' => (bool) $job->material_checked,
                        'display_text' => $room->room_name,
                    ];
                }

                return response()->json([
                    'status' => 'success',
                    'data' => $allRooms,
                ]);
            }
            
            foreach ($selectedJobs as $selectedJob) {
                
                if ($strictSelection) {
                    // Material Assign stays selection-based, but Job View rows can visually
                    // represent multiple sibling schedules. Expand only to the exact visual
                    // grouping key (JA + building + type + period), not every service in the JA.
                    $strictQuery = JobSchedule::with([
                            'jobAssignSchedules.team',
                            'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
                            'jobScheduleRooms',
                        ])
                        ->whereNull('deleted_at');

                    if ($expandGroupedRows && $selectedJob->job_advice_id) {
                        $strictQuery
                            ->where('job_advice_id', $selectedJob->job_advice_id)
                            ->where('building_id', $selectedJob->building_id)
                            ->where('type', $selectedJob->type)
                            ->where(function ($query) use ($selectedJob) {
                                if ($selectedJob->period === null) {
                                    $query->whereNull('period');
                                } else {
                                    $query->where('period', $selectedJob->period);
                                }
                            });
                    } else {
                        $strictQuery->where('id', $selectedJob->id);
                    }

                    $siblings = $strictQuery->get();
                } else {
                    // Find all siblings to ensure we get the whole group for team assignment flows.
                    $query = JobSchedule::with(['jobAssignSchedules.team', 'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items', 'jobScheduleRooms'])
                         ->whereNull('deleted_at');

                    // Try Primary Scope: Job Number
                    if ($selectedJob->job_number) {
                        $query->where('job_number', $selectedJob->job_number);
                    } else {
                         $query->where('job_advice_id', $selectedJob->job_advice_id)
                               ->where('building_id', $selectedJob->building_id)
                               ->where('type', $selectedJob->type);
                    }
                    
                    $siblings = $query->get();
                    
                    // FALLBACK: expand visual siblings for assignment flows, but not material assign.
                    if ($selectedJob->job_advice_id) {
                         $queryFallback = JobSchedule::with(['jobAssignSchedules.team', 'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items', 'jobScheduleRooms'])
                            ->where('job_advice_id', $selectedJob->job_advice_id)
                            ->where('type', $selectedJob->type)
                            ->whereNull('deleted_at');
                            
                         if ($selectedJob->building_id) {
                             $queryFallback->where('building_id', $selectedJob->building_id);
                         }

                         $fallbackSiblings = $queryFallback->get();
                            
                         if ($fallbackSiblings->count() > $siblings->count()) {
                             $siblings = $fallbackSiblings;
                         }
                    }
                }
                
                $hasAnyRoomAssignment = \App\Models\JobScheduleRoomAssignment::whereIn('job_schedule_id', $siblings->pluck('id'))
                    ->where('status', '!=', 'cancelled')
                    ->whereNull('deleted_at')
                    ->exists();

                foreach ($siblings as $job) {
                    if (in_array($job->id, $processedJobIds)) {
                        continue;
                    }
                    $processedJobIds[] = $job->id;
                    
                    // Determine Job-Level Active Team (Primary)
                    // IMPORTANT: Direct database query to get latest data after unassign
                    // This ensures we get fresh data, not cached relationship data
                    $activeAssignment = \App\Models\JobAssignSchedule::where('job_schedule_id', $job->id)
                        ->where('status', '!=', 'cancelled')
                        ->with('team')
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    $primaryTeam = null;
                    if ($activeAssignment && $activeAssignment->team) {
                        $primaryTeam = $activeAssignment->team->team_name;
                    }

                    // 1. Check for valid JobScheduleRooms (New Structure)
                    if ($job->jobScheduleRooms && $job->jobScheduleRooms->isNotEmpty()) {
                        foreach ($job->jobScheduleRooms as $room) {
                            // Check for specific room assignment
                            $roomAssignment = \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $job->id)
                                ->where('job_schedule_room_id', $room->id)
                                ->where('status', '!=', 'cancelled') // MOM FIX: Ignore cancelled assignments
                                ->whereNull('deleted_at')
                                ->with('team')
                                ->latest()
                                ->first();
                                
                            $isAssigned = false;
                            $teamName = '-';
                            
                            if ($roomAssignment && $roomAssignment->team) {
                                $isAssigned = true;
                                $teamName = $roomAssignment->team->team_name;
                            } elseif (!$hasAnyRoomAssignment && $primaryTeam) {
                                // Inherit job assignment ONLY if job doesn't use granular room assignments
                                $isAssigned = true;
                                $teamName = $primaryTeam;
                            }
                            
                            // MOM Fix: Granular Status v2 for Popup
                            $currentStatus = $job->status;
                            if (in_array($currentStatus, ['assign_material', 'barang_dipersiapkan', 'barang_siap_diambil', 'barang_diambil', 'material_issue'])) {
                                // 1. Check if THIS specific room has material
                                $thisRoomHasMaterial = false;
                                foreach ($job->jobAssignSchedules as $jas) {
                                    foreach ($jas->jobAssignMaterialIssues as $jami) {
                                        if ($jami->materialIssue && $jami->materialIssue->items->where('room_name', $room->room_name)->count() > 0) {
                                            $thisRoomHasMaterial = true;
                                            break 2;
                                        }
                                    }
                                }

                                if (!$thisRoomHasMaterial) {
                                    // 2. Check if ANY room in ANY sibling job has material
                                    $anyRoomHasMaterialInGroup = false;
                                    foreach ($siblings as $anySiblingJob) {
                                        foreach ($anySiblingJob->jobScheduleRooms as $siblingRoom) {
                                            foreach ($anySiblingJob->jobAssignSchedules as $jas) {
                                                foreach ($jas->jobAssignMaterialIssues as $jami) {
                                                    if ($jami->materialIssue && $jami->materialIssue->items->where('room_name', $siblingRoom->room_name)->count() > 0) {
                                                        $anyRoomHasMaterialInGroup = true;
                                                        break 4;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if ($anyRoomHasMaterialInGroup) {
                                        $currentStatus = 'scheduled';
                                    }
                                }
                            }

                        // Determine status: can we reassign?
                        // MOM: allow reassign if has team but material not yet checked by tech
                        $status = 'will_assign';
                        if ($isAssigned) {
                            $status = ($job->material_checked) ? 'already_assigned' : 'can_reassign';
                        }

                        $allRooms[] = [
                            'id' => $room->id,
                            'job_id' => $job->id,
                            'room_name' => $room->room_name,
                            'job_number' => $job->job_number ?? 'No Job No',
                            'status' => $status,
                            'team_id' => $roomAssignment?->team_id ?? ($activeAssignment?->team_id ?? null),
                            'team_name' => $teamName,
                            'job_status' => $currentStatus, // Use dynamic granular status
                            'material_checked' => (bool)$job->material_checked,
                            'display_text' => $room->room_name . ($isAssigned ? " (assigned to {$teamName})" : "")
                        ];
                    }
                } else {
                    // 2. Legacy/Fragmented Structure: Job itself represents the room
                    $isAssigned = !empty($primaryTeam);
                    $teamName = $primaryTeam ?? '-';
                    
                    $status = 'will_assign';
                    if ($isAssigned) {
                        $status = ($job->material_checked) ? 'already_assigned' : 'can_reassign';
                    }
                    
                    $allRooms[] = [
                        'id' => 'job_' . $job->id, // Special flag
                        'job_id' => $job->id,
                        'room_name' => $job->room_name ?? 'Main Job Room',
                        'job_number' => $job->job_number ?? 'No Job No',
                        'status' => $status,
                        'team_id' => $activeAssignment?->team_id ?? null,
                        'team_name' => $teamName,
                        'job_status' => $job->status, // Added for frontend filtering
                        'material_checked' => (bool)$job->material_checked,
                        'display_text' => ($job->room_name ?? 'Main Job Room') . ($isAssigned ? " (assigned to {$teamName})" : "")
                    ];
                }
            }
        }
            
            return response()->json([
                'status' => 'success',
                'data' => $allRooms
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error checking bulk assignments: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // BA FILES (Berita Acara) Methods
    // ========================================

    /**
     * Upload BA file for a job schedule room
     */
    public function uploadBaFile(Request $request, JobSchedule $jobSchedule)
    {
        $request->validate([
            'room_id' => 'required|exists:master_rooms,id',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $roomId = $request->room_id;
            
            // Get room name
            $room = \App\Models\MasterRoom::find($roomId);
            $roomName = $room ? $room->room_name : 'Unknown Room';

            // Create upload directory if not exists
            $uploadDir = 'uploads/ba-files/' . $jobSchedule->id;
            $publicPath = public_path($uploadDir);
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getClientMimeType();
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $filePath = $uploadDir . '/' . $fileName;
            
            // Move file
            $file->move($publicPath, $fileName);

            // Create BA file record
            $baFile = \App\Models\JobScheduleBaFile::create([
                'job_schedule_id' => $jobSchedule->id,
                'room_id' => $roomId,
                'room_name' => $roomName,
                'file_name' => $originalName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'file_type' => 'report',
                'verification_status' => 'pending',
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'BA File berhasil diupload.',
                'data' => $baFile->load('uploader')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error uploading BA file: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupload file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get BA files for a job schedule
     */
    public function getBaFiles(JobSchedule $jobSchedule)
    {
        $baFiles = $jobSchedule->baFiles()
            ->with(['uploader', 'verifier', 'room'])
            ->orderBy('room_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $baFiles
        ]);
    }

    /**
     * Update BA file checkboxes (Needed for Invoice, Approved)
     * Point 10: Auto-save checkboxes
     */
    public function updateBaFileCheckbox(Request $request, $baFileId)
    {
        try {
            $baFile = \App\Models\JobScheduleBaFile::findOrFail($baFileId);
            
            $user = auth()->user();

            // Permission Check
            $canEdit = $user->hasPermission('operational.job-schedules.update') || $user->hasPermission('operational.job-schedules.edit') || $user->hasPermission('operational.job-schedules.update.view');
            $canApprove = $user->hasPermission('operational.job-schedules.approve-ba') || $user->hasPermission('operational.job-schedules.approve-ba.view') || $user->hasPermission('operational.job-schedules.approve');

            // 1. If changing 'needed_for_invoice', require Edit
            if ($request->has('needed_for_invoice') && !$canEdit) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Maaf, Anda tidak memiliki akses Edit Job Schedules.'
                ], 403);
            }

            // 2. If changing 'is_approved' (via checkbox?), require Approve
            if ($request->has('is_approved') && !$canApprove) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Maaf, Anda tidak memiliki akses Approve BA Files.'
                ], 403);
            }

            if ($request->has('needed_for_invoice')) {
                $baFile->needed_for_invoice = (bool)$request->needed_for_invoice;
            }

            if ($request->has('is_approved')) {
                $baFile->is_approved = (bool)$request->is_approved;
            }

            $baFile->updated_by = Auth::id();
            $baFile->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Data BA berhasil diperbarui.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data BA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview BA file
     */
    public function previewBaFile($baFileId)
    {
        $baFile = \App\Models\JobScheduleBaFile::findOrFail($baFileId);
        $filePath = public_path($baFile->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->file($filePath);
    }

    /**
     * Approve BA file
     */
    public function approveBaFile(Request $request, $baFileId)
    {
        $user = auth()->user();
        if (!$user->hasPermission('operational.job-schedules.approve-ba') && 
            !$user->hasPermission('operational.job-schedules.approve-ba.view') && 
            !$user->hasPermission('operational.job-schedules.approve')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Missing permission to approve BA files.'
            ], 403);
        }

        $baFile = \App\Models\JobScheduleBaFile::findOrFail($baFileId);

        if ($baFile->verification_status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'File ini sudah diproses sebelumnya.'
            ], 400);
        }

        try {
            $baFile->verify(Auth::id(), $request->notes);

            // Link to existing invoice if any
            $this->linkBaFileToExistingInvoice($baFile);

            return response()->json([
                'status' => 'success',
                'message' => 'BA File berhasil di-approve.',
                'data' => $baFile->fresh(['uploader', 'verifier'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error approving BA file: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal approve file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject BA file
     */
    public function rejectBaFile(Request $request, $baFileId)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        if (!auth()->user()->hasPermission('operational.job-schedules.approve-ba')) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Unauthorized. Missing permission: operational.job-schedules.approve-ba'
             ], 403);
        }

        $baFile = \App\Models\JobScheduleBaFile::findOrFail($baFileId);

        if ($baFile->verification_status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'File ini sudah diproses sebelumnya.'
            ], 400);
        }

        try {
            $baFile->reject(Auth::id(), $request->notes);

            return response()->json([
                'status' => 'success',
                'message' => 'BA File ditolak.',
                'data' => $baFile->fresh(['uploader', 'verifier'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error rejecting BA file: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal reject file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete BA file
     */
    public function deleteBaFile($baFileId)
    {
        $baFile = \App\Models\JobScheduleBaFile::findOrFail($baFileId);

        try {
            // Remove from InvoiceFile linked records to prevent broken links
            $cleanPath = str_replace('uploads/', '', $baFile->file_path);
            \App\Models\InvoiceFile::where('file_path', $cleanPath)->delete();

            // Delete physical file
            $filePath = public_path($baFile->file_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Soft delete record
            $baFile->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'BA File berhasil dihapus.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting BA file: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link a BA file to an existing invoice for the same contract and period
     */
    private function linkBaFileToExistingInvoice($baFile)
    {
        $jobSchedule = $baFile->jobSchedule;
        if (!$jobSchedule || !$jobSchedule->schedule_date) return;

        $contractNumber = $jobSchedule->contract_number;
        // Fallback for contract number
        if (!$contractNumber) {
            if ($jobSchedule->jobAdvice && $jobSchedule->jobAdvice->contract) {
                $contractNumber = $jobSchedule->jobAdvice->contract->contract_number;
            } elseif ($jobSchedule->periodicJob && $jobSchedule->periodicJob->contract) {
                $contractNumber = $jobSchedule->periodicJob->contract->contract_number;
            }
        }

        if ($contractNumber) {
            $contract = \App\Models\Contract::where('contract_number', $contractNumber)->first();
            if ($contract && $contract->start_date) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $scheduleDate = \Carbon\Carbon::parse($jobSchedule->schedule_date);
                
                // Use monthly interval logic consistent with hasActiveInvoice
                $diffInMonths = $startDate->diffInMonths($scheduleDate);
                $periodName = "Period " . ($diffInMonths + 1);

                $invoice = \App\Models\Invoice::where('contract_number', $contractNumber)
                    ->where('period_invoice', $periodName)
                    ->where('invoice_status', '!=', 'cancelled')
                    ->first();

                if ($invoice) {
                    $cleanPath = str_replace('uploads/', '', $baFile->file_path);
                    
                    // Check if already linked
                    $exists = \App\Models\InvoiceFile::where('invoice_id', $invoice->id)
                        ->where('file_path', $cleanPath)
                        ->exists();

                    if (!$exists) {
                        \App\Models\InvoiceFile::create([
                            'invoice_id' => $invoice->id,
                            'file_type' => 'attachment',
                            'file_name' => 'BA File - ' . ($baFile->room_name ?: 'Unknown Room') . ' - ' . $baFile->file_name,
                            'file_path' => $cleanPath,
                            'file_size' => $baFile->file_size,
                            'mime_type' => $baFile->mime_type,
                            'description' => "BA File dari Job #{$jobSchedule->job_number} Ruangan {$baFile->room_name}",
                            'uploaded_by' => $baFile->uploaded_by,
                            'uploaded_at' => $baFile->uploaded_at,
                        ]);
                        \Log::info("BA File #{$baFile->id} linked to existing Invoice #{$invoice->id}");
                    }
                }
            }
        }
    }
    /**
     * Validate web/admin completion flow so Done Job cannot skip technician steps.
     *
     * @param JobSchedule $jobSchedule
     * @param string|null $targetStatus
     * @return bool|array
     */
    private function validateWebCompletionTransition(JobSchedule $jobSchedule, ?string $targetStatus)
    {
        if (!$targetStatus || $targetStatus === $jobSchedule->status) {
            return true;
        }

        $completionTargets = ['completed', 'done_job'];
        if (!in_array($targetStatus, $completionTargets, true)) {
            return true;
        }

        $allowedCurrentStatuses = [
            'in_progress',
            'teknisi_sedang_pengerjaan',
            'teknisi_selesai_pengerjaan',
        ];

        if (in_array($jobSchedule->status, $completionTargets, true)) {
            return true;
        }

        if (!in_array($jobSchedule->status, $allowedCurrentStatuses, true)) {
            return [
                'status' => 'error',
                'message' => 'Done Job hanya bisa dilakukan setelah status job masuk tahap teknisi sedang pengerjaan. Ikuti alur: New Job > Material Assign > Material Prep > Material Ready > On Hand Teknisi > On Progress Teknisi > Done Job.',
                'job_number' => $jobSchedule->job_number ?? 'No Job Number',
                'job_id' => $jobSchedule->id,
                'current_status' => $jobSchedule->status,
                'target_status' => $targetStatus,
            ];
        }

        return true;
    }

    private function completeScheduleRoomsFromWeb(JobSchedule $jobSchedule): void
    {
        $now = now();
        $userId = Auth::id() ?: $jobSchedule->updated_by ?: $jobSchedule->created_by;

        $jobSchedule->jobScheduleRooms()
            ->where('status', '!=', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
            ->update([
                'status' => \App\Models\JobScheduleRoom::STATUS_COMPLETED,
                'completed_at' => $now,
                'completed_by' => $userId,
                'completion_notes' => \DB::raw("CASE WHEN completion_notes IS NULL OR completion_notes = '' THEN 'Completed via web Done Job' ELSE completion_notes END"),
                'updated_by' => $userId,
                'updated_at' => $now,
            ]);
    }

    /**
     * Validate web/admin room completion so room Done cannot skip technician steps.
     *
     * @param JobSchedule $jobSchedule
     * @return bool|array
     */
    private function validateWebRoomCompletion(JobSchedule $jobSchedule)
    {
        $allowedCurrentStatuses = [
            'in_progress',
            'teknisi_sedang_pengerjaan',
            'teknisi_selesai_pengerjaan',
        ];

        if (!in_array($jobSchedule->status, $allowedCurrentStatuses, true)) {
            return [
                'status' => 'error',
                'message' => 'Room hanya bisa di-Done dari web setelah job masuk tahap On Progress Teknisi.',
                'job_number' => $jobSchedule->job_number ?? 'No Job Number',
                'job_id' => $jobSchedule->id,
                'current_status' => $jobSchedule->status,
            ];
        }

        return true;
    }

    /**
     * Validate if a job schedule is ready for team assignment.
     * Requirements:
     * 1. Job must have a job_number (generated during Material Assign)
     * 2. Material Issue must exist in job-assign-material-issues
     * 
     * @param JobSchedule $jobSchedule
     * @return bool|array Returns true if valid, or array with error details
     */
    private function validateMakeAssignTeam(JobSchedule $jobSchedule)
    {
        // Remove jobs do not require Material Assign; team can be assigned directly
        // even if a legacy record is currently stuck at assign_material.
        $removeTypes = ['remove', 'remove_free', 'remove free'];
        if (in_array(strtolower(trim($jobSchedule->type ?? '')), $removeTypes, true)) {
            return true;
        }

        // MOM: Status assign_material or barang_dipersiapkan must reach barang_siap_diambil first
        if (in_array($jobSchedule->status, ['assign_material', 'barang_dipersiapkan'])) {
            return [
                'status' => 'error',
                'message' => 'Harap selesaikan material hingga Barang siap diambil, baru Anda dapat memilih tim.',
                'job_number' => $jobSchedule->job_number ?? 'No Job Number',
                'job_id' => $jobSchedule->id
            ];
        }

        // Skip validation for jobs that are already in further progress (re-assignment case)
        if (in_array($jobSchedule->status, ['assign_team', 'barang_siap_diambil', 'barang_diambil', 'teknisi_tiba_dilokasi', 'meninggalkan_lokasi', 'in_progress', 'suspend', 'dpf'])) {
            return true;
        }
        
        // Only validate for new_job and scheduled status
        if (!in_array($jobSchedule->status, ['new_job', 'scheduled'])) {
            return true; // Skip validation for other statuses
        }
        
        // 1. Check if job has job_number (generated during Material Assign)
        if (empty($jobSchedule->job_number)) {
            return [
                'status' => 'error',
                'message' => 'Maaf, Anda belum menyiapkan material. Pilih "Material Assign" terlebih dahulu sebelum menugaskan tim.',
                'job_number' => $jobSchedule->job_number ?? 'No Job Number',
                'job_id' => $jobSchedule->id
            ];
        }
        
        // 2. Check if Material Issue exists via JobAssignSchedule -> JobAssignMaterialIssue
        $hasMaterialIssue = \App\Models\JobAssignMaterialIssue::whereHas('jobAssignSchedule', function($q) use ($jobSchedule) {
            $q->where('job_schedule_id', $jobSchedule->id);
        })->exists();
        
        if (!$hasMaterialIssue) {
            return [
                'status' => 'error',
                'message' => 'Maaf, Anda belum menyiapkan material. Pilih "Material Assign" terlebih dahulu sebelum menugaskan tim.',
                'job_number' => $jobSchedule->job_number ?? 'No Job Number',
                'job_id' => $jobSchedule->id
            ];
        }
        
        return true;
    }

    /**
     * Print CSR for selected job schedules.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function printCsr(Request $request)
    {
        $ids = explode(',', $request->input('ids', ''));
        $viewMode = $request->input('view_mode', 'job');
        
        if (empty($ids) || (count($ids) === 1 && empty($ids[0]))) {
            return response()->json(['status' => 'error', 'message' => 'No IDs provided'], 400);
        }

        $selectedRoomIds = null;
        $targetJobNumbers = [];

        if ($viewMode === 'room') {
            // User selected specific rooms. Only print those specific rooms.
            $selectedRoomIds = $ids;
            $targetJobNumbers = \App\Models\JobScheduleRoom::whereIn('job_schedule_rooms.id', $ids)
                ->join('job_schedules', 'job_schedule_rooms.job_schedule_id', '=', 'job_schedules.id')
                ->pluck('job_schedules.job_number')
                ->unique();
        } else {
            // User selected Job Groups. Fetch the whole group (siblings).
            $targetJobNumbers = JobSchedule::whereIn('id', $ids)->pluck('job_number')->unique();
        }

        // Fetch jobs - ensure we check validity again for security
        $jobs = JobSchedule::with([
            'jobAdvice.customer', 
            'jobAdvice.contract',
            'building', 
            'assignedTechnician', 
            'jobAssignSchedules.team',
            'jobScheduleRooms.room',
            'jobScheduleRooms.jobAdviceRoom.rentalProduct'
        ])
            ->whereIn('job_number', $targetJobNumbers)
            ->whereIn('status', ['done_job', 'completed']) 
            ->get();

    if ($jobs->isEmpty()) {
         return response('No valid completed jobs found to print.', 404);
    }

    // Group by Job Number
    $groupedJobs = $jobs->groupBy('job_number');

    // Note: View will be created in next step
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('operational.job-schedules.pdf-csr', compact('groupedJobs', 'selectedRoomIds'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('CSR_Report_' . date('Ymd_His') . '.pdf');
    }
}


