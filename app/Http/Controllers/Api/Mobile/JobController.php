<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\JobSchedule;
use App\Models\JobAssignMaterialIssue;
use App\Models\JobFavorite;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JobController extends Controller
{
    private array $favoriteLookup = [];
    private array $locationNameLookup = [
        'cities' => [],
        'provinces' => [],
    ];

    /**
     * Get all jobs for authenticated user's teams (not just today)
     * Shows star (is_new) if job was assigned today
     */
    public function getTodayJobs(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'success',  // Changed to success to match Flutter expectation
                'message' => 'No authenticated user',
                'data' => []
            ]);
        }
        
        // Get filter parameters
        $statusFilter = $request->query('status');
        $typeFilter = $request->query('type');
        $searchQuery = $request->query('search');
        $favoriteOnly = $request->query('favorite_only', false);
        
        $userTeamIds = $this->getUserTeamIds($user->id);
        
        // Get ALL jobs assigned to user's teams (not filtered by date)
        // Exclude completed jobs older than 7 days
        $jobs = JobSchedule::with([
            'jobAdvice.customer',
            'jobAdvice.customerContact',
            'jobAdvice.contract.quotation.survey',
            'jobAdvice.contract.billingGroup',
            'jobAdvice.rooms.contractRoom.room',
            'jobAdvice.rooms.quotationRoom.room',
            'building',
            'building.city',
            'building.province',
            'room',
            'jobScheduleRooms',
            'jobAssignSchedules.team'
        ])
        ->whereHas('jobAssignSchedules', function($q) use ($userTeamIds) {
            // Only include active assignments (not cancelled)
            $q->whereIn('team_id', $userTeamIds)
              ->where('status', '!=', 'cancelled')
              ->whereNull('deleted_at'); // Exclude soft-deleted assignments
        }, '>=', 1) // Ensure at least one assignment exists
        // Safety belt: invalid jobs without official Job No must never appear in technician app
        ->whereNotNull('job_number')
        // Only show non-completed jobs (completed jobs go to "Done Job" page)
        // Exclude only truly completed/done jobs
        // Note: 'teknisi_selesai_pengerjaan' should still appear in job list for verification
        // Exclude suspend/dpf and internal admin rollback jobs.
        // "undone" is only for BA Date correction in Job Schedule and must not
        // re-open work in the technician app after the technician finished it.
        ->whereNotIn('status', ['completed', 'done_job', 'selesai', 'suspend', 'dpf', 'undone', 'meninggalkan_lokasi'])
        ->when($statusFilter, function($q) use ($statusFilter) {
            return $q->where('status', $statusFilter);
        })
        ->when($typeFilter, function($q) use ($typeFilter) {
            return $q->where('type', $typeFilter);
        })
        ->when($searchQuery, function($q) use ($searchQuery) {
            return $q->where(function($subQ) use ($searchQuery) {
                $subQ->where('job_number', 'like', "%{$searchQuery}%")
                     ->orWhereHas('jobAdvice.customer', function($customerQ) use ($searchQuery) {
                         $customerQ->where('name', 'like', "%{$searchQuery}%");
                     });
            });
        })
        ->when($favoriteOnly, function($q) use ($user) {
            return $q->whereHas('favorites', function($favQ) use ($user) {
                $favQ->where('user_id', $user->id);
            });
        })
        ->orderBy('id', 'desc') // Newest jobs first (by ID)
        ->get();

        $this->primeFavoriteLookup($jobs->pluck('id')->all(), $user->id);
        
        $groupedJobs = $jobs->groupBy(function($job) {
            if ($job->job_number) {
                return 'jn_' . $job->job_number;
            }
            return 'ref_' . ($job->job_advice_id ?? '0') . '_' . $job->type . '_' . $job->building_id . '_' . ($job->schedule_date ? $job->schedule_date->format('Y-m-d') : 'nodate');
        });

        $jobs = $groupedJobs->map(function($group) use ($user) {
            $job = $group
                ->sortBy(function ($candidate) {
                    $isTerminal = in_array($candidate->status, ['completed', 'done_job', 'selesai'], true);
                    $roomCount = $candidate->relationLoaded('jobScheduleRooms')
                        ? $candidate->jobScheduleRooms->count()
                        : $candidate->jobScheduleRooms()->count();
                    $hasUnfinishedRooms = $roomCount > 0 && (
                        $candidate->relationLoaded('jobScheduleRooms')
                            ? $candidate->jobScheduleRooms->where('status', '!=', \App\Models\JobScheduleRoom::STATUS_COMPLETED)->isNotEmpty()
                            : $candidate->jobScheduleRooms()->where('status', '!=', \App\Models\JobScheduleRoom::STATUS_COMPLETED)->exists()
                    );

                    return [
                        $hasUnfinishedRooms ? 0 : 1,
                        $isTerminal ? 1 : 0,
                        -1 * (int) $candidate->id,
                    ];
                })
                ->first();
            
            // Skip jobs without jobAdvice
            if (!$job->jobAdvice) {
                \Log::warning('getTodayJobs - Job without jobAdvice', [
                    'job_id' => $job->id,
                    'job_number' => $job->job_number,
                    'job_advice_id' => $job->job_advice_id,
                ]);
                return null;
            }
            
            // Get first active assignment (not cancelled, not soft-deleted)
            $jobAssign = $job->jobAssignSchedules
                ->where('status', '!=', 'cancelled')
                ->whereNull('deleted_at')
                ->first();
            
            // Calculate total and completed rooms for the entire GROUP
            $totalRooms = 0;
            $completedRooms = 0;
            $roomNames = [];
            $processedRoomIds = []; // Track processed room IDs to avoid duplication

            foreach ($group as $gItem) {
                $itemRoomId = $gItem->room_id;
                $adviceRooms = $gItem->jobAdvice->rooms ?? collect();
                
                if ($itemRoomId) {
                    if (in_array($itemRoomId, $processedRoomIds)) continue;
                    $processedRoomIds[] = $itemRoomId;

                    $specificRoom = $adviceRooms->first(function($r) use ($itemRoomId) {
                        return ($r->contractRoom && $r->contractRoom->room_id == $itemRoomId) || 
                               ($r->quotationRoom && $r->quotationRoom->room_id == $itemRoomId);
                    });
                    
                    if ($specificRoom) {
                        $totalRooms++;
                        if ($this->isJobScheduleRoomCompleted($gItem, $specificRoom, $itemRoomId)) {
                            $completedRooms++;
                        }
                        $roomNames[] = $specificRoom->room_name;
                    } else {
                        // Fallback: one job schedule = one conceptual room
                        $totalRooms++;
                        if ($gItem->status == 'completed' || $gItem->status == 'done_job') {
                            $completedRooms++;
                        }
                        $roomNames[] = $gItem->room_name ?? $gItem->room?->room_name ?? '-';
                    }
                } else {
                    // If no specific room_id, count all rooms in JA (legacy/catch-all)
                    // But ONLY if we haven't processed any rooms for this group yet to avoid overcounting
                    if (empty($processedRoomIds)) {
                        $totalRooms = $adviceRooms->count();
                        $completedRooms = $adviceRooms->filter(function ($adviceRoom) use ($gItem) {
                            return $this->isJobScheduleRoomCompleted($gItem, $adviceRoom);
                        })->count();
                        foreach($adviceRooms as $ar) $roomNames[] = $ar->room_name;
                        
                        // Mark all advice room IDs as processed to prevent further counting in this group
                        $processedRoomIds = $adviceRooms->pluck('id')->toArray();
                    }
                }
            }

            // Create a representative room name for the group card
            $displayRoomName = count($roomNames) > 1 
                ? $roomNames[0] . ' & ' . (count($roomNames) - 1) . ' lainnya' 
                : ($roomNames[0] ?? '-');
            
            // Use mapJobToArray to get consistent format
            $jobData = $this->mapJobToArray($job, $user, $jobAssign, null, $totalRooms, $completedRooms);
            $jobData['room_name'] = $displayRoomName; // Override with group name
            
            return $jobData;
        })
        ->filter() // Remove null values
        ->sort(function($a, $b) {
            // Priority 1: Jobs assigned today (is_new) appear first
            // Priority 2: Then sort by job ID (newest first)
            $isNewA = $a['is_new'] ?? false;
            $isNewB = $b['is_new'] ?? false;
            
            // If both are new or both are not new, sort by ID (descending)
            if ($isNewA === $isNewB) {
                return ($b['id'] ?? 0) - ($a['id'] ?? 0); // Descending by ID
            }
            
            // New jobs come first
            return $isNewB ? 1 : -1;
        })
        ->values(); // Re-index array
        
        return response()->json([
            'status' => 'success',
            'data' => $jobs // Already values() above
        ]);
    }

    private function isJobScheduleRoomCompleted(JobSchedule $jobSchedule, $jobAdviceRoom, ?int $roomId = null): bool
    {
        $jobScheduleRoom = $this->resolveJobScheduleRoomForAdviceRoom(
            (int) $jobSchedule->id,
            $jobAdviceRoom,
            $roomId
        );

        return $jobScheduleRoom && $jobScheduleRoom->status === 'completed';
    }

    private function resolveJobScheduleRoomForAdviceRoom(int $jobScheduleId, $jobAdviceRoom, ?int $roomId = null)
    {
        $query = \App\Models\JobScheduleRoom::where('job_schedule_id', $jobScheduleId);

        $jobAdviceRoomId = $jobAdviceRoom->id ?? null;
        if ($jobAdviceRoomId) {
            $query->where(function ($roomQuery) use ($jobAdviceRoomId) {
                $roomQuery->where('job_advice_room_id', $jobAdviceRoomId)
                    ->orWhereHas('rentals', function ($rentalQuery) use ($jobAdviceRoomId) {
                        $rentalQuery->where('job_advice_room_id', $jobAdviceRoomId);
                    });
            });
        }

        if ($roomId) {
            $query->orWhere(function ($roomQuery) use ($jobScheduleId, $roomId) {
                $roomQuery->where('job_schedule_id', $jobScheduleId)
                    ->where('room_id', $roomId);
            });
        }

        return $query->orderByDesc('id')->first();
    }
    
    private function mapJobToArray($job, $user, $jobAssign, $room = null, $totalRooms = 0, $completedRooms = 0)
    {
        // Get building address
        $building = $job->building;
        if ($building) {
            // Handle both old format (JSON strings) and new format (IDs)
            $city = '';
            $province = '';
            
            // Check if city is JSON string (old format)
            if (isset($building->city) && is_string($building->city) && ($decodedCity = json_decode($building->city))) {
                $city = $decodedCity->name ?? '';
            } elseif ($building->relationLoaded('city') && $building->city) {
                $city = $building->city->name ?? '';
            }
            // Check if city_id exists (new format)
            elseif (isset($building->city_id)) {
                $city = $this->getLocationName('cities', $building->city_id);
            }
            
            // Check if province is JSON string (old format)
            if (isset($building->province) && is_string($building->province) && ($decodedProvince = json_decode($building->province))) {
                $province = $decodedProvince->name ?? '';
            } elseif ($building->relationLoaded('province') && $building->province) {
                $province = $building->province->name ?? '';
            }
            // Check if province_id exists (new format)
            elseif (isset($building->province_id)) {
                $province = $this->getLocationName('provinces', $building->province_id);
            }
            
            $buildingAddress = trim(implode(', ', array_filter([
                $building->address ?? $building->alamat_1 ?? '',
                $city,
                $province,
            ])));
        } else {
            $buildingAddress = '-';
        }
        
        // Check if materials have been checked from database
        $materialChecked = $job->material_checked ?? false;
        
        // IMPORTANT: If status indicates technician has arrived or work has progressed,
        // material MUST have been checked already (workflow: verify material → arrive → work)
        // This ensures consistency: if status is beyond material verification, materialChecked must be true
        $statusesRequiringMaterialCheck = [
            'barang_diambil',
            'material_verified',
            'material_checked',
            'teknisi_tiba_dilokasi',
            'arrived',
            'in_progress',
            'teknisi_sedang_pengerjaan',
            'work_started',
            'completed',
            'done_job',
            'selesai',
            'teknisi_selesai_pengerjaan'
        ];
        
        // Priority 1: If status is beyond material verification stage, materialChecked MUST be true
        if (in_array($job->status, $statusesRequiringMaterialCheck)) {
            if (!$materialChecked) {
                $materialChecked = true;
                
                // Also update database if not already set
                if (!$job->material_checked) {
                    $job->update([
                        'material_checked' => true,
                        'material_checked_at' => now(),
                    ]);
                }
            }
        }
        // Auto-set material_checked = true for remove jobs (no material verification needed)
        // Remove jobs take units from Unit On Wall, not from inventory issuing
        else if (!$materialChecked && in_array(strtolower($job->type), ['remove', 'remove_free', 'remove free', 'check', 'ganti_unit'])) {
            $materialChecked = true;
            
            // Also update database if not already set
            if (!$job->material_checked) {
                $job->update([
                    'material_checked' => true,
                    'material_checked_at' => now(),
                ]);
            }
        }
        // Specific logic for material verification button:
        // The button should only be active/visible if status is 'barang_siap_diambil' (Ready for Pickup)
        // If it's early status (assign_team, barang_dipersiapkan), it should stay false to NOT trigger "Arrive" button
        else if (!$materialChecked) {
            $allowedStatusesForVerification = ['barang_siap_diambil'];
            if (!in_array(strtolower($job->status), $allowedStatusesForVerification)) {
                // DON'T set material_checked = true here anymore. 
                // Setting it to true was causing Arrival button to appear too early.
                // By keeping it false, Arrival button stays hidden, and App should only show Verify button if status is Ready.
                $materialChecked = false; 
            }
        }
        // Auto-set material_checked = true for service jobs without material issue (e.g., first service)
        // This allows "Tiba di Lokasi" button to be enabled immediately
        else if (!$materialChecked) {
            // Check if this is a service job or change rental
            $isServiceJob = $this->isServiceLikeJob($job);
            $isPeriodOne = ($job->period == 1 || $job->period == null);
            
            if ($isServiceJob && $isPeriodOne) {
                // Check if job has any material issue via job assign schedule
                $hasMaterialIssue = JobAssignMaterialIssue::whereHas('jobAssignSchedule', function($q) use ($job) {
                    $q->where('job_schedule_id', $job->id);
                })->exists();
                
                // If no material issue, auto-set material_checked = true
                if (!$hasMaterialIssue) {
                    $materialChecked = true;
                    
                    // Also update database if not already set
                    if (!$job->material_checked) {
                        $job->update([
                            'material_checked' => true,
                            'material_checked_at' => now(),
                        ]);
                    }
                }
            }
        }
        
        // Check if technician has arrived at location
        $arrivedAtLocation = in_array($job->status, [
            'teknisi_tiba_dilokasi',
            'in_progress',
            'completed'
        ]);
        $canStartWork = in_array($job->status, ['teknisi_tiba_dilokasi', 'barang_diambil'], true);
        
        // Get PIC info from Job Advice (Standard)
        $jobAdvice = $job->jobAdvice;
        $picName = $jobAdvice->customerContact->name ?? null;
        $picPhone = $jobAdvice->customerContact->phone ?? null;
        
        // Fallback to PIC from survey (customer PIC) if not set in Job Advice
        if (!$picName) {
            $contract = $jobAdvice->contract ?? null;
            if ($contract && $contract->quotation && $contract->quotation->survey) {
                // Get PIC from survey (customer contact person)
                $survey = $contract->quotation->survey;
                $picName = $survey->contact_person;
                $picPhone = $survey->phone_1;
            }
        }
        
        // Use specific room if provided, otherwise use job's room
        $roomName = $room ? ($room->name ?? $room->room_name ?? '-') : ($job->room->name ?? $job->room->room_name ?? '-');
        $roomId = $room ? $room->id : ($job->room->id ?? null);
        $roomStatus = $room ? $room->status : null;
        
        return [
            'id' => $job->id,
            'room_id' => $roomId,
            'job_number' => $job->job_number,
            'customer_name' => $job->jobAdvice->customer->name ?? 'N/A',
            'building_name' => $building?->building_name ?? $building?->name ?? $building?->nama_gedung ?? 'N/A',
            'building_address' => $buildingAddress,
            'room_name' => $roomName,
            'room_status' => $roomStatus,
            'schedule_date' => $job->schedule_date,
            'job_date' => $job->schedule_date ? Carbon::parse($job->schedule_date)->format('d M Y') : '-',
            'status' => $job->status,
            'status_label' => $this->getJobStatusLabel($job->status),
            'type' => $job->type,
            'job_type' => $this->getJobTypeLabel($job->type),
            'contract_number' => $job->jobAdvice->contract->contract_number ?? null,
            'is_new' => $jobAssign && $jobAssign->created_at->isToday(),
            'is_favorite' => $this->isFavorite($job->id, $user->id),
            'team' => $jobAssign?->team->team_name ?? '-',
            'notes' => $job->notes,
            'total_rooms' => $totalRooms,
            'completed_rooms' => $completedRooms,
            'material_checked' => (bool)$materialChecked,
            'arrived_at_location' => (bool)$arrivedAtLocation,
            'can_start_work' => (bool)$canStartWork,
            'requires_start_work' => $this->isServiceLikeJob($job),
            'pic_name' => $picName,
            'pic_phone' => $picPhone,
        ];
    }

    /**
     * Keep one active photo snapshot per job + photo type.
     * Old files stay in storage, but the UI should not keep stacking duplicate
     * PIC/signature rows every time a job is re-done after unpost.
     */
    private function syncJobPhotoRecord(int $jobScheduleId, string $photoType, string $photoPath, string $description, ?int $jobScheduleRoomId = null): void
    {
        $jobPhoto = \App\Models\JobPhoto::where('job_schedule_id', $jobScheduleId)
            ->where('photo_type', $photoType)
            ->when($jobScheduleRoomId, function ($query) use ($jobScheduleRoomId) {
                $query->where('job_schedule_room_id', $jobScheduleRoomId);
            })
            ->latest('id')
            ->first();

        if ($jobPhoto) {
            $jobPhoto->update([
                'photo_path' => $photoPath,
                'description' => $description,
                'job_schedule_room_id' => $jobScheduleRoomId ?: $jobPhoto->job_schedule_room_id,
                'uploaded_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            return;
        }

        \App\Models\JobPhoto::create([
            'job_schedule_id' => $jobScheduleId,
            'job_schedule_room_id' => $jobScheduleRoomId,
            'photo_path' => $photoPath,
            'photo_type' => $photoType,
            'description' => $description,
            'uploaded_by' => Auth::id(),
        ]);
    }

    private function getLatestCompletedJobScheduleRoomId(int $jobScheduleId): ?int
    {
        return \App\Models\JobScheduleRoom::where('job_schedule_id', $jobScheduleId)
            ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->value('id');
    }
    
    /**
     * Get completed/done jobs
     */
    public function getDoneJobs(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'success',
                'message' => 'No authenticated user',
                'data' => []
            ]);
        }

        $userTeamIds = $this->getUserTeamIds($user->id);
        
        $jobs = JobSchedule::with([
            'jobAdvice.customer',
            'jobAdvice.contract.quotation.survey',
            'jobAdvice.rooms',
            'building',
            'building.city',
            'building.province',
            'room',
            'jobAssignSchedules.team'
        ])
        ->whereHas('jobAssignSchedules', function($q) use ($userTeamIds) {
            // Only include active assignments (not cancelled, not soft-deleted)
            $q->whereIn('team_id', $userTeamIds)
              ->where('status', '!=', 'cancelled')
              ->whereNull('deleted_at');
        })
        // Safety belt: completed jobs without Job No are data-invalid and must be hidden from mobile
        ->whereNotNull('job_number')
        // Show only truly completed/verified jobs
        // Note: 'teknisi_selesai_pengerjaan' should NOT appear here - it needs verification first
        ->whereIn('status', ['done_job', 'completed', 'selesai'])
        ->orderBy('completed_at', 'desc')
        ->orderBy('updated_at', 'desc')
        ->get();

        $this->primeFavoriteLookup($jobs->pluck('id')->all(), $user->id);
        
        $groupedJobs = $jobs->groupBy(function($job) {
            if ($job->job_number) {
                return 'jn_' . $job->job_number;
            }
            return 'ref_' . ($job->job_advice_id ?? '0') . '_' . $job->type . '_' . $job->building_id . '_' . ($job->schedule_date ? $job->schedule_date->format('Y-m-d') : 'nodate');
        });

        $jobs = $groupedJobs->map(function($group) use ($user) {
            $job = $group->first();
            
            // Skip jobs without jobAdvice
            if (!$job->jobAdvice) {
                return null;
            }
            
            // Get first active assignment
            $jobAssign = $job->jobAssignSchedules
                ->where('status', '!=', 'cancelled')
                ->whereNull('deleted_at')
                ->first();
            
            // Calculate total and completed rooms for the entire GROUP
            $totalRooms = 0;
            $completedRooms = 0;
            $roomNames = [];

            foreach ($group as $gItem) {
                $itemRoomId = $gItem->room_id;
                $adviceRooms = $gItem->jobAdvice->rooms ?? collect();
                
                if ($itemRoomId) {
                    $specificRoom = $adviceRooms->first(function($r) use ($itemRoomId) {
                        return ($r->contractRoom && $r->contractRoom->room_id == $itemRoomId) || 
                               ($r->quotationRoom && $r->quotationRoom->room_id == $itemRoomId);
                    });
                    
                    if ($specificRoom) {
                        $totalRooms++;
                        if ($specificRoom->status == 'completed') {
                            $completedRooms++;
                        }
                        $roomNames[] = $specificRoom->room_name;
                    } else {
                        $totalRooms++;
                        if ($gItem->status == 'completed' || $gItem->status == 'done_job') {
                            $completedRooms++;
                        }
                        $roomNames[] = $gItem->room_name ?? $gItem->room?->room_name ?? '-';
                    }
                } else {
                    $totalRooms += $adviceRooms->count();
                    $completedRooms += $adviceRooms->where('status', 'completed')->count();
                    $roomNames[] = $gItem->room_name ?? $gItem->room?->room_name ?? '-';
                }
            }

            // Create a representative room name
            $displayRoomName = count($roomNames) > 1 
                ? $roomNames[0] . ' & ' . (count($roomNames) - 1) . ' lainnya' 
                : ($roomNames[0] ?? '-');
            
            // Use mapJobToArray to get consistent format
            $jobData = $this->mapJobToArray($job, $user, $jobAssign, null, $totalRooms, $completedRooms);
            $jobData['room_name'] = $displayRoomName; // Override with group name
            
            return $jobData;
        })
        ->filter(); // Remove null values
        
        return response()->json([
            'status' => 'success',
            'data' => $jobs->values()
        ]);
    }

    /**
     * Get suspend/DPF jobs
     */
    public function getSuspendDpfJobs(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'success',
                'message' => 'No authenticated user',
                'data' => []
            ]);
        }
        
        $userTeamIds = $this->getUserTeamIds($user->id);
        
        $jobs = JobSchedule::with([
            'jobAdvice.customer',
            'jobAdvice.customerContact',
            'jobAdvice.contract.quotation.survey',
            'jobAdvice.rooms',
            'building',
            'building.city',
            'building.province',
            'room',
            'jobAssignSchedules.team'
        ])
        ->whereHas('jobAssignSchedules', function($q) use ($userTeamIds) {
            $q->whereIn('team_id', $userTeamIds);
        })
        // Safety belt: invalid jobs without Job No must not leak into mobile special lists
        ->whereNotNull('job_number')
        // Show only suspend and dpf jobs
        ->whereIn('status', ['suspend', 'dpf'])
        ->orderBy('updated_at', 'desc')
        ->orderBy('id', 'desc')
        ->get();

        $this->primeFavoriteLookup($jobs->pluck('id')->all(), $user->id);

        $groupedJobs = $jobs->groupBy(function($job) {
            if ($job->job_number) {
                return 'jn_' . $job->job_number;
            }
            return 'ref_' . ($job->job_advice_id ?? '0') . '_' . $job->type . '_' . $job->building_id . '_' . ($job->schedule_date ? $job->schedule_date->format('Y-m-d') : 'nodate');
        });

        $jobs = $groupedJobs->map(function($group) use ($user) {
            $job = $group->first();
            
            // Skip jobs without jobAdvice
            if (!$job->jobAdvice) {
                return null;
            }
            
            $jobAssign = $job->jobAssignSchedules->where('status', '!=', 'cancelled')->sortByDesc('id')->first();
            
            // Calculate total and completed rooms for the entire GROUP
            $totalRooms = 0;
            $completedRooms = 0;
            $roomNames = [];

            foreach ($group as $gItem) {
                $itemRoomId = $gItem->room_id;
                $adviceRooms = $gItem->jobAdvice->rooms ?? collect();
                
                if ($itemRoomId) {
                    $specificRoom = $adviceRooms->first(function($r) use ($itemRoomId) {
                        return ($r->contractRoom && $r->contractRoom->room_id == $itemRoomId) || 
                               ($r->quotationRoom && $r->quotationRoom->room_id == $itemRoomId);
                    });
                    
                    if ($specificRoom) {
                        $totalRooms++;
                        if ($specificRoom->status == 'completed') {
                            $completedRooms++;
                        }
                        $roomNames[] = $specificRoom->room_name;
                    } else {
                        $totalRooms++;
                        if ($gItem->status == 'completed' || $gItem->status == 'done_job') {
                            $completedRooms++;
                        }
                        $roomNames[] = $gItem->room_name ?? $gItem->room?->room_name ?? '-';
                    }
                } else {
                    $totalRooms += $adviceRooms->count();
                    $completedRooms += $adviceRooms->where('status', 'completed')->count();
                    $roomNames[] = $gItem->room_name ?? $gItem->room?->room_name ?? '-';
                }
            }

            // Create a representative room name
            $displayRoomName = count($roomNames) > 1 
                ? $roomNames[0] . ' & ' . (count($roomNames) - 1) . ' lainnya' 
                : ($roomNames[0] ?? '-');
            
            // Use mapJobToArray to get consistent format
            $jobData = $this->mapJobToArray($job, $user, $jobAssign, null, $totalRooms, $completedRooms);
            $jobData['room_name'] = $displayRoomName; // Override with group name
            
            return $jobData;
        })
        ->filter(); // Remove null values
        
        return response()->json([
            'status' => 'success',
            'data' => $jobs->values()
        ]);
    }
    
    /**
     * Get job detail
     */
    public function getJobMaterials($jobScheduleId)
    {
        // Get job assign schedule first
        $jobAssignSchedule = DB::table('job_assign_schedules')
            ->where('job_schedule_id', $jobScheduleId)
            ->first();
        
        if (!$jobAssignSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job assignment not found'
            ], 404);
        }

        $materialCompletionService = app(\App\Services\Operational\JobMaterialCompletionService::class);
        
        // Get material issues with items (detail list)
        $materials = JobAssignMaterialIssue::withTrashed()
        ->with([
            'materialIssue.items.product.productType',
            'materialIssue.warehouse'
        ])
        ->where('job_assign_schedule_id', $jobAssignSchedule->id)
        ->get()
        ->flatMap(function($jobAssignMaterial) use ($job, $materialCompletionService) {
            // Skip if materialIssue is null (deleted or missing)
            if (!$jobAssignMaterial->materialIssue) {
                return [];
            }
            
            $materialIssue = $jobAssignMaterial->materialIssue;
            
            // Get all items from this material issue
            if (!$materialIssue->items || $materialIssue->items->isEmpty()) {
                return [];
            }
            
            // Return each item as separate material entry
            // MANDATORY FIX: Filter items by current job_assign_schedule_id
            return $materialIssue->items
                ->filter(function($item) use ($jobAssignSchedule) {
                    // Match by job_assign_schedule_id (new column)
                    if ($item->job_assign_schedule_id) {
                        return $item->job_assign_schedule_id == $jobAssignSchedule->id;
                    }
                    // Fallback: If old data, match by room_name? 
                    // This is less accurate for multi-room jobs but better than showing all
                    if ($item->room_name && $jobAssignSchedule->room_id) {
                        // Assuming room_name in MI Item matches the one in schedule's room metadata
                        // But since we are adding the column, we prioritize the ID.
                        return true; // For legacy, we might show all to avoid missing items
                    }
                    return true;
                })
                ->map(function($item) use ($materialIssue, $job, $materialCompletionService) {
                    $effectiveStatus = $materialCompletionService->getEffectiveItemStatus($item, $job);
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? '-',
                        'product_type' => $item->product->productType->name ?? '-',
                        'product_code' => $item->product->sku ?? '-', 
                        'kode' => $item->product->sku ?? '-', 
                        'quantity' => $item->quantity ?? 0,
                        'unit' => $item->product->unit ?? 'pcs',
                        'warehouse' => $materialIssue->warehouse->name ?? '-',
                        'issue_number' => $materialIssue->issue_number ?? '-',
                        'status' => $effectiveStatus,
                        'material_issue_status' => $materialIssue->status ?? 'pending',
                        'usage_status' => $effectiveStatus,
                        'room_name' => $item->room_name,
                        'job_assign_schedule_id' => $item->job_assign_schedule_id,
                    ];
                });
        })
        ->groupBy('product_id')
        ->map(function($group) {
            $first = $group->first();
            
            // If it's a single item, just return it as is
            if ($group->count() <= 1) {
                return $first;
            }
            
            // Aggregation logic for multiple items of the same product
            $totalQty = 0;
            $rooms = [];
            
            foreach ($group as $item) {
                $totalQty += (float)($item['quantity'] ?? 0);
                if (!empty($item['room_name'])) {
                    $rooms[] = $item['room_name'];
                }
            }
            
            $uniqueRooms = array_unique($rooms);
            
            // Update the representative item with aggregated data
            $first['quantity'] = $totalQty;
            $first['room_name'] = !empty($uniqueRooms) ? implode(', ', $uniqueRooms) : '-';
            // Note: 'id' remains the ID of the first item, which is fine since confirmMaterials 
            // uses jobScheduleId, not the item ID.
            
            return $first;
        })
        ->values(); // Re-index array
        
        return response()->json([
            'status' => 'success',
            'data' => $materials
        ]);
    }
    
    /**
     * Toggle job favorite
     */
    public function toggleFavorite(Request $request, $jobScheduleId)
    {
        $user = $request->user();
        
        $favorite = JobFavorite::where('user_id', $user->id)
            ->where('job_schedule_id', $jobScheduleId)
            ->first();
        
        if ($favorite) {
            // Remove from favorites
            $favorite->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Job removed from favorites',
                'is_favorite' => false
            ]);
        } else {
            // Add to favorites
            JobFavorite::create([
                'user_id' => $user->id,
                'job_schedule_id' => $jobScheduleId,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Job added to favorites',
                'is_favorite' => true
            ]);
        }
    }
    
    /**
     * Confirm materials received
     */
    public function confirmMaterials(Request $request, $jobScheduleId)
    {
        $job = JobSchedule::find($jobScheduleId);
        
        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
            ], 423);
        }

        // Validate status: Only allow confirmation if status is 'barang_siap_diambil'
        // This ensures technicians can't confirm materials that are still being prepared
        if (strtolower($job->status) !== 'barang_siap_diambil') {
             return response()->json([
                'status' => 'error',
                'message' => 'Material belum siap diambil. Harap tunggu status "Barang Siap Diambil".'
            ], 400);
        }
        
        // Update material_checked flag
        $job->material_checked = true;
        $job->material_checked_at = now();
        $job->save();

        // MOM16: Auto-finalize related Inventory Issuings
        try {
            $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($job) {
                $q->where('job_schedule_id', $job->id);
            })->with('materialIssue')->get();

            $issueNumbers = $materialIssueItems->map(fn($item) => $item->materialIssue?->issue_number)->unique()->filter()->toArray();

            if (!empty($issueNumbers)) {
                $issuings = \App\Models\InventoryIssuing::whereIn('reference_no', $issueNumbers)
                    ->where('status', 'processed')
                    ->get();

                $service = new \App\Services\Warehouse\InventoryIssuingService();
                foreach ($issuings as $issuing) {
                    $service->finalize($issuing);
                }
            } else {
                \Log::warning("Mobile API: No issue numbers found for Job {$job->id}");
            }
        } catch (\Exception $e) {
            \Log::error("Mobile API: Failed to auto-finalize issuings for Job {$job->id}: " . $e->getMessage());
            // We don't fail the whole request since material_checked is already saved
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Materials confirmed successfully and inventory finalized.',
            'data' => [
                'material_checked' => true
            ]
        ]);
    }
    
    
    /**
     * Get room details for a job
     */
    public function getJobRooms($jobScheduleId)
    {
        try {
            $job = JobSchedule::with([
                'jobAdvice.rooms.contractRoom.room',
                'jobAdvice.rooms.quotationRoom.room',
                'jobAdvice.rooms.rentalProduct.rentalComponents.preferredProducts.productType',
                'jobAssignSchedules',
                'building' // Load building for fallback MasterRoom lookup
            ])->find($jobScheduleId);
            
            if (!$job) {
                \Log::warning("Job not found: {$jobScheduleId}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found'
                ], 404);
            }
            
            if (!$job->jobAdvice) {
                \Log::warning("Job advice not found for job: {$jobScheduleId}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job advice not found'
                ], 404);
            }

            if ($job->status === 'undone') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
                ], 423);
            }
            
            // Get job assign schedule for material issue items lookup
            $jobAssign = $job->jobAssignSchedules->where('status', '!=', 'cancelled')->sortByDesc('id')->first();
            
            // Check if rooms exist
            if (!$job->jobAdvice->rooms || $job->jobAdvice->rooms->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }
            
        // ALIGNMENT: Filter rooms to only the specific rooms assigned to this job group
        $allRooms = $job->jobAdvice->rooms;
        $jobRoomIds = [$job->room_id];
        $forceEmptyRooms = false;

        // 1. Get all sibling jobs that share this job context
        $siblingJobIds = [$job->id];
        if ($job->job_number) {
            $siblingJobIds = JobSchedule::where('job_number', $job->job_number)
                ->where('job_advice_id', $job->job_advice_id)
                ->pluck('id')
                ->toArray();
        } elseif ($job->job_advice_id) {
            $siblingJobIds = JobSchedule::where('job_advice_id', $job->job_advice_id)
                ->where('type', $job->type)
                ->where('building_id', $job->building_id)
                ->whereDate('schedule_date', $job->schedule_date)
                ->pluck('id')
                ->toArray();
        }

        // 2. Check for explicit Room-Level Assignments
        $hasAnyRoomAssignment = \App\Models\JobScheduleRoomAssignment::whereIn('job_schedule_id', $siblingJobIds)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasAnyRoomAssignment) {
            // A. Strict Room Assignment Mode: Only allow rooms explicitly assigned to this technician's team
            $technicianTeamId = null;
            $authenticatedUser = auth()->user();
            $userTeamIds = $authenticatedUser ? $this->getUserTeamIds($authenticatedUser->id) : [];

            // IMPORTANT:
            // Job list visibility uses all user teams (getUserTeamIds), so room detail must also
            // resolve the team based on the current job assignment context instead of taking the
            // first team relation arbitrarily. Otherwise multi-team technicians can see the job
            // card but receive an empty room list for the wrong team.
            $assignedTeamIds = \App\Models\JobScheduleRoomAssignment::whereIn('job_schedule_id', $siblingJobIds)
                ->where('status', '!=', 'cancelled')
                ->pluck('team_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            foreach ($assignedTeamIds as $assignedTeamId) {
                if (in_array($assignedTeamId, $userTeamIds)) {
                    $technicianTeamId = $assignedTeamId;
                    break;
                }
            }

            if (!$technicianTeamId && isset($jobAssign) && $jobAssign->team_id && in_array($jobAssign->team_id, $userTeamIds)) {
                $technicianTeamId = $jobAssign->team_id;
            }

            $assignedJobScheduleRoomIds = \App\Models\JobScheduleRoomAssignment::whereIn('job_schedule_id', $siblingJobIds)
                ->where('team_id', $technicianTeamId)
                ->where('status', '!=', 'cancelled')
                ->pluck('job_schedule_room_id');

            $validRoomIds = \App\Models\JobScheduleRoom::whereIn('id', $assignedJobScheduleRoomIds)
                ->pluck('room_id')
                ->filter()
                ->toArray();

            if (empty($validRoomIds) && $technicianTeamId) {
                $activeAssignments = \App\Models\JobAssignSchedule::whereIn('job_schedule_id', $siblingJobIds)
                    ->where('team_id', $technicianTeamId)
                    ->where('status', '!=', 'cancelled')
                    ->whereNull('deleted_at')
                    ->with('jobSchedule')
                    ->get();

                foreach ($activeAssignments as $activeAssignment) {
                    if ($activeAssignment->jobSchedule) {
                        app(\App\Services\Warehouse\InventoryIssuingService::class)
                            ->syncRoomAssignmentsForJobSchedule(
                                $activeAssignment->jobSchedule,
                                (int) $technicianTeamId,
                                (int) $activeAssignment->id,
                                $activeAssignment->assigned_date?->toDateString()
                            );
                    }
                }

                $assignedJobScheduleRoomIds = \App\Models\JobScheduleRoomAssignment::whereIn('job_schedule_id', $siblingJobIds)
                    ->where('team_id', $technicianTeamId)
                    ->where('status', '!=', 'cancelled')
                    ->pluck('job_schedule_room_id');

                $validRoomIds = \App\Models\JobScheduleRoom::whereIn('id', $assignedJobScheduleRoomIds)
                    ->pluck('room_id')
                    ->filter()
                    ->toArray();
            }

            $jobRoomIds = array_merge($jobRoomIds, $validRoomIds);

            // If the technician is not assigned to ANY room but the job has room assignments, they should see 0 rooms
            if (empty($validRoomIds)) {
                $forceEmptyRooms = true;
            }
        } else {
            // B. Legacy Global Mode
            // FIX: If THIS job has a specific room_id, use ONLY that room.
            // Do NOT merge all sibling room IDs or we'll show 2 rooms per job schedule
            // (e.g. 2 job schedules × 2 rooms = 4 tasks shown incorrectly).
            if ($job->room_id) {
                // Job already has a specific room – $jobRoomIds already contains it
                // Only add JobScheduleRoom entries linked to THIS specific job (not siblings)
                $groupedRoomIds = \App\Models\JobScheduleRoom::where('job_schedule_id', $job->id)
                    ->pluck('room_id')
                    ->filter()
                    ->toArray();
                $jobRoomIds = array_merge($jobRoomIds, $groupedRoomIds);
            } else {
                // No specific room_id on this job → use all sibling job rooms (old behaviour)
                $legacyRoomIds = JobSchedule::whereIn('id', $siblingJobIds)->pluck('room_id')->filter()->toArray();
                $groupedRoomIds = \App\Models\JobScheduleRoom::whereIn('job_schedule_id', $siblingJobIds)
                    ->pluck('room_id')
                    ->filter()
                    ->toArray();
                $jobRoomIds = array_merge($jobRoomIds, $legacyRoomIds, $groupedRoomIds);
            }
        }

        if ($job->job_number && !$hasAnyRoomAssignment && !$job->room_id) {
            $siblingRoomIds = JobSchedule::whereIn('id', $siblingJobIds)->pluck('room_id')->filter()->toArray();
            $siblingScheduleRoomIds = \App\Models\JobScheduleRoom::whereIn('job_schedule_id', $siblingJobIds)
                ->pluck('room_id')
                ->filter()
                ->toArray();
            $jobRoomIds = array_merge($jobRoomIds, $siblingRoomIds, $siblingScheduleRoomIds);
        }

        $jobRoomIds = array_unique(array_filter($jobRoomIds));

        if ($forceEmptyRooms) {
            $targetRooms = collect([]);
        } elseif (!empty($jobRoomIds)) {
            $targetRooms = $allRooms->filter(function($r) use ($jobRoomIds) {
                $roomId = null;
                if ($r->contractRoom) $roomId = $r->contractRoom->room_id;
                elseif ($r->quotationRoom) $roomId = $r->quotationRoom->room_id;
                
                return in_array($roomId, $jobRoomIds);
            });
            
            if ($targetRooms->isEmpty()) {
                // STRICT MODE: If this job has a specific room_id, do NOT fall back to all rooms.
                // Fallback to all rooms was the root cause of showing 4 rooms instead of 2.
                // If job has no room_id, it's a legacy/grouped job — show all advice rooms.
                if ($job->room_id) {
                    \Log::warning("room_ids " . implode(',', $jobRoomIds) . " not matched in advice rooms (contractRoom/quotationRoom may not be set). Returning empty to prevent over-display.");
                    $targetRooms = collect([]);
                } else {
                    \Log::warning("No room_id on job, falling back to all advice rooms (legacy mode).");
                    $targetRooms = $allRooms;
                }
            }
        } else {
            // No room IDs resolved at all: only show all rooms for legacy jobs (no room_id)
            $targetRooms = $job->room_id ? collect([]) : $allRooms;
        }
    

            $rooms = $targetRooms->map(function($room) use ($job, $jobAssign) {
            // Get MasterRoom details via ContractRoom
            // Ensure contractRoom relationship is loaded
            if (!$room->relationLoaded('contractRoom')) {
                $room->load('contractRoom.room');
            }
            
            $masterRoom = null;
            if ($room->contractRoom) {
                if (!$room->contractRoom->relationLoaded('room')) {
                    $room->contractRoom->load('room');
                }
                $masterRoom = $room->contractRoom->room;
            }
            
            // FALLBACK: If MasterRoom not found via ContractRoom, try to find by room_name and building
            if (!$masterRoom && $room->room_name) {
                // Get building from job
                $building = $job->building;
                if ($building) {
                    $masterRoom = \App\Models\MasterRoom::where('room_name', $room->room_name)
                        ->where('building_id', $building->id)
                        ->where('is_active', true)
                        ->first();
                }
            }
            
            // ALIGNMENT: Get the specific JobSchedule ID for this room from our group
            // This is CRITICAL for ensuring the App uses the correct JobSchedule context for each room
            $specificJobScheduleId = null;
            if ($job->job_number) {
                 $roomId = $masterRoom?->id;
                 $match = JobSchedule::where('job_number', $job->job_number)
                    ->where('job_advice_id', $job->job_advice_id)
                    ->where('room_id', $roomId)
                    ->first();
                 $specificJobScheduleId = $match->id ?? $job->id;
            } else {
                 $specificJobScheduleId = $job->id;
            }

            $roomName = $room->room_name ?? 'Room ' . $room->id;
            
            // Get products/materials for this room
            $products = [];
            
            // REMOVE JOB: Get products from Unit On Wall (units with serial numbers)
            // Include remove, remove_free, remove free to handle all remove job types consistently
            if (in_array(strtolower($job->type), ['remove', 'remove_free', 'remove free'])) {
                // Remove job: Get units from Unit On Wall for this room
                $unitOnWallQuery = \App\Models\UnitOnWall::where('status', 'active')
                    ->where('customer_id', $job->jobAdvice->customer_id)
                    ->where('building_id', $job->building_id);
                
                // Filter by room if available
                if ($masterRoom && $masterRoom->id) {
                    $unitOnWallQuery->where('room_id', $masterRoom->id);
                } else {
                    // Fallback: filter by room name
                    if ($roomName) {
                        $unitOnWallQuery->where('room_name', $roomName);
                    }
                }
                
                $unitOnWalls = $unitOnWallQuery
                    ->with(['product.productType', 'serialNumber'])
                    ->get();
                
                foreach ($unitOnWalls as $unit) {
                    if ($unit->product) {
                        // Only show unit products (is_unit = true), not liquids/cleaners
                        if ($unit->product->productType && $unit->product->productType->is_unit) {
                            // Get serial number
                            $serialNumber = '';
                            if ($unit->serialNumber) {
                                $serialNumber = $unit->serialNumber->serial_number ?? '';
                            } elseif ($unit->serial_number) {
                                $serialNumber = $unit->serial_number;
                            }
                            
                            $products[] = [
                                'product_id' => $unit->product_id,
                                'product_name' => $unit->product->name ?? '-',
                                'product_code' => $unit->product->sku ?? '-', // Add SKU
                                'kode' => $unit->product->sku ?? '-', // Add Alias
                                'product_type' => $unit->product->productType->name ?? '-',
                                'quantity' => 1, // One unit per Unit On Wall entry
                                'unit' => $unit->product->unit ?? 'pcs',
                                'source' => 'unit_on_wall',
                                'serial_number' => $serialNumber, // Include serial number
                                'requires_serial_number' => $unit->product->requiresSerialNumber(),
                                'is_unit' => $unit->product->productType?->is_unit ?? $unit->product->productCategory?->is_unit ?? false,
                            ];
                        }
                    }
                }
            } else {
                // OTHER JOBS: Get from Material Issue Items or Rental Components
                // Priority 1: Get from Material Issue Items (factual data)
                if ($jobAssign) {
                    $jobAssignMaterialIssues = $jobAssign->jobAssignMaterialIssues; 
                    
                    // Collect processed MI IDs to avoid duplicates if multiple loops (though unlikely)
                    $processedMiIds = [];

                    foreach ($jobAssignMaterialIssues as $jami) {
                        $materialIssue = $jami->materialIssue;
                        if (!$materialIssue) continue;

                        if (in_array($materialIssue->id, $processedMiIds)) continue;
                        $processedMiIds[] = $materialIssue->id;

                        // Check if there is a linked Inventory Issuing (via Ref No)
                        // Allow pending/processed/sent/received status (exclude draft/cancelled if any)
                        // Note: Aroma Change issuing might be pending or processed.
                        $inventoryIssuing = \App\Models\InventoryIssuing::where('reference_no', $materialIssue->issue_number)
                            ->whereIn('status', ['pending', 'processed', 'sent', 'received']) 
                            ->first();

                        if ($inventoryIssuing) {
                            // Use Inventory Issuing Items (Fact of what was issued)
                            foreach ($inventoryIssuing->items as $item) {
                                // MOM34 Fix: Get the correct JobAssignSchedule ID for THIS specific room
                                // kode sebelumnya menggunakan $room->id (JobAdviceRoom ID) yang salah karena dibandingkan dengan job_assign_schedule_id
                                $specificJobAssign = \App\Models\JobAssignSchedule::where('job_schedule_id', $specificJobScheduleId)
                                    ->where('status', '!=', 'cancelled')
                                    ->whereNull('deleted_at')
                                    ->first();
                                
                                $targetJobAssignScheduleId = $specificJobAssign?->id;
                                $itemMatch = false;
                                
                                if ($item->job_assign_schedule_id && $targetJobAssignScheduleId) {
                                    $itemMatch = ($item->job_assign_schedule_id == $targetJobAssignScheduleId);
                                } elseif ($item->room_name && $roomName) {
                                    $itemMatch = (trim(strtolower($item->room_name)) === trim(strtolower($roomName)));
                                } else {
                                    // Fallback for legacy items or if no specific assignment found: 
                                    // show in all rooms to prevent "missing" materials but log it
                                    $itemMatch = true; 
                                }

                                if (!$itemMatch) {
                                    continue;
                                }
                                
                                if ($item->product) {
                                    // Avoid duplicates in $products array for this room
                                    $exists = collect($products)->contains(function($p) use ($item) {
                                        return $p['product_id'] == $item->product_id;
                                    });
                                    
                                    if (!$exists) {
                                        $products[] = [
                                            'product_id' => $item->product_id,
                                            'product_name' => $item->product->name ?? '-',
                                            'product_code' => $item->product->sku ?? '-',
                                            'kode' => $item->product->sku ?? '-',
                                            'product_type' => $item->product->productType->name ?? '-',
                                            'quantity' => $item->quantity_issued ?? 0, 
                                            'unit' => $item->product->unit ?? 'pcs',
                                            'source' => 'inventory_issuing', 
                                            'serial_number' => '', 
                                            'requires_serial_number' => $item->product->requiresSerialNumber(),
                                            'is_unit' => $item->product->productType?->is_unit ?? $item->product->productCategory?->is_unit ?? false,
                                            'job_assign_schedule_id' => $item->job_assign_schedule_id,
                                            'room_name' => $item->room_name,
                                        ];
                                    }
                                }
                            }
                        } else {
                            // Fallback: Use Material Issue Items (Plan/Request)
                            $miItems = $materialIssue->items()
                                ->where(function($q) use ($roomName) {
                                    if ($roomName) {
                                        $q->where('room_name', $roomName)
                                          ->orWhereNull('room_name');
                                    }
                                })
                                ->with(['product.productType'])
                                ->get();

                            foreach ($miItems as $item) {
                                if ($item->product) {
                                    $exists = collect($products)->contains(function($p) use ($item) {
                                        return $p['product_id'] == $item->product_id;
                                    });
                                    
                                    if (!$exists) {
                                        $products[] = [
                                            'product_id' => $item->product_id,
                                            'product_name' => $item->product->name ?? '-',
                                            'product_code' => $item->product->sku ?? '-',
                                            'kode' => $item->product->sku ?? '-',
                                            'product_type' => $item->product->productType->name ?? '-',
                                            'quantity' => $item->quantity ?? 0,
                                            'unit' => $item->product->unit ?? 'pcs',
                                            'source' => 'material_issue',
                                            'serial_number' => '',
                                            'requires_serial_number' => $item->product->requiresSerialNumber(),
                                            'is_unit' => $item->product->productType?->is_unit ?? $item->product->productCategory?->is_unit ?? false,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Priority 2: If no products from material issue, get from rental components
                if (empty($products) && $room->rentalProduct) {
                    $rental = $room->rentalProduct;
                    $components = $rental->rentalComponents()->where('is_active', true)->get();
                    
                    foreach ($components as $component) {
                        // Get preferred product from component
                        $preferredProduct = $component->preferredProducts()->first();
                        if ($preferredProduct) {
                            $products[] = [
                                'product_id' => $preferredProduct->id,
                                'product_name' => $preferredProduct->name ?? '-',
                                'product_code' => $preferredProduct->sku ?? '-', // Add SKU
                                'kode' => $preferredProduct->sku ?? '-', // Add Alias
                                'product_type' => $preferredProduct->productType->name ?? '-',
                                'quantity' => $component->quantity ?? 1,
                                'unit' => $preferredProduct->unit ?? 'pcs',
                                'source' => 'rental_component',
                                'component_name' => $component->component_name ?? '-',
                                'serial_number' => '', // No serial number for rental components
                                'requires_serial_number' => $this->isServiceLikeJob($job)
                                    ? false
                                    : $preferredProduct->requiresSerialNumber(),
                                'is_unit' => $preferredProduct->productType?->is_unit ?? $preferredProduct->productCategory?->is_unit ?? false,
                            ];
                        }
                    }
                }
            }
            
            // Get dimensions from MasterRoom (access directly using room_length, room_width, room_height)
            // Ensure values are cast to float for JSON response
            $length = $masterRoom ? (float)($masterRoom->room_length ?? 0) : 0.0;
            $width = $masterRoom ? (float)($masterRoom->room_width ?? 0) : 0.0;
            $height = $masterRoom ? (float)($masterRoom->room_height ?? 0) : 0.0;
            
            // Calculate area and volume if dimensions are available
            $area = 0.0;
            $volume = 0.0;
            if ($masterRoom) {
                // Try to get area from database field first
                if (isset($masterRoom->area) && $masterRoom->area > 0) {
                    $area = (float)$masterRoom->area;
                } elseif ($length > 0 && $width > 0) {
                    // Calculate from dimensions
                    $area = (float)($length * $width);
                }
                
                // Calculate volume from dimensions
                if ($length > 0 && $width > 0 && $height > 0) {
                    $volume = (float)($length * $width * $height);
                } elseif (isset($masterRoom->volume) && $masterRoom->volume > 0) {
                    $volume = (float)$masterRoom->volume;
                }
            }
            
            // IR-CSR Dependency Check: Service job cannot start if Install (IR) job in same room & JA is not completed
            $isBlockedByIr = false;
            $blockedByIrMessage = '';
            
            $isServiceJob = $this->isServiceLikeJob($job);
            
            if ($isServiceJob && $masterRoom) {
                // Find if there is an Install job for this room in the same JA that is NOT completed
                $irJobExists = \App\Models\JobSchedule::where('job_advice_id', $job->job_advice_id)
                    ->where('room_id', $masterRoom->id)
                    ->whereIn(\DB::raw('lower(type)'), ['install', 'install_free', 'install free', 'ir'])
                    ->whereNotIn('status', ['completed', 'done_job', 'selesai', 'teknisi_selesai_pengerjaan', 'undone'])
                    ->exists();
                
                if ($irJobExists) {
                    $isBlockedByIr = true;
                    $blockedByIrMessage = 'Harap selesaikan pemasangan unit (Job IR) terlebih dahulu untuk ruangan ini.';
                }
            }

            $jobScheduleRoom = $this->resolveJobScheduleRoomForAdviceRoom($specificJobScheduleId, $room, $masterRoom?->id);
            $roomStatus = $jobScheduleRoom->status ?? 'scheduled';

            return [
                'id' => $room->id,
                'name' => $roomName,
                'status' => $roomStatus,
                'status_label' => $this->getJobStatusLabel($roomStatus),
                'is_blocked_by_ir' => $isBlockedByIr,
                'blocked_by_ir_message' => $blockedByIrMessage,
                'notes' => $room->notes ?? '',
                'job_schedule_id' => $specificJobScheduleId,
                // Detailed Room Info from MasterRoom
                'floor' => $masterRoom->room_floor ?? '-',
                'room_type' => $masterRoom->room_type ?? '-',
                'temperature' => $masterRoom->room_temperature ?? '-',
                'intensity' => $masterRoom->room_intensity ?? '-',
                'installation_type' => $masterRoom->room_installation_type ?? '-',
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'area' => $area,
                'volume' => $volume,
                'remark' => $masterRoom->room_remark ?? '-',
                // Products/Materials for this room
                'products' => $products,
            ];
        });
        
            return response()->json([
                'status' => 'success',
                'data' => $rooms->values() // Ensure proper array indexing
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting job rooms: {$e->getMessage()}", [
                'job_schedule_id' => $jobScheduleId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load rooms: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if a job can be started based on its dependencies (like IR must finish before CSR)
     */
    private function checkJobDependency($job)
    {
        // Only apply to service type jobs (Service First, Service Routine, CSR, dll)
        if (!$this->isServiceLikeJob($job)) {
            return ['is_blocked' => false, 'message' => ''];
        }

        // Must have job advice reference
        if (!$job->job_advice_id) {
            return ['is_blocked' => false, 'message' => ''];
        }

        // Check if there's any INSTALL job for the same Job Advice that is NOT completed
        $blockingInstallJob = \App\Models\JobSchedule::where('job_advice_id', $job->job_advice_id)
            ->when($job->room_id, function ($query) use ($job) {
                $query->where('room_id', $job->room_id);
            })
            ->whereIn(\DB::raw('LOWER(type)'), ['install', 'install_free', 'install free', 'ir'])
            ->whereNotIn('status', ['done_job', 'completed', 'selesai', 'teknisi_selesai_pengerjaan', 'cancelled', 'undone'])
            ->first();

        if ($blockingInstallJob) {
            return [
                'is_blocked' => true,
                'message' => "Pekerjaan Servis ini tidak dapat dikerjakan. Harap pastikan Pekerjaan Pemasangan (IR) dengan nomor {$blockingInstallJob->job_number} diselesaikan terlebih dahulu."
            ];
        }

        return ['is_blocked' => false, 'message' => ''];
    }

    /**
     * Start work on a job
     * Called when technician clicks "Mulai Pekerjaan" after checking SN and entering data
     */
    public function startWork(Request $request, $jobScheduleId)
    {
        $job = JobSchedule::find($jobScheduleId);
        
        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
            ], 423);
        }
        
        // Update status to in_progress (pekerjaan dimulai)
        // Status: teknisi_tiba_dilokasi → in_progress (ketika mulai pekerjaan diklik via apps, setelah tiba di lokasi)
        // Also allow from barang_diambil (backward compatibility if tiba di lokasi was skipped)
        $oldStatus = $job->status;
        $allowedStatuses = ['teknisi_tiba_dilokasi', 'barang_diambil'];
        if (!in_array($job->status, $allowedStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job harus dalam status "Tiba di Lokasi" atau "Barang Diambil" sebelum dapat mulai pekerjaan. Status saat ini: ' . ($job->status_text ?? $job->status)
            ], 400);
        }
        
        // IR-CSR Dependency Check
        $dependencyCheck = $this->checkJobDependency($job);
        if ($dependencyCheck['is_blocked']) {
            return response()->json([
                'status' => 'error',
                'message' => $dependencyCheck['message']
            ], 403);
        }
        
        $job->status = 'in_progress';
        $job->started_at = now();
        $job->updated_by = Auth::id();
        $job->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Pekerjaan dimulai',
            'data' => [
                'job_schedule_id' => $job->id,
                'status' => $job->status,
                'status_text' => $job->status_text ?? 'Pekerjaan Dimulai'
            ]
        ]);
    }
    
    /**
     * Complete work on a room
     */
    /**
     * Complete work on a room
     */
    public function completeRoom(Request $request, $roomId)
    {
        try {
            $request->validate([
                'before_photos' => 'nullable|array',
                'before_photos.*' => 'image|max:5120', // 5MB max per photo
                'after_photos' => 'nullable|array',
                'after_photos.*' => 'image|max:5120', // 5MB max per photo
                'notes' => 'nullable|string',
                'job_schedule_id' => 'nullable|integer', // Optional: specify which job schedule this room completion belongs to
            ]);
            
            $room = \App\Models\JobAdviceRoom::find($roomId);
            
            if (!$room) {
                \Log::warning("Room not found: {$roomId}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Room not found'
                ], 404);
            }

            // DUPLICATION FIX: debounce must be scoped to this JobScheduleRoom,
            // not JobAdviceRoom. Install/IF can complete the global advice room
            // while CSR for the same room still has its own pending work.
            $recentCompletedScheduleRoom = null;
            if ($request->filled('job_schedule_id')) {
                $recentCompletedScheduleRoom = \App\Models\JobScheduleRoom::where('job_schedule_id', $request->job_schedule_id)
                    ->where('job_advice_room_id', $roomId)
                    ->where('status', 'completed')
                    ->where('updated_at', '>=', now()->subSeconds(30))
                    ->first();
            }

            if ($recentCompletedScheduleRoom) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Room completed successfully (duplicate)',
                    'data' => [
                        'room_id' => $room->id,
                        'room_status' => $recentCompletedScheduleRoom->status,
                        'all_completed' => true // Assume true for duplicates
                    ]
                ]);
            }
            
            // Get job schedule for this room
            // IMPORTANT: For remove job, we need to use remove_job_schedule_id from JobAdviceRoom
            // Priority order:
            // 1. If job_schedule_id provided in request (explicit)
            // 2. Check remove_job_schedule_id in JobAdviceRoom (for remove jobs)
            // 3. Check install_job_schedule_id (for install jobs)
            // 4. Find via JobScheduleRoom relationship
            // 5. Fallback: Find by job_advice_id and job type
            $jobSchedule = null;
            $jobAdvice = $room->jobAdvice;
            
            if ($jobAdvice) {
                // Priority 1: If job_schedule_id explicitly provided in request
                if ($request->filled('job_schedule_id')) {
                    $jobSchedule = JobSchedule::find($request->job_schedule_id);
                }
                
                // Priority 2: Check remove_job_schedule_id in JobAdviceRoom (for remove jobs)
                if (!$jobSchedule && $room->remove_job_schedule_id) {
                    $jobSchedule = JobSchedule::find($room->remove_job_schedule_id);
                    if (!($jobSchedule && in_array(strtolower($jobSchedule->type), ['remove', 'remove_free', 'remove free']))) {
                        $jobSchedule = null; // Reset if type doesn't match
                    }
                }
                
                // Priority 3: Check install_job_schedule_id (for install jobs)
                if (!$jobSchedule && $room->install_job_schedule_id) {
                    $jobSchedule = JobSchedule::find($room->install_job_schedule_id);
                    if (!($jobSchedule && in_array(strtolower($jobSchedule->type), ['install', 'install_free', 'install free']))) {
                        $jobSchedule = null; // Reset if type doesn't match
                    }
                }
                
                // Priority 4: Try to find via JobScheduleRoom relationship
                // But filter by job type to avoid conflicts between install and remove jobs
                if (!$jobSchedule) {
                    $jobScheduleRooms = \App\Models\JobScheduleRoom::where('job_advice_room_id', $roomId)
                        ->with('jobSchedule') // Eager load to check type
                        ->get();
                    
                    // Prefer remove job if exists, otherwise service/CSR, then install job
                    $removeJobScheduleRoom = $jobScheduleRooms->first(function($jsr) {
                        return $jsr->jobSchedule && in_array(strtolower($jsr->jobSchedule->type), ['remove', 'remove_free', 'remove free']);
                    });
                    
                    if ($removeJobScheduleRoom && $removeJobScheduleRoom->jobSchedule) {
                        $jobSchedule = $removeJobScheduleRoom->jobSchedule;
                    } else {
                    // Check for service/CSR job (e.g. tipe 'service', 'service_first', 'service_routine')
                    $serviceJobScheduleRoom = $jobScheduleRooms->first(function($jsr) {
                            return $jsr->jobSchedule && in_array(strtolower($jsr->jobSchedule->type), ['service', 'servis', 'service_first', 'service_routine', 'change_rental', 'change rental', 'csr']);
                    });
                        
                        if ($serviceJobScheduleRoom && $serviceJobScheduleRoom->jobSchedule) {
                            $jobSchedule = $serviceJobScheduleRoom->jobSchedule;
                        } else {
                            // Fallback to install job
                            $installJobScheduleRoom = $jobScheduleRooms->first(function($jsr) {
                                return $jsr->jobSchedule && in_array(strtolower($jsr->jobSchedule->type), ['install', 'install_free', 'install free']);
                            });
                            
                            if ($installJobScheduleRoom && $installJobScheduleRoom->jobSchedule) {
                                $jobSchedule = $installJobScheduleRoom->jobSchedule;
                            } elseif ($jobScheduleRooms->isNotEmpty() && $jobScheduleRooms->first()->jobSchedule) {
                                // Last resort: use first JobScheduleRoom found
                                $jobSchedule = $jobScheduleRooms->first()->jobSchedule;
                            }
                        }
                    }
                }
                
                // Priority 5: Fallback: Find by job_advice_id and job type
                if (!$jobSchedule) {
                    // Check if there's a remove job for this job advice (for remove job photo saving)
                    $removeJob = JobSchedule::where('job_advice_id', $jobAdvice->id)
                        ->whereIn('type', ['remove', 'remove_free'])
                        ->first();
                    
                    if ($removeJob) {
                        $jobSchedule = $removeJob;
                    } else {
                        // Cari service/CSR job terlebih dahulu
                        $serviceJob = JobSchedule::where('job_advice_id', $jobAdvice->id)
                            ->whereIn('type', ['service', 'servis', 'service_first', 'service_routine', 'change_rental', 'change rental', 'csr'])
                            ->first();
                        
                        if ($serviceJob) {
                            $jobSchedule = $serviceJob;
                        } else {
                            // Fallback: get install job
                            $installJob = JobSchedule::where('job_advice_id', $jobAdvice->id)
                                ->whereIn('type', ['install', 'install_free', 'install free'])
                                ->first();
                            
                            if ($installJob) {
                                $jobSchedule = $installJob;
                            } else {
                                // Last resort: get first job schedule
                                $jobSchedule = JobSchedule::where('job_advice_id', $jobAdvice->id)->first();
                            }
                        }
                    }
                }
            }
            
            if (!$jobSchedule) {
                \Log::warning("completeRoom: No job schedule found for room {$roomId}, job_advice_id: " . ($jobAdvice->id ?? 'N/A'));
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job schedule untuk room ini tidak ditemukan. Tidak bisa menyelesaikan room tanpa job yang jelas.'
                ], 422);
            }

            if ($jobSchedule->status === 'undone') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat diselesaikan ulang dari aplikasi teknisi.'
                ], 423);
            }

            $serviceCompletableStatuses = ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_tiba_dilokasi', 'barang_diambil'];
            if ($this->isServiceLikeJob($jobSchedule) && !in_array($jobSchedule->status, $serviceCompletableStatuses, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Klik Mulai Pekerjaan terlebih dahulu sebelum menyelesaikan room CSR/Service.',
                    'requires_start_work' => true,
                    'current_status' => $jobSchedule->status,
                ], 422);
            }
            
            // Define jobScheduleRoom variable
            $jobScheduleRoom = null;
            if ($jobSchedule) {
                $jobScheduleRoom = \App\Models\JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)
                    ->where('job_advice_room_id', $roomId)
                    ->first();
            }

            if (!$jobScheduleRoom) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Room ini tidak terdaftar pada job schedule yang sedang dikerjakan.'
                ], 422);
            }

            $hasNewBeforePhoto = $this->requestHasAnyFile($request, 'before_photos');
            $hasNewAfterPhoto = $this->requestHasAnyFile($request, 'after_photos');
            $hasExistingBeforePhoto = $this->jobScheduleRoomHasPhotoType($jobScheduleRoom->id, 'Before Work');
            $hasExistingAfterPhoto = $this->jobScheduleRoomHasPhotoType($jobScheduleRoom->id, 'After Work');

            if ((!$hasNewBeforePhoto && !$hasExistingBeforePhoto) || (!$hasNewAfterPhoto && !$hasExistingAfterPhoto)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Foto sebelum dan sesudah pengerjaan wajib diupload untuk menyelesaikan room.'
                ], 422);
            }

            // Upload and save before photos if provided
            if ($request->hasFile('before_photos')) {
                $uploadPath = public_path('uploads/job-verifications');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                foreach ($request->file('before_photos') as $photo) {
                    if ($photo && $photo->isValid()) {
                        $filename = time() . '_' . uniqid() . '_before.' . $photo->getClientOriginalExtension();
                        $photo->move($uploadPath, $filename);
                        $path = 'job-verifications/' . $filename;
                        
                        if ($jobSchedule) {
                            \App\Models\JobPhoto::create([
                                'job_schedule_id' => $jobSchedule->id,
                                'job_schedule_room_id' => $jobScheduleRoom->id ?? null,
                                'photo_path' => $path,
                                'photo_type' => 'Before Work',
                                'description' => 'Foto sebelum pengerjaan - Room: ' . ($room->room_name ?? 'N/A'),
                                'uploaded_by' => Auth::id(),
                            ]);
                            
                        } else {
                            \Log::error("❌ Before photo NOT saved for room {$roomId} - Job Schedule not found!");
                        }
                    }
                }
            }
            
            // Upload and save after photos if provided
            if ($request->hasFile('after_photos')) {
                $uploadPath = public_path('uploads/job-verifications');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                foreach ($request->file('after_photos') as $photo) {
                    if ($photo && $photo->isValid()) {
                        $filename = time() . '_' . uniqid() . '_after.' . $photo->getClientOriginalExtension();
                        $photo->move($uploadPath, $filename);
                        $path = 'job-verifications/' . $filename;
                        
                        if ($jobSchedule) {
                            \App\Models\JobPhoto::create([
                                'job_schedule_id' => $jobSchedule->id,
                                'job_schedule_room_id' => $jobScheduleRoom->id ?? null,
                                'photo_path' => $path,
                                'photo_type' => 'After Work',
                                'description' => 'Foto sesudah pengerjaan - Room: ' . ($room->room_name ?? 'N/A'),
                                'uploaded_by' => Auth::id(),
                            ]);
                            
                        } else {
                            \Log::error("❌ After photo NOT saved for room {$roomId} - Job Schedule not found!");
                        }
                    }
                }
            }
            
            // Update JobAdviceRoom status to completed
            $room->status = 'completed';
            
            // DUPLICATION FIX: Smart Note Appending
            if ($request->filled('notes')) {
                $newNote = trim($request->notes);
                $currentNotes = $room->notes ?? '';
                
                // Only append if the note is not already present
                if (strpos($currentNotes, $newNote) === false) {
                    $room->notes = ($currentNotes ? $currentNotes . "\n" : '') . $newNote;
                }
            }
            
            $room->save();

            // Ensure JobSchedule status is updated to 'in_progress' if work just started
            if ($jobSchedule && in_array($jobSchedule->status, ['barang_diambil', 'barang_siap_diambil', 'barang_dipersiapkan', 'assign_material', 'assign_team', 'scheduled', 'new_job'])) {
                $jobSchedule->status = 'in_progress';
                $jobSchedule->updated_by = Auth::id();
                $jobSchedule->save();
            }
            
            // STUDY CASE B1: Also update JobScheduleRoom status to completed
            // IMPORTANT: Only update JobScheduleRoom for the job schedule we're working on (not all jobs)
            // This prevents status updates from affecting other jobs (e.g., install free vs remove)
            if ($jobScheduleRoom) {
                // Also update completion notes with smart append logic
                $completionNote = 'Completed via mobile app';
                if ($request->filled('notes')) {
                     // If user provided notes, use them instead of default
                     $completionNote = $request->notes;
                }
                
                $jobScheduleRoom->markAsCompleted(Auth::id(), $completionNote);
            } else {
                \Log::warning("JobScheduleRoom not found for room {$roomId} and job schedule " . ($jobSchedule ? $jobSchedule->id : 'null'));
            }
            
            // Get job through jobAdvice relationship
            $jobAdvice = $room->jobAdvice;
            if (!$jobAdvice) {
                \Log::warning("Job advice not found for room: {$roomId}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job advice not found'
                ], 404);
            }
            
            // reload rooms to get fresh data
            $jobAdvice->load('rooms');
            
            // Check completion at JobScheduleRoom level. One JA can have IR, CSR, and remove,
            // so JobAdviceRoom status must not complete sibling jobs automatically.
            $allCompleted = $jobSchedule ? $jobSchedule->areAllRoomsCompleted() : false;
            
            if ($allCompleted && $jobSchedule && !in_array($jobSchedule->status, ['done_job', 'completed', 'selesai'])) {
                $jobSchedule->status = 'teknisi_selesai_pengerjaan';
                $jobSchedule->updated_by = Auth::id();
                $jobSchedule->save();
            } elseif ($jobSchedule && !in_array($jobSchedule->status, ['teknisi_selesai_pengerjaan', 'done_job', 'completed', 'selesai'])) {
                // If not all rooms done, ensure current job is at least in_progress
                if ($jobSchedule->status !== 'in_progress') {
                    $jobSchedule->status = 'in_progress';
                    $jobSchedule->updated_by = Auth::id();
                    $jobSchedule->save();
                }
            }

            // MANDATORY FIX: Sync Serial Number status to 'in_use' when room is completed
            // This acts as a safety net if status wasn't updated during scan
            if ($jobSchedule && !in_array(strtolower($jobSchedule->type), ['remove', 'remove_free', 'remove free'])) {
                $scannedUnits = \DB::table('job_schedule_units')
                    ->where('job_schedule_id', $jobSchedule->id)
                    ->where('job_advice_room_id', $roomId)
                    ->get();
                
                foreach ($scannedUnits as $scannedUnit) {
                    if ($scannedUnit->mac) {
                        $sn = \App\Models\SerialNumber::where('serial_number', $scannedUnit->mac)->first();
                        
                        if (!$sn) {
                             $unit = \DB::table('units')->where('mac', $scannedUnit->mac)->first();
                             if ($unit && $unit->serial_number) {
                                 $sn = \App\Models\SerialNumber::where('serial_number', $unit->serial_number)->first();
                             }
                        }

                        if ($sn && $sn->status !== 'in_use') {
                            $sn->update([
                                'status' => 'in_use',
                                'location_type' => 'customer',
                                'location_id' => $jobAdvice->customer_id ?? null,
                                'updated_by' => auth()->id()
                            ]);
                        }
                    }
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Room completed successfully',
                'data' => [
                    'room_id' => $room->id,
                    'room_status' => $room->status,
                    'all_completed' => $allCompleted
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Error completing room {$roomId}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete room: ' . $e->getMessage()
            ], 500);
        }
    }

    private function requestHasAnyFile(Request $request, string $key): bool
    {
        if (!$request->hasFile($key)) {
            return false;
        }

        $files = $request->file($key);
        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                return true;
            }
        }

        return false;
    }

    private function requestIndicatesCannotCompleteAllRooms(Request $request): bool
    {
        $truthyKeys = [
            'cannot_complete_all_rooms',
            'cannot_complete_all_room',
            'cannot_complete_all',
            'unable_complete_all_rooms',
            'not_complete_all_rooms',
            'incomplete_job',
            'is_incomplete',
            'leave_location',
            'meninggalkan_lokasi',
        ];

        foreach ($truthyKeys as $key) {
            if ($request->has($key) && filter_var($request->input($key), FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        if ($request->has('complete_all_rooms')) {
            return !filter_var($request->input('complete_all_rooms'), FILTER_VALIDATE_BOOLEAN);
        }

        $completionStatus = strtolower(trim((string) $request->input('completion_status', '')));

        return in_array($completionStatus, [
            'incomplete',
            'cannot_complete_all_rooms',
            'tidak_selesai',
            'meninggalkan_lokasi',
        ], true);
    }

    private function isServiceLikeJob($jobOrType): bool
    {
        $type = is_string($jobOrType) ? $jobOrType : ($jobOrType->type ?? '');
        $normalized = strtolower(trim(str_replace('-', '_', (string) $type)));

        return in_array($normalized, [
            'service',
            'servis',
            'service_first',
            'service first',
            'service_routine',
            'service routine',
            'csr',
            'change_rental',
            'change rental',
        ], true);
    }

    private function jobScheduleRoomHasPhotoType(?int $jobScheduleRoomId, string $photoType): bool
    {
        if (!$jobScheduleRoomId) {
            return false;
        }

        return \App\Models\JobPhoto::where('job_schedule_room_id', $jobScheduleRoomId)
            ->where('photo_type', $photoType)
            ->exists();
    }

    private function validateJobReadyForMobileCompletion(JobSchedule $job): array
    {
        $job->loadMissing(['jobScheduleRooms', 'jobAdvice.rooms']);

        if ($job->jobAdvice && $job->jobAdvice->rooms->isNotEmpty() && $job->jobScheduleRooms->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'Room tracking untuk job ini belum terbentuk. Admin perlu repair data room sebelum job bisa diselesaikan.',
            ];
        }

        if (!$job->areAllRoomsCompleted()) {
            return [
                'ok' => false,
                'message' => 'Masih ada room yang belum diselesaikan pada job ini.',
            ];
        }

        foreach ($job->jobScheduleRooms as $room) {
            $hasBefore = $this->jobScheduleRoomHasPhotoType($room->id, 'Before Work');
            $hasAfter = $this->jobScheduleRoomHasPhotoType($room->id, 'After Work');

            if (!$hasBefore || !$hasAfter) {
                return [
                    'ok' => false,
                    'message' => "Foto sebelum dan sesudah wajib ada untuk room {$room->room_name} sebelum job bisa diselesaikan.",
                ];
            }
        }

        return ['ok' => true, 'message' => null];
    }

    private function syncInstallRoomsFromActiveUnitOnWall(JobSchedule $job): void
    {
        if (!in_array(strtolower(trim($job->type ?? '')), ['install', 'install_free', 'install free'], true)) {
            return;
        }

        $job->loadMissing(['jobAdvice', 'jobScheduleRooms']);
        if (!$job->jobAdvice || !$job->jobAdvice->customer_id || !$job->building_id) {
            return;
        }

        $unfinishedRooms = $job->jobScheduleRooms
            ->where('status', '!=', \App\Models\JobScheduleRoom::STATUS_COMPLETED);

        if ($unfinishedRooms->isEmpty()) {
            return;
        }

        $assignmentIds = $job->jobAssignSchedules()->pluck('id')->all();
        if (empty($assignmentIds)) {
            return;
        }

        $issuedSerialIds = \App\Models\InventoryIssuingItem::whereIn('job_assign_schedule_id', $assignmentIds)
            ->whereNotNull('serial_number_id')
            ->pluck('serial_number_id')
            ->filter()
            ->unique()
            ->all();

        if (empty($issuedSerialIds)) {
            return;
        }

        foreach ($unfinishedRooms as $room) {
            $activeUnitExists = \App\Models\UnitOnWall::whereIn('serial_number_id', $issuedSerialIds)
                ->where('status', 'active')
                ->where('customer_id', $job->jobAdvice->customer_id)
                ->where('building_id', $job->building_id)
                ->where(function ($query) use ($room) {
                    if ($room->room_id) {
                        $query->where('room_id', $room->room_id);
                    }

                    if ($room->room_name) {
                        $query->orWhereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim($room->room_name))]);
                    }
                })
                ->exists();

            if ($activeUnitExists) {
                $room->markAsCompleted(Auth::id(), 'Auto-synced from active Unit On Wall during mobile verification retry');
            }
        }

        $job->unsetRelation('jobScheduleRooms');
    }
    
    /**
     * Upload photo for a job
     */
    public function uploadPhoto(Request $request, $jobScheduleId)
    {
        $request->validate([
            'photo' => 'required|image|max:5120', // Max 5MB
            'type' => 'required|in:before,after,progress',
        ]);
        
        $job = JobSchedule::find($jobScheduleId);
        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat diselesaikan ulang dari aplikasi teknisi.'
            ], 423);
        }
        
        // Store photo
        $photo = $request->file('photo');
        $filename = time() . '_' . $photo->getClientOriginalName();
        $path = $photo->storeAs('job_photos', $filename, 'public');
        
        // Save to database (assuming JobPhoto model exists)
        \App\Models\JobPhoto::create([
            'job_schedule_id' => $jobScheduleId,
            'job_schedule_room_id' => $request->job_schedule_room_id ?? $request->room_id,
            'photo_path' => $path,
            'photo_type' => $request->type,
            'description' => $request->description,
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Photo uploaded successfully',
        ]);
    }
    
    /**
     * Submit signature
     */
    public function submitSignature(Request $request, $jobScheduleId)
    {
        $cannotCompleteAllRooms = $this->requestIndicatesCannotCompleteAllRooms($request);

        if ($cannotCompleteAllRooms) {
            return $this->verifyJob($request, $jobScheduleId);
        }

        $request->validate([
            'signature' => 'required|string',
            'pic_name' => 'required|string',
            'pic_phone' => 'nullable|string',
        ]);
        
        $job = JobSchedule::find($jobScheduleId);
        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat diselesaikan ulang dari aplikasi teknisi.'
            ], 423);
        }

        $readiness = $this->validateJobReadyForMobileCompletion($job);
        if (!$readiness['ok']) {
            return response()->json([
                'status' => 'error',
                'message' => $readiness['message'],
            ], 422);
        }
        
        // Decode and save signature
        $signatureData = $request->signature;
        $filename = 'signature_' . $jobScheduleId . '_' . time() . '.png';
        
        // Remove data:image/png;base64, prefix if exists
        $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
        $signatureData = base64_decode($signatureData);
        
        \Storage::disk('public')->put('signatures/' . $filename, $signatureData);
        
        // Multi-Job Sync Fix: Update ALL related jobs in this visit to done_job (By Job Number, NOT Job Advice ID)
        if ($job->job_number) {
            $relatedJobsByNumber = JobSchedule::where('job_number', $job->job_number)
                ->whereNotIn('status', ['done_job', 'completed', 'selesai', 'undone'])
                ->whereNotIn('type', ['remove', 'remove_free', 'remove free']) // Don't auto-complete removal jobs
                ->get();
                
            foreach ($relatedJobsByNumber as $rJob) {
                $siblingReadiness = $this->validateJobReadyForMobileCompletion($rJob);
                if (!$siblingReadiness['ok']) {
                    \Log::info('submitSignature: skipping sibling auto-completion because it is not independently ready', [
                        'primary_job_id' => $job->id,
                        'sibling_job_id' => $rJob->id,
                        'reason' => $siblingReadiness['message'],
                    ]);
                    continue;
                }

                $rJob->signature_path = 'signatures/' . $filename;
                $rJob->pic_name = $request->pic_name;
                $rJob->pic_phone = $request->pic_phone;
                $rJob->status = 'done_job';
                $rJob->completed_at = now();
                $rJob->updated_by = Auth::id();
                $rJob->save();
                app(\App\Services\Operational\JobMaterialCompletionService::class)
                    ->finalizeForCompletedJob($rJob);
            }
        }
        
        // Ensure the primary job is updated even if it was not in the related query
        if ($job->status !== 'done_job') {
            $job->signature_path = 'signatures/' . $filename;
            $job->pic_name = $request->pic_name;
            $job->pic_phone = $request->pic_phone;
            $job->status = 'done_job';
            $job->completed_at = now();
            $job->save();
            app(\App\Services\Operational\JobMaterialCompletionService::class)
                ->finalizeForCompletedJob($job);
        }

        // Trigger auto-create Unit On Wall if needed
        try {
            $job->load('jobAdvice');
            $jobAdvice = $job->jobAdvice;
            if ($jobAdvice) {
                $installTypes = ['install', 'install_free', 'service', 'change_rental', 'change rental'];
                $jobTypeLower = strtolower(trim($job->type));
                if (in_array($jobTypeLower, $installTypes)) {
                    $jobScheduleController = new \App\Http\Controllers\Operational\JobScheduleController();
                    $reflection = new \ReflectionClass($jobScheduleController);
                    $autoCreateUnitOnWallMethod = $reflection->getMethod('autoCreateUnitOnWall');
                    $autoCreateUnitOnWallMethod->setAccessible(true);
                    $autoCreateUnitOnWallMethod->invoke($jobScheduleController, $job, $jobAdvice);
                }
            }
        } catch (\Exception $e) {
            \Log::error("submitSignature: ❌ Failed to trigger auto-create Unit On Wall for job {$job->job_number}: " . $e->getMessage());
        }

        // AUTO-GENERATE INVOICE (Added to ensure invoice is sparked via signature flow)
        if (in_array($job->status, ['completed', 'done_job']) && $jobAdvice) {
            try {
                $contractId = $jobAdvice->contract_id;
                if ($contractId) {
                    $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
                    $invoiceService->attemptAutoInvoiceForContract($contractId);
                }
            } catch (\Exception $e) {
                \Log::error("Mobile API (Signature): Failed to trigger auto invoice for job {$job->job_number}: " . $e->getMessage());
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Signature submitted successfully'
        ]);
    }
    
    /**
     * Get unit detail by QR code (MAC address) or Serial Number
     */
    public function getUnitByQrCode(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
            'job_schedule_id' => 'required|integer',
        ]);
        
        $input = $request->qr_code;
        $jobScheduleId = $request->job_schedule_id;
        
        // Get job schedule info
        $jobSchedule = JobSchedule::with('jobAdvice.rooms')->find($jobScheduleId);
        
        if (!$jobSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job schedule tidak ditemukan'
            ], 404);
        }

        if ($jobSchedule->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
            ], 423);
        }
        
        // Check if input is serial number (by checking if it exists in serial_numbers for this job)
        // Get material issue items for this job first
        $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($jobScheduleId) {
            $q->where('job_schedule_id', $jobScheduleId);
        })->with(['product'])->get();
        
        // Get serial number if it matches the input and is in the job's materials
        $serialNumber = \App\Models\SerialNumber::where('serial_number', $input)
            ->whereIn('master_product_id', $materialIssueItems->pluck('product_id')->unique())
            ->with(['masterProduct.productType', 'warehouse'])
            ->first();
            
        // Fallback for Service/Change Rental: allow scanning unit that is already on wall
        if (!$serialNumber && ($this->isServiceLikeJob($jobSchedule) || in_array(strtolower($jobSchedule->type), ['check'], true))) {
            $serialNumber = \App\Models\SerialNumber::where('serial_number', $input)
                ->with(['masterProduct.productType', 'warehouse'])
                ->first();
                
        }
        
        // If it's a serial number, return data from serial number
        if ($serialNumber) {
            // Try to find MAC address from unit_on_walls or units table
            $unitOnWall = \DB::table('unit_on_walls')
                ->where('serial_number_id', $serialNumber->id)
                ->first();
            
            $macAddress = null;
            $deviceData = null;
            $snapshot = [];
            
            // If we have unit_on_wall, try to get MAC from units table
            if ($unitOnWall) {
                $unit = \DB::table('units')->where('id', $unitOnWall->unit_id ?? null)->first();
                if ($unit && $unit->mac) {
                    $macAddress = $unit->mac;
                    // Try to get device data from external API
                    $smartScentService = new \App\Services\SmartScentApiService();
                    $apiResult = $smartScentService->getDeviceFullData($macAddress);
                    if ($apiResult['success']) {
                        $deviceData = $apiResult['data'];
                        $snapshot = $deviceData['deviceSnapshot'] ?? [];
                    }
                }
            }
            
            // Prepare response data from serial number
            // Safely access relationships to avoid "attempt to read property on null" errors
            $masterProduct = $serialNumber->masterProduct;
            $productType = $masterProduct ? $masterProduct->productType : null;
            $warehouse = $serialNumber->warehouse;
            
            $responseData = [
                'serial_number' => $serialNumber->serial_number,
                'mac' => $macAddress ?? '-',
                'device_type' => $deviceData['deviceType'] ?? '-',
                'device_name' => $masterProduct ? ($masterProduct->name ?? 'Unit ' . substr($serialNumber->serial_number, -4)) : 'Unit ' . substr($serialNumber->serial_number, -4),
                'product_name' => $masterProduct ? ($masterProduct->name ?? '-') : '-',
                'product_type' => $productType ? ($productType->name ?? '-') : '-',
                'product_code' => $masterProduct ? ($masterProduct->sku ?? '-') : '-', // Add SKU
                'kode' => $masterProduct ? ($masterProduct->sku ?? '-') : '-', // Add Alias
                'online_status' => $snapshot['online'] ?? 0,
                'online_status_text' => ($snapshot['online'] ?? 0) == 1 ? 'Online' : 'Offline',
                'liquid_level' => $snapshot['liquidLevel'] ?? 0,
                'liquid_level_text' => $this->getLiquidLevelText($snapshot['liquidLevel'] ?? 0),
                'power_status' => $snapshot['status'] ?? 0,
                'power_status_text' => ($snapshot['status'] ?? 0) == 1 ? 'On' : 'Off',
                'run_time' => $snapshot['run'] ?? 0,
                'suspend_time' => $snapshot['suspend'] ?? 0,
                'fan_level' => $snapshot['fanLevel'] ?? 0,
                'fan_level_text' => $this->getFanLevelText($snapshot['fanLevel'] ?? 0),
                'screen_lock' => $snapshot['screen'] ?? 0,
                'screen_lock_text' => ($snapshot['screen'] ?? 0) == 1 ? 'Locked' : 'Unlocked',
                'net_mode' => $snapshot['netMode'] ?? 'WIFI',
                'warehouse' => $warehouse ? ($warehouse->name ?? '-') : '-',
                'status' => $serialNumber->status,
                'notes' => $serialNumber->notes ?? '',
                'exists_in_db' => true,
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $responseData
            ]);
        }
        
        // If not serial number, treat as MAC address
        $macAddress = $input;
        
        // Get device data from external API (Smart Scent Pro)
        $smartScentService = new \App\Services\SmartScentApiService();
        $apiResult = $smartScentService->getDeviceFullData($macAddress);
        
        if (!$apiResult['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $apiResult['message'] ?? 'Unit tidak ditemukan'
            ], 404);
        }
        
        $deviceData = $apiResult['data'];
        $snapshot = $deviceData['deviceSnapshot'] ?? [];
        
        // Check if unit exists in our database
        $unit = \DB::table('units')->where('mac', $macAddress)->first();
        
        // Get room name safely (unit table doesn't have relationship, need to query separately)
        $roomName = '-';
        if ($unit && isset($unit->room_id)) {
            $room = \DB::table('master_rooms')->where('id', $unit->room_id)->first();
            if ($room) {
                $roomName = $room->room_name ?? '-';
            }
        }
        
        // Prepare response data
        $responseData = [
            'mac' => $deviceData['mac'],
            'device_type' => $deviceData['deviceType'],
            'device_name' => $unit ? ($unit->device_name ?? 'Unit ' . substr($macAddress, -4)) : 'Unit ' . substr($macAddress, -4),
            'online_status' => $snapshot['online'] ?? 0,
            'online_status_text' => ($snapshot['online'] ?? 0) == 1 ? 'Online' : 'Offline',
            'liquid_level' => $snapshot['liquidLevel'] ?? 0,
            'liquid_level_text' => $this->getLiquidLevelText($snapshot['liquidLevel'] ?? 0),
            'power_status' => $snapshot['status'] ?? 0,
            'power_status_text' => ($snapshot['status'] ?? 0) == 1 ? 'On' : 'Off',
            'run_time' => $snapshot['run'] ?? 0,
            'suspend_time' => $snapshot['suspend'] ?? 0,
            'fan_level' => $snapshot['fanLevel'] ?? 0,
            'fan_level_text' => $this->getFanLevelText($snapshot['fanLevel'] ?? 0),
            'screen_lock' => $snapshot['screen'] ?? 0,
            'screen_lock_text' => ($snapshot['screen'] ?? 0) == 1 ? 'Locked' : 'Unlocked',
            'net_mode' => $snapshot['netMode'] ?? 'WIFI',
            'room_name' => $roomName,
            'notes' => $unit ? ($unit->notes ?? '') : '',
            'exists_in_db' => $unit ? true : false,
        ];
        
        return response()->json([
            'status' => 'success',
            'data' => $responseData
        ]);
    }
    
    /**
     * Save unit data after scan
     */
    public function saveScannedUnit(Request $request)
    {
        try {
            // Check if this is a remove job - remove jobs have different requirements
            $jobSchedule = \App\Models\JobSchedule::find($request->job_schedule_id);
            if ($jobSchedule && $jobSchedule->status === 'undone') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
                ], 423);
            }
            $isRemoveJob = $jobSchedule && in_array(strtolower($jobSchedule->type), ['remove', 'remove_free', 'remove free']);
            
            // Build validation rules
            $rules = [
                'job_schedule_id' => 'required|integer',
                'room_id' => 'required|integer',
                'mac' => 'required|string', // For remove job, this will be serial_number
                'device_type' => 'required|string',
                'device_name' => 'nullable|string',
                'device_snapshot' => 'nullable', // Can be string (JSON) or array - allowed to be null for new/offline units
                'notes' => 'nullable|string',
                'schedule' => 'nullable|array',
                'workTime' => 'nullable|array', // Allow workTime as alternative to schedule
                'photo_path' => 'nullable|string',
                'photos' => 'nullable|array',
                'photos.*' => 'image|max:5120', // 5MB max per photo
            ];
            
            // Only validate unit_on_wall_id if it's provided and not null
            // For remove job, unit_on_wall_id is typically required
            if ($request->filled('unit_on_wall_id')) {
                $rules['unit_on_wall_id'] = 'required|integer|exists:unit_on_walls,id';
            } else {
                $rules['unit_on_wall_id'] = 'nullable|integer';
            }
            
            // For remove job, device_snapshot can be empty object {}
            // For other jobs, device_snapshot should have data
            $validated = $request->validate($rules, [
                'device_snapshot.required' => 'Device snapshot is required',
                'unit_on_wall_id.exists' => 'The selected unit on wall does not exist',
            ]);

            // Requirement 2: Status SN On Hand Remove when scanned for removal
            if ($isRemoveJob && $request->filled('unit_on_wall_id')) {
                $unitOnWall = \App\Models\UnitOnWall::find($request->unit_on_wall_id);
                $selectedRoom = \App\Models\JobAdviceRoom::with(['contractRoom', 'quotationRoom'])->find($request->room_id);
                $selectedRoomId = $selectedRoom?->room_id
                    ?? $selectedRoom?->contractRoom?->room_id
                    ?? $selectedRoom?->quotationRoom?->room_id;

                if ($unitOnWall && $selectedRoomId && $unitOnWall->room_id && (int) $unitOnWall->room_id !== (int) $selectedRoomId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Serial Number ini terpasang di room lain ({$unitOnWall->room_name}). Pilih SN sesuai room yang dikerjakan.",
                    ], 409);
                }

                if ($unitOnWall && $unitOnWall->serial_number_id) {
                    \App\Models\SerialNumber::where('id', $unitOnWall->serial_number_id)->update([
                        'status' => 'on_hand_remove',
                        'location_type' => 'technician',
                        'location_id' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Save scanned unit - Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['device_snapshot']),
            ]);
            throw $e;
        }
        
        try {
            \DB::beginTransaction();
            
            // Parse device_snapshot if it's a string
            $deviceSnapshot = is_string($request->device_snapshot) 
                ? (json_decode($request->device_snapshot, true) ?: [])
                : $request->device_snapshot;
            
            // Ensure device_snapshot is an array
            if (!is_array($deviceSnapshot)) {
                $deviceSnapshot = [];
            }

            $existingScanForSn = \DB::table('job_schedule_units')
                ->where('job_schedule_id', $request->job_schedule_id)
                ->whereRaw('UPPER(TRIM(mac)) = ?', [strtoupper(trim((string) $request->mac))])
                ->first();

            if ($existingScanForSn && (int) ($existingScanForSn->job_advice_room_id ?? 0) !== (int) $request->room_id) {
                \DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$request->mac} sudah dipakai untuk room lain pada job ini. Scan SN yang sesuai dengan room.",
                ], 409);
            }
            
            // NOTE: Photos are NOT saved here in saveScannedUnit
            // Photos will be saved separately via completeRoom endpoint with before_photos and after_photos
            // This prevents duplicate photo saving (photos would be saved twice if we save here)
            // Flow: saveScannedUnit (saves unit data) → completeRoom (saves photos with proper before/after types)
            
            // Initialize empty photoPaths array (photos are handled separately via completeRoom)
            $photoPaths = [];
            
            // Since units table doesn't have the required columns, we'll use job_schedule_units directly
            // We'll store device info in job_schedule_units table
            
            // 1. Use updateOrInsert with job_schedule_id and mac as unique key
            // Since unit_id is nullable, we use mac as the identifier
            $jobUnitData = [
                'job_schedule_id' => $request->job_schedule_id,
                'job_advice_room_id' => $request->room_id, // Save room ID for better tracking
                'unit_id' => null, // unit_id is nullable now
                'unit_on_wall_id' => $request->unit_on_wall_id ?? null, // For remove job
                'mac' => $request->mac,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'device_snapshot' => json_encode($deviceSnapshot),
                'scanned_at' => now(),
                'photo_path' => !empty($photoPaths) ? json_encode($photoPaths) : ($request->photo_path ?? null),
                'notes' => $request->notes,
                'updated_at' => now(),
            ];
            
            // Use updateOrInsert with job_schedule_id and mac
            $updated = \DB::table('job_schedule_units')
                ->where('job_schedule_id', $request->job_schedule_id)
                ->where('mac', $request->mac)
                ->update($jobUnitData);
            
            if ($updated > 0) {
                // Entry was updated, get the ID
                $jobScheduleUnit = \DB::table('job_schedule_units')
                    ->where('job_schedule_id', $request->job_schedule_id)
                    ->where('mac', $request->mac)
                    ->first();
                $jobScheduleUnitId = $jobScheduleUnit->id;
            } else {
                // No entry found, insert new
                $jobUnitData['created_at'] = now();
                $jobScheduleUnitId = \DB::table('job_schedule_units')->insertGetId($jobUnitData);
            }

            // 2. Save Schedule if provided (we'll store in job_schedule_units or separate table)
            // For now, we'll skip unit_schedules since it requires unit_id from units table
            // Schedule data can be stored in device_snapshot or notes if needed
            
            // 3. If schedule or workTime data is provided, we can store it in a JSON field or separate table
            $scheduleData = null;
            if ($request->has('schedule') && !empty($request->schedule)) {
                $scheduleData = $request->schedule;
            } elseif ($request->has('workTime') && !empty($request->workTime)) {
                $scheduleData = $request->workTime;
            }
            
            if ($scheduleData) {
                // Store schedule in device_snapshot
                $currentSnapshot = json_decode($jobUnitData['device_snapshot'] ?? '{}', true) ?: [];
                $currentSnapshot['schedule'] = $scheduleData;
                
                \DB::table('job_schedule_units')
                    ->where('id', $jobScheduleUnitId)
                    ->update([
                        'device_snapshot' => json_encode($currentSnapshot),
                        'updated_at' => now(),
                    ]);
                    
            }

            // 4. Also save to unit_on_walls for warehouse tracking (if needed)
            // Note: unit_on_walls uses serial_number_id, not unit_id
            
            // MANDATORY FIX: Update Serial Number status to 'in_use' for install jobs
            // This ensures consistent tracking in the database (On Hand -> In Use)
            if (!$isRemoveJob) {
                 $macInput = trim((string) $request->mac);
                 
                 if ($macInput !== '' && $macInput !== '-') {
                 // Enhanced logic: Try to find serial number from MAC (serial_number string)
                 // OR find from units table
                 $sn = \App\Models\SerialNumber::where('serial_number', $macInput)->first();
                 
                 if (!$sn) {
                     // Try to find SN via MAC in units table
                     $unit = \DB::table('units')->where('mac', $macInput)->first();
                     if ($unit && $unit->serial_number) {
                         $sn = \App\Models\SerialNumber::where('serial_number', $unit->serial_number)->first();
                         if ($sn) {
                         }
                     }
                 }

                 if ($sn) {
                     $sn->update([
                         'status' => 'in_use',
                         'location_type' => 'customer',
                         'location_id' => $jobSchedule->jobAdvice->customer_id ?? null,
                         'updated_by' => auth()->id()
                     ]);
                 } else {
                     \Log::warning("⚠️ saveScannedUnit: Could not find Serial Number for MAC/Input: {$request->mac}");
                 }
                 }
            }
            
            \DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Unit data saved successfully',
                'data' => [
                    'job_schedule_unit_id' => $jobScheduleUnitId,
                ]
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            // Log error for debugging
            \Log::error('Save scanned unit error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save unit data: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Leave location (meninggalkan lokasi)
     */
    public function leaveLocation(Request $request, $jobScheduleId)
    {
        try {
            $request->validate([
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'device_info' => 'nullable|string',
            ]);
            
            $job = JobSchedule::whereKey($jobScheduleId)->lockForUpdate()->first();
            
            if (!$job) {
                \DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found'
                ], 404);
            }

            if ($job->status === 'undone') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
                ], 423);
            }
            
            // Get user's team
            $userTeamId = DB::table('team_members')
                ->where('user_id', Auth::id())
                ->value('team_id');
            
            // Save location to job_team_locations with action 'left'
            if ($request->has('latitude') && $request->has('longitude')) {
                \App\Models\JobTeamLocation::create([
                    'job_schedule_id' => $job->id,
                    'user_id' => Auth::id(),
                    'team_id' => $userTeamId,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'device_info' => $request->device_info,
                    'action' => 'left',
                    'recorded_at' => now()
                ]);
            }
            
            // Update status to meninggalkan_lokasi
            $job->status = 'meninggalkan_lokasi';
            $job->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Anda telah meninggalkan lokasi'
            ]);
        } catch (\Exception $e) {
            \Log::error('Leave location error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to leave location: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify job completion (verifikasi pekerjaan)
     */
    public function verifyJob(Request $request, $jobScheduleId)
    {
        $cannotCompleteAllRooms = $this->requestIndicatesCannotCompleteAllRooms($request);

        $validator = \Validator::make($request->all(), [
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120', // 5MB max
            'pic_photo' => 'nullable|image|max:5120', // PIC photo
            'notes' => 'nullable|string',
            'signature' => 'nullable|string', // Base64 signature
            'pic_name' => $cannotCompleteAllRooms ? 'nullable|string' : 'required|string',
            'cannot_complete_all_rooms' => 'nullable|boolean', // Checkbox: Tidak dapat menyelesaikan semua ruangan
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            \DB::beginTransaction();
            
            $job = JobSchedule::find($jobScheduleId);
            
            if (!$job) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found'
                ], 404);
            }

            if ($job->status === 'undone') {
                \DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat diselesaikan ulang dari aplikasi teknisi.'
                ], 423);
            }

            $this->syncInstallRoomsFromActiveUnitOnWall($job);

            if (!$cannotCompleteAllRooms) {
                $readiness = $this->validateJobReadyForMobileCompletion($job);
                if (!$readiness['ok']) {
                    \DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => $readiness['message'],
                    ], 422);
                }

                $existingJobReportForValidation = \App\Models\JobReport::where('job_schedule_id', $jobScheduleId)->first();
                $hasPicPhoto = $request->hasFile('pic_photo') || !empty($existingJobReportForValidation?->photo_pic);
                $hasSignature = $request->filled('signature') || !empty($existingJobReportForValidation?->signature_file) || !empty($existingJobReportForValidation?->signature_data);

                if (!$hasPicPhoto || !$hasSignature) {
                    \DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Foto PIC dan tanda tangan wajib diisi sebelum job bisa diselesaikan.',
                    ], 422);
                }
            }
            
            // Upload photos if provided
            $photoUrls = [];
            if ($request->hasFile('photos')) {
                try {
                    $uploadPath = public_path('uploads/job-verifications');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            throw new \Exception('Failed to create upload directory');
                        }
                    }
                    foreach ($request->file('photos') as $photo) {
                        if ($photo && $photo->isValid()) {
                            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                            if (!$photo->move($uploadPath, $filename)) {
                                throw new \Exception('Failed to move photo file: ' . $filename);
                            }
                            $path = 'job-verifications/' . $filename;
                            $photoUrls[] = $path;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("verifyJob: Failed to upload photos", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw new \Exception('Gagal mengunggah foto: ' . $e->getMessage());
                }
            }
            
            // Upload PIC photo if provided
            $picPhotoPath = null;
            if ($request->hasFile('pic_photo')) {
                try {
                    $uploadPath = public_path('uploads/job-verifications');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            throw new \Exception('Failed to create upload directory');
                        }
                    }
                    $picPhoto = $request->file('pic_photo');
                    if ($picPhoto && $picPhoto->isValid()) {
                        $filename = 'pic_' . time() . '_' . uniqid() . '.' . $picPhoto->getClientOriginalExtension();
                        if (!$picPhoto->move($uploadPath, $filename)) {
                            throw new \Exception('Failed to move PIC photo file: ' . $filename);
                        }
                        $picPhotoPath = 'job-verifications/' . $filename;
                    }
                } catch (\Exception $e) {
                    \Log::error("verifyJob: Failed to upload PIC photo", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw new \Exception('Gagal mengunggah foto PIC: ' . $e->getMessage());
                }
            }
            
            // Save signature if provided
            $signaturePath = null;
            if ($request->signature) {
                try {
                    $uploadPath = public_path('uploads/job-verifications');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            throw new \Exception('Failed to create upload directory');
                        }
                    }
                    $signatureData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature));
                    if ($signatureData === false) {
                        throw new \Exception('Invalid base64 signature data');
                    }
                    $filename = 'signature_' . $jobScheduleId . '_' . time() . '.png';
                    $signaturePath = 'job-verifications/' . $filename;
                    if (file_put_contents($uploadPath . '/' . $filename, $signatureData) === false) {
                        throw new \Exception('Failed to save signature file: ' . $filename);
                    }
                } catch (\Exception $e) {
                    \Log::error("verifyJob: Failed to save signature", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw new \Exception('Gagal menyimpan tanda tangan: ' . $e->getMessage());
                }
            }
            
            // IMPORTANT: Get oldStatus BEFORE changing status (for auto-create unit on wall trigger)
            $oldStatus = $job->status; // Save current status before changing
            // Update job updated_by
            $job->updated_by = Auth::id();
            
            // Partial/left-location jobs must not be stamped as BA-completed.
            // They may carry evidence for audit, but invoice/completion flow is status-driven.
            if (!$cannotCompleteAllRooms) {
                if (!$job->ba_date) {
                    $job->ba_date = now()->toDateString();
                }
                if (!$job->ba_number) {
                    $documentNumberService = new \App\Services\DocumentNumberService();
                    $job->ba_number = $documentNumberService->generate('berita_acara', null, null, null, $job->id);
                }
            }
            
            // Store verification data in internal_notes or create JobReport
            // Since job_schedules doesn't have verification columns, we'll use JobReport
            try {
                $existingJobReport = \App\Models\JobReport::where('job_schedule_id', $jobScheduleId)->first();

                $beforeWorkPhoto = \App\Models\JobPhoto::where('job_schedule_id', $jobScheduleId)
                    ->where('photo_type', 'Before Work')
                    ->latest('id')
                    ->value('photo_path');

                $afterWorkPhoto = \App\Models\JobPhoto::where('job_schedule_id', $jobScheduleId)
                    ->where('photo_type', 'After Work')
                    ->latest('id')
                    ->value('photo_path');

                $jobReport = \App\Models\JobReport::updateOrCreate(
                    ['job_schedule_id' => $jobScheduleId],
                    [
                        'technician_id' => Auth::id(),
                        'job_type' => $job->type, // Ensure job_type is set
                        'notes' => $request->notes ?? $job->internal_notes,
                        'photo_pic' => $picPhotoPath ?: $existingJobReport?->photo_pic,
                        'signature_file' => $signaturePath ?: $existingJobReport?->signature_file,
                        'signature_data' => $request->signature ?: $existingJobReport?->signature_data,
                        'pic_name' => $request->pic_name ?: $existingJobReport?->pic_name,
                        'photos' => !empty($photoUrls) ? $photoUrls : ($existingJobReport?->photos ?: null),
                        'photo_before' => $beforeWorkPhoto ?: $existingJobReport?->photo_before,
                        'photo_after' => $afterWorkPhoto ?: $existingJobReport?->photo_after,
                        'completed_at' => $cannotCompleteAllRooms ? null : now(),
                        'signature_at' => $signaturePath ? now() : $existingJobReport?->signature_at,
                    ]
                );
                
                // SYNC FIX: Pull technical data from scan log if missing in report
                // This ensures technical data (MAC, Snapshot) is preserved even if scan was done earlier
                if (empty($jobReport->unit_mac_address) || empty($jobReport->device_snapshot)) {
                    $scannedData = \DB::table('job_schedule_units')
                        ->where('job_schedule_id', $jobScheduleId)
                        ->orderBy('scanned_at', 'desc')
                        ->first();
                    
                    if ($scannedData) {
                        $snapshot = json_decode($scannedData->device_snapshot, true) ?: [];
                        $jobReport->update([
                            'unit_mac_address' => $jobReport->unit_mac_address ?: $scannedData->mac,
                            'unit_serial_number' => $jobReport->unit_serial_number ?: $scannedData->mac,
                            'device_snapshot' => $jobReport->device_snapshot ?: $scannedData->device_snapshot,
                            'device_online_status' => $jobReport->device_online_status ?: ($snapshot['online'] ?? null),
                            'device_liquid_level' => $jobReport->device_liquid_level ?: ($snapshot['liquidLevel'] ?? null),
                            'device_fan_level' => $jobReport->device_fan_level ?: ($snapshot['fanLevel'] ?? null),
                            'qr_scan_at' => $jobReport->qr_scan_at ?: $scannedData->scanned_at,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error("verifyJob: Failed to create/update JobReport", [
                    'job_schedule_id' => $jobScheduleId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new \Exception('Gagal menyimpan data verifikasi: ' . $e->getMessage());
            }
            
            // Save photos to JobPhoto table for display in Photos tab
            // Save before/after photos
            $verificationRoomId = $this->getLatestCompletedJobScheduleRoomId($jobScheduleId);

            if (!empty($photoUrls)) {
                foreach ($photoUrls as $index => $photoPath) {
                    // Determine photo type based on index or filename
                    $photoType = count($photoUrls) > 1 
                        ? ($index == 0 ? 'Before Work' : 'After Work')
                        : 'Work Photo';
                    
                    $this->syncJobPhotoRecord(
                        $jobScheduleId,
                        $photoType,
                        $photoPath,
                        'Foto dokumentasi pekerjaan',
                        $verificationRoomId
                    );
                }
            }
            
            // Save PIC photo
            if ($picPhotoPath) {
                $this->syncJobPhotoRecord(
                    $jobScheduleId,
                    'PIC Photo',
                    $picPhotoPath,
                    'Foto PIC Lapangan',
                    $verificationRoomId
                );
            }
            
            // Save signature as photo
            if ($signaturePath) {
                $this->syncJobPhotoRecord(
                    $jobScheduleId,
                    'Digital Signature',
                    $signaturePath,
                    'Tanda tangan digital PIC Lapangan',
                    $verificationRoomId
                );
            }
            
            // Also update job internal_notes if notes provided
            if ($request->notes) {
                $job->internal_notes = $request->notes;
            }
            
            // Handle checkbox "Tidak dapat menyelesaikan semua ruangan"
            $cannotCompleteAllRooms = $this->requestIndicatesCannotCompleteAllRooms($request);
            
            if ($cannotCompleteAllRooms) {
                $now = now();

                // Reload job with relationships
                $job->load(['jobScheduleRooms', 'jobAssignSchedules.team', 'building']);
                
                // Get unfinished rooms (status != 'completed')
                $unfinishedRooms = $job->jobScheduleRooms()
                    ->whereNotIn('status', [
                        \App\Models\JobScheduleRoom::STATUS_COMPLETED,
                        \App\Models\JobScheduleRoom::STATUS_CANCELLED,
                    ])
                    ->get();
                
                if ($unfinishedRooms->isNotEmpty()) {
                    // Get team and warehouse
                    $team = $job->jobAssignSchedules()->first()?->team;
                    $warehouse = null;
                    $materialReturn = null;
                    $inventoryReceiving = null;
                    
                    // Get warehouse from material issue
                    $materialIssue = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($job) {
                        $q->where('job_schedule_id', $job->id);
                    })->latest('id')->first();
                    
                    if ($materialIssue && $materialIssue->warehouse_id) {
                        $warehouse = \App\Models\Warehouse::find($materialIssue->warehouse_id);
                    }

                    if (!$warehouse && $team?->branch_office) {
                        $warehouse = \App\Models\Warehouse::where('branch_id', $team->branch_office)
                            ->where('is_active', true)
                            ->orderByDesc('is_center')
                            ->orderBy('id')
                            ->first();
                    }

                    if (!$warehouse && $job->building?->branch_id) {
                        $warehouse = \App\Models\Warehouse::where('branch_id', $job->building->branch_id)
                            ->where('is_active', true)
                            ->orderByDesc('is_center')
                            ->orderBy('id')
                            ->first();
                    }

                    if ($warehouse) {
                        // 1. Create Material Return Record for better UI integration
                        $roomNames = $unfinishedRooms->pluck('room_name')->implode(', ');
                        $receivingNote = "Auto-return dari Aplikasi teknisi via Job {$job->job_number} (Pekerjaan tidak selesai). Room: {$roomNames}";

                        $materialReturn = \App\Models\MaterialReturn::where('job_schedule_id', $job->id)
                            ->where('status', \App\Models\MaterialReturn::STATUS_RETURNED)
                            ->where('notes', 'like', 'Auto-return dari Aplikasi teknisi via Job ' . $job->job_number . '%')
                            ->latest('id')
                            ->first();

                        if (!$materialReturn) {
                            $receivingNumber = \App\Models\MaterialReturn::generateReturnNumber($job->id); // Using MaterialReturn format

                            $materialReturn = \App\Models\MaterialReturn::create([
                                'return_number' => $receivingNumber,
                                'job_schedule_id' => $job->id,
                                'warehouse_id' => $warehouse->id,
                                'team_id' => $team?->id,
                                'status' => \App\Models\MaterialReturn::STATUS_RETURNED,
                                'return_date' => $now->toDateString(),
                                'return_reason' => 'Pekerjaan tidak selesai (Auto-return via Mobile App)',
                                'notes' => $receivingNote,
                                'returned_by' => auth()->id(),
                                'returned_at' => $now,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);
                        }

                        // Also create Inventory Receiving for stock integrity
                        $inventoryReceiving = \App\Models\InventoryReceiving::where('reference_no', $job->job_number)
                            ->where('notes', 'like', 'Auto-return dari Aplikasi teknisi via Job ' . $job->job_number . '%')
                            ->latest('id')
                            ->first();

                        if (!$inventoryReceiving) {
                            $inventoryReceiving = \App\Models\InventoryReceiving::create([
                                'receiving_number' => str_replace('RTR', 'IRC', $materialReturn->return_number), // Consistently map RTR to IRC
                                'reference_no' => $job->job_number,
                                'branch_id' => $warehouse->branch_id ?? $job->branch_id, // Use warehouse branch if available
                                'received_from' => auth()->id(),
                                'received_by_old' => auth()->id(),
                                'receive_date' => $now->toDateString(),
                                'status' => 'received',
                                'notes' => $receivingNote,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);
                        }
                    } else {
                        \Log::warning("verifyJob: No warehouse resolved for incomplete job {$job->job_number}. Outstanding job will still be created without auto material return.");
                    }

                    // 2. Create one New JobSchedule for all unfinished rooms
                    $newJob = \App\Models\JobSchedule::where('job_advice_id', $job->job_advice_id)
                        ->where('building_id', $job->building_id)
                        ->where('type', $job->type)
                        ->where('internal_notes', 'like', "Lanjutan dari Job {$job->job_number}%")
                        ->whereNotIn('status', ['cancelled', 'done_job', 'completed', 'selesai'])
                        ->whereHas('jobScheduleRooms', function ($query) use ($unfinishedRooms) {
                            $query->whereIn('job_advice_room_id', $unfinishedRooms->pluck('job_advice_room_id')->filter()->unique()->values());
                        })
                        ->latest('id')
                        ->first();

                    if (!$newJob) {
                        $newJob = new \App\Models\JobSchedule();
                        if (\Illuminate\Support\Facades\Schema::hasColumn('job_schedules', 'customer_id')) {
                            $newJob->customer_id = $job->customer_id;
                        }
                        $newJob->building_id = $job->building_id;
                        $newJob->building_name = $job->building_name;
                        $newJob->company_name = $job->company_name;
                        $newJob->job_advice_id = $job->job_advice_id;
                        $newJob->contract_number = $job->contract_number;
                        $newJob->quotation_number = $job->quotation_number;
                        $newJob->type = $job->type;
                        $newJob->status = 'new_job';
                        $newJob->schedule_date = $now->toDateString();
                        $newJob->expected_date = $job->expected_date;
                        $newJob->job_number = null;
                        $newJob->internal_notes = "Lanjutan dari Job {$job->job_number} (Pekerjaan tidak selesai).";
                        $newJob->created_by = auth()->id();
                        $newJob->updated_by = auth()->id();
                        $newJob->save();
                    }

                    $jobAssignScheduleIds = $job->jobAssignSchedules()->pluck('id');

                    foreach ($unfinishedRooms as $room) {
                        // Move room to new job
                        \App\Models\JobScheduleRoom::firstOrCreate(
                            [
                                'job_schedule_id' => $newJob->id,
                                'job_advice_room_id' => $room->job_advice_room_id,
                            ],
                            [
                                'room_name' => $room->room_name,
                                'room_id' => $room->room_id,
                                'status' => 'pending',
                                'notes' => "Pindahan dari Job {$job->job_number}",
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]
                        );

                        // Get material issue items for this room
                        $materialIssueItems = \App\Models\MaterialIssueItem::whereIn('job_assign_schedule_id', $jobAssignScheduleIds)
                            ->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim((string) $room->room_name))])
                            ->get();

                        if ($materialIssueItems->isEmpty()) {
                            $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($job) {
                                $q->where('job_schedule_id', $job->id);
                            })
                            ->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim((string) $room->room_name))])
                            ->get();
                        }

                        if ($materialReturn && $inventoryReceiving && $warehouse) {
                            foreach ($materialIssueItems as $issueItem) {
                                if (!$issueItem->product_id) {
                                    continue;
                                }

                                $issuedItem = \App\Models\InventoryIssuingItem::whereIn('job_assign_schedule_id', $jobAssignScheduleIds)
                                    ->where('product_id', $issueItem->product_id)
                                    ->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim((string) $room->room_name))])
                                    ->whereHas('inventoryIssuing', function ($query) {
                                        $query->whereIn('status', ['processed', 'sent', 'received']);
                                    })
                                    ->latest('id')
                                    ->first();

                                $quantityToReturn = $issuedItem && (float) $issuedItem->quantity_issued > 0
                                    ? (float) $issuedItem->quantity_issued
                                    : (float) ($issueItem->quantity ?? 0);

                                // Create Material Return Item
                                $returnItem = \App\Models\MaterialReturnItem::firstOrCreate(
                                    [
                                        'material_return_id' => $materialReturn->id,
                                        'material_issue_item_id' => $issueItem->id,
                                    ],
                                    [
                                        'product_id' => $issueItem->product_id,
                                        'room_name' => $room->room_name,
                                        'room_id' => $room->room_id,
                                        'quantity' => $quantityToReturn,
                                        'notes' => "Auto-return dari Room {$room->room_name}",
                                        'created_by' => auth()->id(),
                                        'updated_by' => auth()->id(),
                                    ]
                                );

                                // Create Receiving Item
                                $receivingItem = \App\Models\InventoryReceivingItem::firstOrCreate(
                                    [
                                        'inventory_receiving_id' => $inventoryReceiving->id,
                                        'master_product_id' => $issueItem->product_id,
                                        'notes' => "Auto-return dari Room {$room->room_name} (MI Item {$issueItem->id})",
                                    ],
                                    [
                                        'quantity' => $quantityToReturn,
                                        'quantity_received' => $quantityToReturn,
                                    ]
                                );

                                // UPDATE SERIAL NUMBER STATUS IF ANY
                                if ($issuedItem?->serial_number_id) {
                                    $sn = \App\Models\SerialNumber::find($issuedItem->serial_number_id);
                                    if ($sn) {
                                        $returnedSnCode = $sn->serial_number;

                                        $sn->update([
                                            'status' => 'ready',
                                            'location_type' => 'warehouse',
                                            'location_id' => $warehouse->id,
                                            'inventory_receiving_id' => $inventoryReceiving->id,
                                            'updated_by' => auth()->id(),
                                        ]);

                                        // IMPORTANT:
                                        // When partial completion auto-returns a unit with SN,
                                        // the stock/SN can already be back in warehouse while the
                                        // old Inventory Issuing Item still keeps serial_number_id.
                                        // That stale WI linkage makes the SN look "reserved" and
                                        // blocks future usage even though it is physically returned.
                                        //
                                        // Release the SN from the old issuing row after the return
                                        // has been recorded in Material Return + Inventory Receiving.
                                        $issuedItem->update([
                                            'serial_number_id' => null,
                                            'updated_by' => auth()->id(),
                                            'notes' => trim((string) $issuedItem->notes . ' | Auto-returned to warehouse via partial completion on Job ' . $job->job_number . ' | SN released: ' . $returnedSnCode),
                                        ]);
                                    }
                                }

                                if ($returnItem->wasRecentlyCreated || $receivingItem->wasRecentlyCreated) {
                                    // Update Stock (Auto-Approved) once per returned material row.
                                    $warehouseProduct = \App\Models\WarehouseProduct::firstOrCreate(
                                        [
                                            'warehouse_id' => $warehouse->id,
                                            'master_product_id' => $issueItem->product_id,
                                        ],
                                        [
                                            'quantity' => 0,
                                            'created_by' => auth()->id(),
                                            'updated_by' => auth()->id(),
                                        ]
                                    );
                                    $warehouseProduct->increment('quantity', $quantityToReturn);

                                    \App\Models\InventoryMovement::create([
                                        'warehouse_id' => $warehouse->id,
                                        'master_product_id' => $issueItem->product_id,
                                        'movement_type' => 'in',
                                        'quantity' => abs($quantityToReturn),
                                        'movement_date' => $now->toDateString(),
                                        'reference_no' => $inventoryReceiving->receiving_number,
                                        'reference_type' => 'inventory_receiving',
                                        'movement_no' => str_replace('IRC-', 'REC-', $inventoryReceiving->receiving_number),
                                        'notes' => "Auto-return via Job {$job->job_number} (Parsial, MI Item {$issueItem->id})",
                                        'created_by' => auth()->id(),
                                        'updated_by' => auth()->id(),
                                    ]);
                                }
                            }
                        }

                        // Cancel room from current job and link to material return
                        $room->update([
                            'status' => 'cancelled',
                            'material_return_status' => $materialReturn
                                ? \App\Models\JobScheduleRoom::MATERIAL_RETURN_RETURNED
                                : \App\Models\JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                            'material_return_id' => $materialReturn?->id,
                            'material_return_at' => $materialReturn ? $now : null,
                            'material_return_by' => $materialReturn ? auth()->id() : null,
                            'notes' => "Pekerjaan tidak selesai, dipindahkan ke Job baru.",
                            'updated_by' => auth()->id(),
                        ]);
                    }
                    
                    // Partial completion means the technician left the location.
                    // Keep the original job out of completion/invoice flow; unfinished rooms
                    // are already moved to a follow-up job above.
                    $job->status = 'meninggalkan_lokasi';
                    $job->completed_at = null;
                } else {
                    if ($job->job_number) {
                        $relatedJobs = JobSchedule::where('job_number', $job->job_number)
                            ->whereNotIn('status', ['done_job', 'completed', 'selesai', 'meninggalkan_lokasi', 'undone'])
                            ->whereNotIn('type', ['remove', 'remove_free', 'remove free'])
                            ->get();

                        foreach ($relatedJobs as $rJob) {
                            $rJob->status = 'meninggalkan_lokasi';
                            $rJob->completed_at = null;
                            $rJob->updated_by = Auth::id();
                            $rJob->save();
                        }
                    }

                    $job->status = 'meninggalkan_lokasi';
                    $job->completed_at = null;
                }
            } else {
                // Normal flow: all rooms completed
                // Multi-Job Sync Fix: Update ALL related jobs in this visit to done_job (By Job Number, NOT Job Advice ID)
                // Using job_number ensures only jobs in the SAME visit are synced, protecting future routine services.
                if ($job->job_number) {
                    $relatedJobs = JobSchedule::where('job_number', $job->job_number)
                        ->where('id', '!=', $job->id)
                        ->whereNotIn('status', ['done_job', 'completed', 'selesai', 'undone'])
                        ->whereNotIn('type', ['remove', 'remove_free', 'remove free']) // Don't auto-complete removal jobs
                        ->get();
                    
                    foreach ($relatedJobs as $rJob) {
                        $siblingReadiness = $this->validateJobReadyForMobileCompletion($rJob);
                        if (!$siblingReadiness['ok']) {
                            \Log::info('verifyJob: skipping sibling auto-completion because it is not independently ready', [
                                'primary_job_id' => $job->id,
                                'sibling_job_id' => $rJob->id,
                                'job_number' => $rJob->job_number,
                                'type' => $rJob->type,
                                'reason' => $siblingReadiness['message'],
                            ]);
                            continue;
                        }

                        $rJob->status = 'done_job';
                        $rJob->completed_at = now();
                        $rJob->updated_by = Auth::id();
                        $rJob->save();
                        app(\App\Services\Operational\JobMaterialCompletionService::class)
                            ->finalizeForCompletedJob($rJob);
                    }
                }
                
                // Ensure primary job is set correctly just in case
                $job->status = 'done_job';
                $job->completed_at = now();
                $job->save();
                app(\App\Services\Operational\JobMaterialCompletionService::class)
                    ->finalizeForCompletedJob($job);
                // Note: verified_at column doesn't exist in job_schedules table
                // Verification is tracked via JobReport table instead
            }
            
            // Save the job with updated status
            $job->save();
            
            // Reload to verify status was saved correctly
            $job->refresh();

            if (in_array($job->status, ['completed', 'done_job', 'selesai'], true)) {
                app(\App\Services\Operational\JobMaterialCompletionService::class)
                    ->finalizeForCompletedJob($job);
            }
            
            // AUTO-CREATE UNIT ON WALL and REMOVE JOB for install jobs
            // This logic should match what happens in JobScheduleController@update
            // Trigger auto-create only if status changed from non-completed to completed/done_job
            if (in_array($job->status, ['completed', 'done_job']) && $job->areAllRoomsCompleted()) {
                $job->load('jobAdvice');
                $jobAdvice = $job->jobAdvice;
                
                if ($jobAdvice) {
                    $installTypes = ['install', 'install_free', 'service', 'change_rental', 'change rental'];
                    $jobTypeLower = strtolower(trim($job->type));
                    if (in_array($jobTypeLower, $installTypes)) {
                        // Trigger auto-create logic from JobScheduleController
                        // Use reflection to call private methods autoCreateUnitOnWall and autoCreateRemoveJob
                        try {
                            $jobScheduleController = new \App\Http\Controllers\Operational\JobScheduleController();
                            $reflection = new \ReflectionClass($jobScheduleController);
                            
                            // Get the methods
                            $autoCreateUnitOnWallMethod = $reflection->getMethod('autoCreateUnitOnWall');
                            $autoCreateUnitOnWallMethod->setAccessible(true);
                            $autoCreateRemoveJobMethod = $reflection->getMethod('autoCreateRemoveJob');
                            $autoCreateRemoveJobMethod->setAccessible(true);
                            
                            // [FIX BUG 1 - Mobile API] Run autoCreateUnitOnWall for ALL siblings
                            $schedulesToComplete = \App\Models\JobSchedule::where('job_number', $job->job_number)
                                ->whereIn('status', ['done_job', 'completed', 'selesai'])
                                ->get();
                                
                            $anyUnitCreated = false;
                            
                            foreach ($schedulesToComplete as $completedSchedule) {
                                $unitCreated = $autoCreateUnitOnWallMethod->invoke($jobScheduleController, $completedSchedule, $jobAdvice);
                                if ($unitCreated) $anyUnitCreated = true;
                            }
                            
                            // If unit on wall created and remove_date exists, create remove job for install free
                            if ($anyUnitCreated && $jobAdvice->remove_date) {
                                // Check if this is Install Free
                                $isInstallFree = false;
                                if ($jobAdvice && $jobAdvice->type) {
                                    $jaTypeLower = strtolower(trim($jobAdvice->type));
                                    $isInstallFree = ($jaTypeLower === 'install_free' || $jaTypeLower === 'install free');
                                }
                                
                                if ($isInstallFree) {
                                    // Call autoCreateRemoveJob securely ONCE
                                    $autoCreateRemoveJobMethod->invoke($jobScheduleController, $job, $jobAdvice);
                                }
                            }
                        } catch (\Throwable $e) {
                            \Log::error("verifyJob: ❌ Failed to trigger auto-create Unit On Wall/Remove Job for job {$job->job_number}: " . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'job_id' => $job->id,
                                'job_type' => $job->type,
                                'old_status' => $oldStatus,
                                'new_status' => $job->status,
                            ]);
                            
                            // Don't rollback - allow job to complete even if auto-create fails
                            \Log::warning("verifyJob: Unit On Wall auto-create failed but job completion will continue. Job: {$job->job_number}");
                        }
                    }
                } else {
                    \Log::warning("verifyJob: JobAdvice not found for job {$job->job_number}. Cannot auto-create Unit On Wall.");
                }
            }
            
            // Continue with service job and remove job updates
            $job->load('jobAdvice');
            $jobAdvice = $job->jobAdvice;
            
            if ($jobAdvice) {
                // Auto-update last_service_date for service jobs
                if (in_array(strtolower($job->type), ['service', 'service_first', 'service_routine'])) {
                    try {
                        if (!isset($jobScheduleController)) {
                            $jobScheduleController = new \App\Http\Controllers\Operational\JobScheduleController();
                        }
                        if (!isset($reflection)) {
                            $reflection = new \ReflectionClass($jobScheduleController);
                        }
                        
                        $autoUpdateLastServiceDateMethod = $reflection->getMethod('autoUpdateUnitOnWallLastServiceDate');
                        $autoUpdateLastServiceDateMethod->setAccessible(true);
                        
                        // Call autoUpdateUnitOnWallLastServiceDate
                        $autoUpdateLastServiceDateMethod->invoke($jobScheduleController, $job, $jobAdvice);
                        
                        // MOM13: Trigger routine services generation if first service is done
                        if ($job->period == 1 && in_array(strtolower($job->type), ['service', 'service_first'])) {
                            try {
                                $methodGen = $reflection->getMethod('generateAllRemainingServices');
                                $methodGen->setAccessible(true);
                                $methodGen->invoke($jobScheduleController, $job, $jobAdvice);
                            } catch (\Exception $e) {
                                 \Log::error("MOM13 Error: Failed to trigger routine services generation for job {$job->job_number}: " . $e->getMessage());
                            }
                        }

                        // MOM: Trigger auto create remove job if all services are done
                        try {
                            $methodRemove = $reflection->getMethod('checkAndCreateRemoveJobAfterAllServicesComplete');
                            $methodRemove->setAccessible(true);
                            $methodRemove->invoke($jobScheduleController, $job, $jobAdvice);
                        } catch (\Exception $e) {
                             \Log::error("MOM Error: Failed to trigger auto create remove job check for job {$job->job_number}: " . $e->getMessage());
                        }
                    } catch (\Throwable $e) {

                        \Log::error("Failed to trigger auto-update last_service_date for service job {$job->job_number}: " . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'job_id' => $job->id,
                        ]);

                        // Don't rollback - allow job to complete even if auto-update fails
                        \Log::warning("verifyJob: Unit On Wall auto-update failed but job completion will continue. Service Job: {$job->job_number}");
                    }
                }
                
                // AUTO-REMOVE/HIDE UNIT ON WALL when remove job is completed
                // "ketika remove job sudah selesai, unit on wall akan otomatis ter-hide/removed"
                // Trigger only if status changed from non-completed to completed/done_job
                if (in_array(strtolower($job->type), ['remove', 'remove_free', 'remove free']) && !in_array($oldStatus, ['completed', 'done_job']) && in_array($job->status, ['completed', 'done_job'])) {
                    try {
                        if (!isset($jobScheduleController)) {
                            $jobScheduleController = new \App\Http\Controllers\Operational\JobScheduleController();
                        }
                        if (!isset($reflection)) {
                            $reflection = new \ReflectionClass($jobScheduleController);
                        }
                        
                        $autoRemoveUnitOnWallMethod = $reflection->getMethod('autoRemoveUnitOnWall');
                        $autoRemoveUnitOnWallMethod->setAccessible(true);
                        
                        // Call autoRemoveUnitOnWall
                        $unitsRemoved = $autoRemoveUnitOnWallMethod->invoke($jobScheduleController, $job, $jobAdvice);
                    } catch (\Exception $e) {
                        \Log::error("verifyJob: ❌ Failed to trigger auto-remove Unit On Wall for remove job {$job->job_number}: " . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'job_id' => $job->id,
                            'job_type' => $job->type,
                            'old_status' => $oldStatus,
                            'new_status' => $job->status,
                        ]);
                        
                        // Don't rollback transaction - removal is not critical, just log the error
                        // Return warning instead of error to allow job completion
                        \Log::warning("verifyJob: Unit On Wall removal failed but job completion will continue. Remove Job: {$job->job_number}");
                    }
                }
            }
            
            // AUTO-GENERATE INVOICE
            // Trigger auto-generate invoice if all jobs in billing period are completed
            if (in_array($job->status, ['completed', 'done_job']) && $jobAdvice) {
                try {
                    $contractId = $jobAdvice->contract_id;
                    if ($contractId) {
                        $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
                        $invoiceService->attemptAutoInvoiceForContract($contractId);
                    }
                } catch (\Exception $e) {
                    \Log::error("Mobile API: Failed to trigger auto invoice for job {$job->job_number}: " . $e->getMessage());
                }
            }
            
            \DB::commit();
            
            $message = $cannotCompleteAllRooms 
                ? 'Pekerjaan berhasil diverifikasi. Material Return otomatis dibuat untuk ruangan yang belum selesai.'
                : 'Pekerjaan berhasil diverifikasi';
            
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'job_schedule_id' => $job->id,
                    'job_report_id' => $jobReport->id,
                    'job_status' => $job->status,
                    'cannot_complete_all_rooms' => $cannotCompleteAllRooms,
                ]
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('verifyJob: ❌ Failed to verify job', [
                'job_schedule_id' => $jobScheduleId ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => [
                    'has_photos' => $request->hasFile('photos'),
                    'has_pic_photo' => $request->hasFile('pic_photo'),
                    'has_signature' => !empty($request->signature),
                    'pic_name' => $request->pic_name ?? null,
                    'cannot_complete_all_rooms' => $request->cannot_complete_all_rooms ?? false,
                ],
            ]);
            
            // Provide more user-friendly error message
            $errorMessage = 'Gagal menyimpan verifikasi pekerjaan';
            if (strpos($e->getMessage(), 'Gagal menyimpan data verifikasi') !== false) {
                $errorMessage = $e->getMessage(); // Use the specific error message
            } elseif (strpos($e->getMessage(), 'database') !== false || strpos($e->getMessage(), 'SQL') !== false) {
                $errorMessage = 'Gagal menyimpan verifikasi: Terjadi kesalahan pada database. Silakan coba lagi atau hubungi admin.';
            } elseif (strpos($e->getMessage(), 'file') !== false || strpos($e->getMessage(), 'photo') !== false) {
                $errorMessage = 'Gagal menyimpan verifikasi: Terjadi kesalahan saat menyimpan foto. Pastikan foto tidak terlalu besar atau format tidak didukung.';
            } else {
                $errorMessage = 'Gagal menyimpan verifikasi: ' . $e->getMessage();
            }
            
            return response()->json([
                'status' => 'error',
                'message' => $errorMessage,
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Get liquid level text
     */
    private function getLiquidLevelText($level)
    {
        if ($level == 0) return 'No liquid';
        if ($level == 1) return '>25%';
        if ($level == 2) return '>10%';
        if ($level == 3) return '<=10%';
        return $level . '%';
    }
    
    /**
     * Get fan level text
     */
    private function getFanLevelText($level)
    {
        if ($level == 0) return 'Off';
        return 'Level ' . $level;
    }
    
    /**
     * Check if job is favorite for current user
     */
    private function isFavorite($jobScheduleId, $userId)
    {
        if (!isset($this->favoriteLookup[$userId])) {
            $this->favoriteLookup[$userId] = [];
        }

        if (array_key_exists($jobScheduleId, $this->favoriteLookup[$userId])) {
            return $this->favoriteLookup[$userId][$jobScheduleId];
        }

        $isFavorite = JobFavorite::where('user_id', $userId)
            ->where('job_schedule_id', $jobScheduleId)
            ->exists();

        $this->favoriteLookup[$userId][$jobScheduleId] = $isFavorite;

        return $isFavorite;
    }

    private function primeFavoriteLookup(array $jobIds, int $userId): void
    {
        if (empty($jobIds)) {
            $this->favoriteLookup[$userId] = [];
            return;
        }

        $this->favoriteLookup[$userId] = JobFavorite::where('user_id', $userId)
            ->whereIn('job_schedule_id', array_values(array_unique($jobIds)))
            ->pluck('job_schedule_id')
            ->mapWithKeys(fn ($jobId) => [$jobId => true])
            ->toArray();
    }

    private function getUserTeamIds(int $userId): array
    {
        return Cache::remember("mobile:user-team-ids:{$userId}:v1", now()->addMinutes(5), function () use ($userId) {
            return DB::table('teams')
                ->where('team_head_id', $userId)
                ->pluck('id')
                ->merge(
                    DB::table('team_members')
                        ->where('user_id', $userId)
                        ->pluck('team_id')
                )
                ->unique()
                ->values()
                ->toArray();
        });
    }

    private function getLocationName(string $table, $id): string
    {
        if (!$id) {
            return '';
        }

        if (isset($this->locationNameLookup[$table][$id])) {
            return $this->locationNameLookup[$table][$id];
        }

        $name = (string) DB::table($table)
            ->where('id', $id)
            ->value('name');

        $this->locationNameLookup[$table][$id] = $name;

        return $name;
    }
    
    /**
     * Get job type label in Indonesian
     */
    private function getJobTypeLabel($type)
    {
        $labels = [
            'install' => 'Pemasangan',
            'install_free' => 'Pemasangan',
            'install free' => 'Pemasangan',
            'service' => 'Servis',
            'service_first' => 'Servis',
            'service_routine' => 'Servis',
            'servis' => 'Servis',
            'remove' => 'Pembongkaran',
            'remove_free' => 'Pembongkaran',
            'remove free' => 'Pembongkaran',
            'maintenance' => 'Pemeliharaan',
            'extra' => 'Extra',
            'complain' => 'Komplain',
            'change' => 'Ganti Unit',
            'change_unit' => 'Ganti Unit',
            'change_rental' => 'Ganti Rental',
            'ganti_unit' => 'Ganti Unit',
            'check' => 'Check',
        ];
        
        return $labels[strtolower($type)] ?? ucfirst($type);
    }

    /**
     * Get job status label in Indonesian
     */
    private function getJobStatusLabel($status)
    {
        $statusTexts = [
            'new_job' => 'Pekerjaan Baru',
            'scheduled' => 'Terjadwal',
            'assign_team' => 'Tim Ditunjuk',
            'assign_material' => 'Material Ditunjuk',
            'barang_dipersiapkan' => 'Material Dalam Persiapan',
            'barang_siap_diambil' => 'Siap Diambil',
            'barang_diambil' => 'Siap Dikerjakan', // Label indicates technician has goods and is ready to work
            'teknisi_tiba_dilokasi' => 'Tiba di Lokasi',
            'in_progress' => 'Sedang Dikerjakan',
            'teknisi_sedang_pengerjaan' => 'Sedang Dikerjakan',
            'teknisi_selesai_pengerjaan' => 'Selesai Dikerjakan',
            'done_job' => 'Selesai',
            'completed' => 'Selesai',
            'selesai' => 'Selesai',
            'suspend' => 'Ditangguhkan',
            'dpf' => 'Selesai (Force)',
            'cancelled' => 'Dibatalkan',
            'pending' => 'Menunggu',
        ];

        return $statusTexts[strtolower($status)] ?? ucfirst(str_replace('_', ' ', $status));
    }
    
    /**
     * Save team location when arrived at location
     */
    public function arrivedAtLocation(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'device_info' => 'nullable|string',
        ]);
        
        $job = JobSchedule::findOrFail($id);

        if ($job->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
            ], 423);
        }
        
        // IR-CSR Dependency Check
        $dependencyCheck = $this->checkJobDependency($job);
        if ($dependencyCheck['is_blocked']) {
            return response()->json([
                'status' => 'error',
                'message' => $dependencyCheck['message']
            ], 403);
        }
        
        // Get user's team
        $userTeamId = DB::table('team_members')
            ->where('user_id', Auth::id())
            ->value('team_id');
        
        // Save location to job_team_locations
        $location = \App\Models\JobTeamLocation::create([
            'job_schedule_id' => $job->id,
            'user_id' => Auth::id(),
            'team_id' => $userTeamId,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'device_info' => $request->device_info,
            'action' => 'arrived',
            'recorded_at' => now()
        ]);
        
        // Update job status to 'teknisi_tiba_dilokasi' when technician arrives at location
        // Status: barang_diambil → teknisi_tiba_dilokasi (setelah cek material, sebelum mulai pekerjaan)
        // Only update if status is before work started (barang_diambil or earlier) OR meninggalkan_lokasi
        $allowedStatuses = ['barang_diambil', 'barang_dipersiapkan', 'assign_material', 'assign_team', 'scheduled', 'new_job', 'meninggalkan_lokasi'];
        $arrivalJobs = collect([$job]);

        if ($job->job_number) {
            $arrivalJobs = JobSchedule::where('job_number', $job->job_number)
                ->where('job_advice_id', $job->job_advice_id)
                ->where('type', $job->type)
                ->get();
        }

        foreach ($arrivalJobs as $arrivalJob) {
            if (in_array($arrivalJob->status, $allowedStatuses, true)) {
                $arrivalJob->update([
                    'status' => 'teknisi_tiba_dilokasi',
                    'updated_by' => Auth::id()
                ]);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Lokasi berhasil dicatat',
            'data' => [
                'id' => $location->id,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'recorded_at' => $location->recorded_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }
    
    /**
     * Swap Serial Number for a job (Replacement Unit)
     * Used in service jobs when technician needs to replace a faulty unit on wall
     */
    public function swapSerialNumber(Request $request, $id)
    {
        $request->validate([
            'old_serial_number' => 'required|string',
            'new_serial_number' => 'required|string',
            'room_id' => 'required'
        ]);

        $job = JobSchedule::findOrFail($id);
        if ($job->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikerjakan ulang dari aplikasi teknisi.'
            ], 423);
        }

        $oldSn = $request->old_serial_number;
        $newSn = $request->new_serial_number;
        $roomId = $request->room_id;

        if (trim(strtoupper($oldSn)) === trim(strtoupper($newSn))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Serial Number baru tidak boleh sama dengan Serial Number lama.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $uow = \App\Models\UnitOnWall::where('customer_id', $job->jobAdvice->customer_id)
                ->where('building_id', $job->building_id)
                ->where(function($q) use ($oldSn) {
                    $q->where('serial_number', $oldSn)
                      ->orWhereHas('serialNumber', function($sq) use ($oldSn) {
                          $sq->where('serial_number', $oldSn);
                      });
                })
                ->first();

            if (!$uow) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Unit dengan SN {$oldSn} tidak ditemukan di lokasi ini."
                ], 404);
            }

            // 2. Validate and Get New Serial Number
            $newSnModel = \App\Models\SerialNumber::where('serial_number', $newSn)->first();
            if (!$newSnModel) {
                 return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number baru {$newSn} tidak terdaftar di sistem."
                ], 404);
            }

            // Check if new SN is already in use
            if ($newSnModel && $newSnModel->status === 'in_use') {
                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$newSn} sudah terpasang di tempat lain."
                ], 400);
            }

            // Also check UnitOnWall specifically
            if ($newSnModel) {
                $alreadyOnWall = \App\Models\UnitOnWall::where('serial_number_id', $newSnModel->id)
                    ->where('status', 'active')
                    ->exists();
                if ($alreadyOnWall) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Serial Number {$newSn} sudah terdaftar secara aktif di Unit On Wall lokasi lain."
                    ], 400);
                }
            }

            // 3. Update Old Serial Number Status
            $oldSnModel = \App\Models\SerialNumber::where('serial_number', $oldSn)->first();
            if ($oldSnModel) {
                $oldSnModel->update([
                    'status' => 'on_hand_remove',
                    'location_type' => 'technician',
                    'location_id' => Auth::id()
                ]);
            }

            // 4. Update New Serial Number Status
            $newSnModel->update([
                'status' => 'in_use',
                'location_type' => 'customer',
                'location_id' => $job->jobAdvice->customer_id
            ]);

            // 5. Update UnitOnWall
            $uow->update([
                'serial_number_id' => $newSnModel->id,
                'serial_number' => $newSn,
                'last_service_date' => now(),
                'updated_by' => Auth::id()
            ]);

            // 6. Record History
            \App\Models\UnitOnWallHistory::create([
                'unit_on_wall_id' => $uow->id,
                'action' => 'swap',
                'action_date' => now(),
                'serial_number_before' => $oldSn,
                'serial_number_after' => $newSn,
                'performed_by' => Auth::id(),
                'job_schedule_id' => $job->id,
                'notes' => 'Penggantian unit via aplikasi teknisi'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengganti unit.',
                'data' => [
                    'old_sn' => $oldSn,
                    'new_sn' => $newSn
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Ganti Unit gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate serial number against job schedule materials
     * For service jobs (CSR), also check unit on wall if not found in materials
     */
    public function validateSerialNumber(Request $request, $id)
    {
        $request->validate([
            'serial_number' => 'required|string',
            'room_name' => 'nullable|string', // Optional: room yang dipilih saat mulai pekerjaan
        ]);
        
        $job = JobSchedule::with(['building', 'jobAdvice.customer'])->findOrFail($id);
        // Normalize serial number: uppercase and trim (SN disimpan dalam UPPERCASE di database)
        $serialNumberInput = trim(strtoupper($request->serial_number));
        $selectedRoomName = $request->room_name ? trim($request->room_name) : null;
        $source = 'materials'; // Default source
        $snRoomName = null; // Room name dari SN yang ditemukan
        
        // STEP 1: PRIORITY - Check in inventory_issuing_items first (SN yang sudah terdaftar saat verifikasi material)
        // Ini adalah SN yang sudah di-scan dan terverifikasi saat verifikasi material via apps
        
        // Get Material Issues for this job (with items for room_name lookup)
        $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($job) {
            $q->where('job_schedule_id', $job->id);
        })->with('items')->get();
        
        $materialIssueNumbers = $materialIssues->pluck('issue_number')->toArray();
        
        // Get Inventory Issuings that reference these Material Issues
        // Web Alignment: Only from verified/processed issuings (matching JobScheduleController::show)
        $inventoryIssuings = \App\Models\InventoryIssuing::whereIn('reference_no', $materialIssueNumbers)
            ->whereIn('status', ['processed', 'received', 'sent'])
            ->get();
        
        $inventoryIssuingIds = $inventoryIssuings->pluck('id')->toArray();
        
        // Now get inventory issuing items with the serial number
        $serialNumber = null;
        if (!empty($inventoryIssuingIds)) {
            // Get all inventory issuing items with serial numbers
            $inventoryIssuingItems = \App\Models\InventoryIssuingItem::whereIn('inventory_issuing_id', $inventoryIssuingIds)
                ->whereNotNull('serial_number_id')
                ->with(['serialNumber.masterProduct.productType', 'serialNumber.warehouse', 'product', 'inventoryIssuing'])
                ->get();
            
            // Find the matching SN (case-insensitive, trim whitespace)
            $serialNumberInputNormalized = trim(strtoupper($serialNumberInput));
            $inventoryIssuingItem = $inventoryIssuingItems->first(function($item) use ($serialNumberInputNormalized) {
                if (!$item->serialNumber) {
                    return false;
                }
                $itemSnNormalized = trim(strtoupper($item->serialNumber->serial_number ?? ''));
                return $itemSnNormalized === $serialNumberInputNormalized;
            });
            
            if ($inventoryIssuingItem && $inventoryIssuingItem->serialNumber) {
                $serialNumber = $inventoryIssuingItem->serialNumber;
                $serialNumber->load(['masterProduct.productType', 'warehouse']);
                
                // MANDATORY FIX: Strict Checks for Install Jobs
                if (in_array(strtolower($job->type), ['install', 'install_free', 'install free'])) {
                    $activeUnitOnWallForThisJob = \App\Models\UnitOnWall::where('serial_number_id', $serialNumber->id)
                        ->where('status', 'active')
                        ->when($job->jobAdvice?->customer_id, fn ($query) => $query->where('customer_id', $job->jobAdvice->customer_id))
                        ->when($job->building_id, fn ($query) => $query->where('building_id', $job->building_id))
                        ->when($selectedRoomName, function ($query) use ($selectedRoomName) {
                            $query->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim($selectedRoomName))]);
                        })
                        ->first();

                    // Check SN Status
                    if ($serialNumber->status === 'in_use' && !$activeUnitOnWallForThisJob) {
                         return response()->json([
                            'status' => 'error',
                            'message' => "Serial Number {$serialNumberInput} sudah terdaftar di Unit On Wall (In Use). Tidak dapat digunakan kembali."
                        ], 400);
                    }

                    if (in_array($serialNumber->status, ['broken', 'retired', 'damaged'])) {
                         return response()->json([
                            'status' => 'error',
                            'message' => "Serial Number {$serialNumberInput} dalam kondisi Rusak/Retired. Tidak dapat dipasang."
                        ], 400);
                    }

                    // Check Warehouse Alignment (MOM: Prevent scanning SN from different warehouse/branch)
                    $issuingWarehouseId = $inventoryIssuingItem->inventoryIssuing->warehouse_id;
                    if ($serialNumber->warehouse_id != $issuingWarehouseId) {
                         $correctWarehouse = \App\Models\Warehouse::find($issuingWarehouseId)->name ?? 'Tujuan';
                         return response()->json([
                            'status' => 'error',
                            'message' => "Serial Number {$serialNumberInput} berasal dari warehouse lain. SN ini harus dari warehouse {$correctWarehouse}."
                        ], 400);
                    }

                    // Explicit UnitOnWall Check (Extra Safety)
                    $existsOnWall = \App\Models\UnitOnWall::where('serial_number_id', $serialNumber->id)
                        ->where('status', 'active')
                        ->exists();
                    if ($existsOnWall && !$activeUnitOnWallForThisJob) {
                         return response()->json([
                            'status' => 'error',
                            'message' => "Serial Number {$serialNumberInput} sudah terpasang di lokasi customer (Unit On Wall). Hubungi admin jika ini kesalahan."
                        ], 400);
                    }
                }

                $source = 'verified_materials'; // SN dari verified materials
                
                // Extract room_name using the new columns in InventoryIssuingItem
                $snRoomName = $inventoryIssuingItem->room_name;
                
                // If not found in columns, fallback to the old lookup logic
                if (!$snRoomName) {
                    $inventoryIssuing = $inventoryIssuingItem->inventoryIssuing;
                    if ($inventoryIssuing && $inventoryIssuing->reference_no) {
                        $materialIssue = $materialIssues->firstWhere('issue_number', $inventoryIssuing->reference_no);
                        if ($materialIssue) {
                            // Priority 1: If we have multiple rooms, find item matching selected room name
                            if ($selectedRoomName) {
                                $materialIssueItem = $materialIssue->items
                                    ->where('product_id', $serialNumber->master_product_id)
                                    ->filter(function($m) use ($selectedRoomName) {
                                        return trim(strtolower($m->room_name)) === trim(strtolower($selectedRoomName));
                                    })->first();
                                
                                if ($materialIssueItem) {
                                    $snRoomName = trim($materialIssueItem->room_name);
                                }
                            }
                            
                            // Priority 2: Fallback to first item for this product
                            if (!$snRoomName) {
                                 $materialIssueItem = $materialIssue->items->firstWhere('product_id', $serialNumber->master_product_id);
                                 if ($materialIssueItem && $materialIssueItem->room_name) {
                                     $snRoomName = trim($materialIssueItem->room_name);
                                 }
                            }
                        }
                    }
                }
                
                // If room_name not found from MaterialIssueItem, try extract from notes
                if (!$snRoomName && $inventoryIssuingItem->notes) {
                    if (preg_match('/Room:\s*([^,]+)/i', $inventoryIssuingItem->notes, $matches)) {
                        $snRoomName = trim($matches[1]);
                    }
                }
                
                // Validate room if selected
                if ($selectedRoomName && $snRoomName) {
                    // Normalize room names for comparison (case-insensitive, trim)
                    $selectedRoomNormalized = trim(strtolower($selectedRoomName));
                    $snRoomNormalized = trim(strtolower($snRoomName));
                    
                    $roomMatches = $selectedRoomNormalized === $snRoomNormalized
                        || str_starts_with($selectedRoomNormalized, $snRoomNormalized . ' ')
                        || str_starts_with($snRoomNormalized, $selectedRoomNormalized . ' ');

                    if (!$roomMatches) {
                        \Log::warning("❌ Room mismatch for serial number", [
                            'serial_number' => $serialNumberInput,
                            'selected_room' => $selectedRoomName,
                            'sn_room' => $snRoomName,
                        ]);
                        
                        return response()->json([
                            'status' => 'error',
                            'message' => "Serial Number {$serialNumberInput} terdaftar untuk Room: {$snRoomName}, bukan Room: {$selectedRoomName}",
                            'room_mismatch' => true,
                            'expected_room' => $snRoomName,
                            'selected_room' => $selectedRoomName,
                        ], 400);
                    }
                }
                
                // Log product type info
                $isUnit = $serialNumber->masterProduct && $serialNumber->masterProduct->productType && $serialNumber->masterProduct->productType->is_unit;
                
                $inventoryIssuingItemMatched = $inventoryIssuingItem;
            } else {
                \Log::warning("Serial number {$serialNumberInput} NOT FOUND in verified materials", [
                    'total_items_checked' => $inventoryIssuingItems->count(),
                    'available_sns' => $inventoryIssuingItems->pluck('serialNumber.serial_number')->filter()->values()->toArray(),
                ]);
            }
        } else {
            \Log::warning("No inventory issuings found for job {$job->id}. Material issue numbers searched: " . implode(', ', $materialIssueNumbers));
        }
        
        if (!$serialNumber) {
        }
        
        // STEP 2: [DISABLED Strict Enforcement] 
        // Previously, this block searched for ANY serial number with matching Product ID globally.
        // This caused issues where units from other warehouses (e.g. Bandung) were accepted for Jakarta jobs.
        // We now enforce STRICT validation: SN must be in Verified Materials (Step 1) or Unit On Wall (Step 3).
        
        /* 
        if (!$serialNumber) {
             // ... Global search logic removed ...
        }
        */
        
        if (!$serialNumber) {
        }
        
        // STRICT VALIDATION: For install/install_free jobs, SN MUST be in verified materials
        // Install jobs cannot fallback to unit_on_wall - they are installing NEW units
        if (!$serialNumber && in_array(strtolower($job->type), ['install', 'install_free', 'install free'], true)) {
            \Log::warning("❌ Serial number not found in verified materials for INSTALL job", [
                'serial_number' => $serialNumberInput,
                'job_id' => $job->id,
                'job_number' => $job->job_number,
                'job_type' => $job->type,
                'material_issue_numbers' => $materialIssueNumbers,
                'inventory_issuing_ids' => $inventoryIssuingIds,
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Serial number tidak terdaftar di material job ini. Untuk job INSTALL, SN harus sudah terverifikasi saat verifikasi material di gudang.',
                'expected_serial_numbers' => isset($inventoryIssuingItems)
                    ? $inventoryIssuingItems->pluck('serialNumber.serial_number')->filter()->values()->toArray()
                    : [],
            ], 404);
        }
        
        // STEP 2: For remove job, directly check unit on wall (no material verification needed)
        // Remove job takes units from Unit On Wall, not from inventory issuing
        if (in_array(strtolower($job->type), ['remove', 'remove_free', 'remove free'])) {
            // Web Alignment: Load rooms from JobAdvice to match the "Serial Number" tab filter
            $jobAdvice = $job->jobAdvice;
            $roomIds = [];
            if ($jobAdvice) {
                // Ensure rooms relationship is loaded
                if (!$jobAdvice->relationLoaded('rooms')) {
                    $jobAdvice->load(['rooms.contractRoom', 'rooms.quotationRoom']);
                }
                $roomIds = $jobAdvice->rooms->map(function($jr) {
                    if ($jr->room_id) return $jr->room_id;
                    if ($jr->contractRoom) return $jr->contractRoom->room_id;
                    if ($jr->quotationRoom) return $jr->quotationRoom->room_id;
                    return null;
                })->filter()->unique()->toArray();
            }
            
            // SECURITY: Check unit on wall with strict validation
            // Must match: serial number, status active/removed (if done), customer, building, and ROOMS
            $unitOnWallQuery = \App\Models\UnitOnWall::where(function($q) use ($serialNumberInput) {
                    $q->whereRaw('UPPER(TRIM(serial_number)) = ?', [strtoupper(trim($serialNumberInput))])
                      ->orWhereHas('serialNumber', function($sq) use ($serialNumberInput) {
                          $sq->whereRaw('UPPER(TRIM(serial_number)) = ?', [strtoupper(trim($serialNumberInput))]);
                      });
                })
                ->with(['product.productType', 'building', 'room', 'customer', 'serialNumber']);
            
            // Web Alignment: Status logic
            if (in_array(strtolower($job->status), ['completed', 'done_job', 'done job'])) {
                $unitOnWallQuery->whereIn('status', ['active', 'removed']);
            } else {
                $unitOnWallQuery->where('status', 'active');
            }

            // Web Alignment: Room filter
            if (!empty($roomIds)) {
                $unitOnWallQuery->whereIn('room_id', $roomIds);
            }

            // SECURITY CHECK 1: Must match customer
            if ($jobAdvice && $jobAdvice->customer_id) {
                $unitOnWallQuery->where('customer_id', $jobAdvice->customer_id);
            } else {
                \Log::warning("Cannot validate serial number from unit on wall: Customer ID not found for remove job {$job->id}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer information tidak ditemukan. Tidak dapat memvalidasi serial number dari unit on wall.'
                ], 400);
            }
            
            // SECURITY CHECK 2: Must match building
            if ($job->building_id) {
                $unitOnWallQuery->where('building_id', $job->building_id);
            }
            
            $unitOnWall = $unitOnWallQuery->first();
            
            if ($unitOnWall) {
                // Double-check customer match (extra safety)
                if ($job->jobAdvice && $unitOnWall->customer_id != $job->jobAdvice->customer_id) {
                    \Log::warning("Security: Serial number {$serialNumberInput} found but customer mismatch", [
                        'unit_customer_id' => $unitOnWall->customer_id,
                        'job_customer_id' => $job->jobAdvice->customer_id,
                    ]);
                    $unitOnWall = null;
                }
            }
            
            if ($unitOnWall) {
                $serialNumberId = $unitOnWall->serialNumber ? $unitOnWall->serialNumber->id : null;
                $unitRoomName = $unitOnWall->room->room_name ?? $unitOnWall->room_name ?? null;

                if ($selectedRoomName && $unitRoomName && trim(strtolower($selectedRoomName)) !== trim(strtolower($unitRoomName))) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Serial Number {$serialNumberInput} terpasang di Room: {$unitRoomName}, bukan Room: {$selectedRoomName}",
                        'room_mismatch' => true,
                        'expected_room' => $unitRoomName,
                        'selected_room' => $selectedRoomName,
                    ], 400);
                }
                
                // Return success response with unit on wall data
                return response()->json([
                    'status' => 'success',
                    'message' => 'Serial number valid (ditemukan di unit on wall)',
                    'source' => 'unit_on_wall', // Indicate source
                    'data' => [
                        'id' => $serialNumberId,
                        'serial_number' => $unitOnWall->serial_number ?? ($unitOnWall->serialNumber->serial_number ?? $serialNumberInput),
                        'product_id' => $unitOnWall->product_id,
                        'product_name' => $unitOnWall->product->name ?? $unitOnWall->product_name ?? '-',
                        'product_code' => $unitOnWall->product->sku ?? '-', // Add SKU
                        'kode' => $unitOnWall->product->sku ?? '-', // Add Alias
                        'product_type' => $unitOnWall->product->productType->name ?? '-',
                        'warehouse' => '-', // Unit on wall doesn't have warehouse
                        'status' => $unitOnWall->status,
                        'unit_on_wall_id' => $unitOnWall->id,
                        'location' => [
                            'building' => $unitOnWall->building->nama_gedung ?? $unitOnWall->building_name ?? '-',
                            'room' => $unitOnWall->room->room_name ?? $unitOnWall->room_name ?? '-',
                        ],
                    ]
                ]);
            } else {
                \Log::warning("Serial number not found in unit on wall for remove job", [
                    'job_id' => $job->id,
                    'serial_number' => $serialNumberInput,
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Serial number tidak ditemukan di unit on wall untuk remove job ini'
                ], 404);
            }
        }
        
        // STEP 3: If not found in materials, for SERVICE jobs ONLY (including Change Rental), also check unit on wall
        // Only Service/Change Rental jobs can use existing unit_on_wall SNs. Install jobs MUST use verified materials.
        if (!$serialNumber && $this->isServiceLikeJob($job)) {
            // Web Alignment: Even for fallback, we must stay within the job's registered rooms
            $jobAdvice = $job->jobAdvice;
            $roomIds = [];
            if ($jobAdvice) {
                if (!$jobAdvice->relationLoaded('rooms')) {
                    $jobAdvice->load(['rooms.contractRoom', 'rooms.quotationRoom']);
                }
                $roomIds = $jobAdvice->rooms->map(function($jr) {
                    if ($jr->room_id) return $jr->room_id;
                    if ($jr->contractRoom) return $jr->contractRoom->room_id;
                    if ($jr->quotationRoom) return $jr->quotationRoom->room_id;
                    return null;
                })->filter()->unique()->toArray();
            }
            
            // SECURITY: Check unit on wall with strict validation
            // Must match: serial number, status active, customer, building, and ROOMS
            $unitOnWallQuery = \App\Models\UnitOnWall::where(function($q) use ($serialNumberInput) {
                    $q->whereRaw('UPPER(TRIM(serial_number)) = ?', [strtoupper(trim($serialNumberInput))])
                      ->orWhereHas('serialNumber', function($sq) use ($serialNumberInput) {
                          $sq->whereRaw('UPPER(TRIM(serial_number)) = ?', [strtoupper(trim($serialNumberInput))]);
                      });
                })
                ->where('status', 'active') 
                ->with(['product.productType', 'building', 'room', 'customer', 'serialNumber']);

            // Web Alignment: Room filter
            if (!empty($roomIds)) {
                $unitOnWallQuery->whereIn('room_id', $roomIds);
            }
            
            // SECURITY CHECK 1: Must match customer (most important - prevent cross-customer access)
            if ($job->jobAdvice && $job->jobAdvice->customer_id) {
                $unitOnWallQuery->where('customer_id', $job->jobAdvice->customer_id);
            } else {
                \Log::warning("Cannot validate serial number from unit on wall: Customer ID not found for job {$job->id}");
                // Don't proceed if customer is not known - security risk
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer information tidak ditemukan. Tidak dapat memvalidasi serial number dari unit on wall.'
                ], 400);
            }
            
            // SECURITY CHECK 2: Must match building (if job has building)
            if ($job->building_id) {
                $unitOnWallQuery->where('building_id', $job->building_id);
            }
            
            // SECURITY CHECK 3: Must match room if available (most specific)
            if ($job->room_id) {
                $unitOnWallQuery->where('room_id', $job->room_id);
            }
            
            $unitOnWall = $unitOnWallQuery->first();
            
            // Additional security validation after query
            if ($unitOnWall) {
                // Double-check customer match (extra safety)
                if ($job->jobAdvice && $unitOnWall->customer_id != $job->jobAdvice->customer_id) {
                    \Log::warning("Security: Serial number {$serialNumberInput} found but customer mismatch", [
                        'unit_customer_id' => $unitOnWall->customer_id,
                        'job_customer_id' => $job->jobAdvice->customer_id,
                    ]);
                    $unitOnWall = null; // Reject if customer doesn't match
                }
            }
            
            if ($unitOnWall) {
                $unitRoomName = $unitOnWall->room->room_name ?? $unitOnWall->room_name ?? null;

                if ($selectedRoomName && $unitRoomName && trim(strtolower($selectedRoomName)) !== trim(strtolower($unitRoomName))) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Serial Number {$serialNumberInput} terpasang di Room: {$unitRoomName}, bukan Room: {$selectedRoomName}",
                        'room_mismatch' => true,
                        'expected_room' => $unitRoomName,
                        'selected_room' => $selectedRoomName,
                    ], 400);
                }
                
                // Return success response with unit on wall data
                return response()->json([
                    'status' => 'success',
                    'message' => 'Serial number valid (ditemukan di unit on wall)',
                    'source' => 'unit_on_wall', // Indicate source
                    'data' => [
                        'serial_number' => $unitOnWall->serial_number,
                        'product_id' => $unitOnWall->product_id,
                        'product_name' => $unitOnWall->product->name ?? $unitOnWall->product_name ?? '-',
                        'product_type' => $unitOnWall->product->productType->name ?? '-',
                        'warehouse' => '-', // Unit on wall doesn't have warehouse
                        'status' => $unitOnWall->status,
                        'unit_on_wall_id' => $unitOnWall->id,
                        'location' => [
                            'building' => $unitOnWall->building->nama_gedung ?? $unitOnWall->building_name ?? '-',
                            'room' => $unitOnWall->room->room_name ?? $unitOnWall->room_name ?? '-',
                        ],
                    ]
                ]);
            } else {
                \Log::warning("Serial number not found in unit on wall either", [
                    'job_id' => $job->id,
                    'serial_number' => $serialNumberInput,
                ]);
            }
        }
        
        // If found in materials, return success
        if ($serialNumber) {
            $isUnit = $serialNumber->masterProduct && $serialNumber->masterProduct->productType && $serialNumber->masterProduct->productType->is_unit;
            
            // Get room_name if available (from earlier extraction or from material_issue_item)
            $responseRoomName = $snRoomName ?? null;
            if (!$responseRoomName && isset($inventoryIssuingItemMatched)) {
                // Try extract from notes
                if ($inventoryIssuingItemMatched->notes && preg_match('/Room:\s*([^\s,]+)/i', $inventoryIssuingItemMatched->notes, $matches)) {
                    $responseRoomName = trim($matches[1]);
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Serial number valid' . ($responseRoomName ? " (Room: {$responseRoomName})" : ''),
                'source' => $source, // Use the detected source (verified_materials, materials, or unit_on_wall)
                'data' => [
                    'id' => $serialNumber->id,
                    'serial_number' => $serialNumber->serial_number,
                    'product_id' => $serialNumber->master_product_id,
                    'product_name' => $serialNumber->masterProduct->name ?? 'Unknown',
                    'product_type' => $serialNumber->masterProduct->productType->name ?? 'Unknown',
                    'product_code' => $serialNumber->masterProduct->sku ?? '-', // Add product code (SKU)
                    'kode' => $serialNumber->masterProduct->sku ?? '-', // Alias for convenience
                    'is_unit' => $isUnit,
                    'warehouse' => $serialNumber->warehouse->name ?? '-',
                    'status' => $serialNumber->status,
                    'room_name' => $responseRoomName, // Include room_name in response
                ]
            ]);
        }
        
        // Not found in either materials or unit on wall
        \Log::warning("❌ Serial number not found for job", [
            'serial_number' => $serialNumberInput,
            'job_id' => $job->id,
            'job_number' => $job->job_number,
            'job_type' => $job->type,
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Serial number tidak terdaftar untuk job ini. Pastikan SN sudah terverifikasi saat verifikasi material.'
        ], 404);
    }
    
    /**
     * Get detailed job information - returns same format as job list for consistency
     */
    public function getJobDetail(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No authenticated user'
            ], 401);
        }
        
        $job = JobSchedule::with([
            'jobAdvice.customer',
            'jobAdvice.customerContact',
            'jobAdvice.contract.quotation.survey',
            'jobAdvice.contract.billingGroup',
            'jobAdvice.rooms.contractRoom.room',
            'jobAdvice.rooms.quotationRoom.room',
            'building',
            'room',
            'jobAssignSchedules.team'
        ])->findOrFail($id);
        
        if (!$job->jobAdvice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job advice not found'
            ], 404);
        }

        if ($job->status === 'undone') {
            return response()->json([
                'status' => 'error',
                'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dibuka ulang dari aplikasi teknisi.'
            ], 423);
        }
        
        $jobAssign = $job->jobAssignSchedules->where('status', '!=', 'cancelled')->sortByDesc('id')->first();
        
        // Get all rooms for this job advice
        $allRooms = $job->jobAdvice->rooms ?? collect();
        $jobRoomIds = [$job->room_id];

        // FIX: If this job has a specific room_id, use ONLY that room.
        // Previously this merged all sibling job room_ids (e.g. 2 jobs × 2 rooms = 4 rooms shown).
        if ($job->room_id) {
            // Already set: $jobRoomIds = [$job->room_id] above — keep it, don't merge siblings
            // Nothing extra needed, $jobRoomIds already has only this job's room
        }
        // If no room_id: use sibling jobs' rooms (old behaviour for legacy jobs without room_id)
        elseif ($job->job_number) {
            $jobRoomIds = \App\Models\JobSchedule::where('job_number', $job->job_number)
                ->where('job_advice_id', $job->job_advice_id)
                ->pluck('room_id')
                ->filter()
                ->unique()
                ->toArray();
        } 
        // Fallback: If no job_number, use reference context
        elseif ($job->job_advice_id) {
            $jobRoomIds = \App\Models\JobSchedule::where('job_advice_id', $job->job_advice_id)
                ->where('type', $job->type)
                ->where('building_id', $job->building_id)
                ->whereDate('schedule_date', $job->schedule_date)
                ->pluck('room_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        if ($job->job_number) {
            $siblingJobIds = \App\Models\JobSchedule::where('job_number', $job->job_number)
                ->where('job_advice_id', $job->job_advice_id)
                ->where('type', $job->type)
                ->pluck('id')
                ->toArray();

            $jobRoomIds = array_merge(
                $jobRoomIds,
                \App\Models\JobSchedule::whereIn('id', $siblingJobIds)->pluck('room_id')->filter()->toArray(),
                \App\Models\JobScheduleRoom::whereIn('job_schedule_id', $siblingJobIds)->pluck('room_id')->filter()->toArray()
            );
            $jobRoomIds = array_values(array_unique(array_filter($jobRoomIds)));
        }

        // Filter the rooms that belong to this actual job schedule group
        if (!empty($jobRoomIds)) {
            $targetRooms = $allRooms->filter(function($r) use ($jobRoomIds) {
                $roomId = null;
                if ($r->contractRoom) $roomId = $r->contractRoom->room_id;
                elseif ($r->quotationRoom) $roomId = $r->quotationRoom->room_id;
                
                return in_array($roomId, $jobRoomIds);
            });
            
            // STRICT: If job has a specific room_id and filter is empty, do NOT show all rooms.
            // Showing all rooms was the root cause of 'Total: 4 Ruangan' instead of 2.
            if ($targetRooms->isEmpty()) {
                if ($job->room_id) {
                    \Log::warning("getJobDetail: room_id={$job->room_id} not matched in advice rooms. Returning empty to prevent over-display.");
                    $targetRooms = collect([]);
                } else {
                    \Log::warning("getJobDetail: No room_id on job, falling back to all advice rooms (legacy mode).");
                    $targetRooms = $allRooms;
                }
            }
        } else {
            // No room IDs resolved: strict mode for jobs with room_id
            $targetRooms = $job->room_id ? collect([]) : $allRooms;
        }
        
        // Calculate total and completed rooms for THIS SPECIFIC job
        $totalRooms = $targetRooms->count();
        $completedRooms = $targetRooms->filter(function ($room) use ($job) {
            $masterRoomId = $room->contractRoom?->room_id ?? $room->quotationRoom?->room_id ?? null;
            $roomJob = $job;

            if ($job->job_number && $masterRoomId) {
                $roomJob = \App\Models\JobSchedule::where('job_number', $job->job_number)
                    ->where('job_advice_id', $job->job_advice_id)
                    ->where('type', $job->type)
                    ->where('room_id', $masterRoomId)
                    ->first() ?? $job;
            }

            return $this->isJobScheduleRoomCompleted($roomJob, $room, $masterRoomId);
        })->count();
        
        // Use mapJobToArray to get consistent data format (same as job list)
        // This ensures all fields are populated correctly
        $jobData = $this->mapJobToArray($job, $user, $jobAssign, null, $totalRooms, $completedRooms);
        
        return response()->json([
            'status' => 'success',
            'data' => $jobData
        ]);
    }
}

