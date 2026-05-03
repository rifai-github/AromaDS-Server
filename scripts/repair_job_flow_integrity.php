<?php

use App\Models\Contract;
use App\Models\Finance\InvoiceDetail;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobAssignSchedule;
use App\Models\JobReport;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use App\Models\UnitOnWall;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);
$only = null;
$limit = null;
$fromDate = now()->startOfMonth()->toDateString();
$allHistory = in_array('--all-history', $argv, true);

foreach ($argv as $index => $arg) {
    if ($arg === '--only' && isset($argv[$index + 1])) {
        $only = $argv[$index + 1];
    } elseif (str_starts_with($arg, '--only=')) {
        $only = substr($arg, strlen('--only='));
    } elseif ($arg === '--limit' && isset($argv[$index + 1])) {
        $limit = (int) $argv[$index + 1];
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, strlen('--limit='));
    } elseif ($arg === '--from-date' && isset($argv[$index + 1])) {
        $fromDate = $argv[$index + 1];
    } elseif (str_starts_with($arg, '--from-date=')) {
        $fromDate = substr($arg, strlen('--from-date='));
    }
}

$actorId = User::query()->orderBy('id')->value('id');
$nowLabel = now()->format('Y-m-d H:i:s');
$prefix = $dryRun ? '[DRY-RUN]' : '[APPLY]';
$stats = [];
$notesPrefix = "[REPAIR {$nowLabel}]";

function shouldRun(?string $only, string $section): bool
{
    return $only === null || $only === $section;
}

function addStat(array &$stats, string $key, int $count = 1): void
{
    $stats[$key] = ($stats[$key] ?? 0) + $count;
}

function appendRepairNote(?string $notes, string $message): string
{
    global $notesPrefix;

    return trim(($notes ? $notes . "\n" : '') . "{$notesPrefix} {$message}");
}

function activeUnitStatuses(): array
{
    return ['active', 'installed', 'on_wall', 'on wall', 'onwall'];
}

function cancelableRemoveStatuses(): array
{
    return [
        'scheduled',
        'new_job',
        'pending',
        'assign_team',
        'assign_material',
        'barang_dipersiapkan',
        'barang_siap_diambil',
    ];
}

function completedJobStatuses(): array
{
    return ['completed', 'done_job', 'selesai'];
}

function jobAdviceRoomGroupKey(JobAdviceRoom $room): string
{
    if ($room->contract_room_id) {
        return 'contract_room:' . $room->contract_room_id;
    }

    $roomId = $room->contractRoom?->room_id ?? $room->room_id;
    if ($roomId) {
        return 'room:' . $roomId;
    }

    return 'name:' . strtolower(trim((string) $room->room_name));
}

function activeServiceScheduleExists(int $jobAdviceId, array $jobAdviceRoomIds): bool
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

function runRepair(string $section, callable $callback): void
{
    global $prefix, $only;

    if (!shouldRun($only, $section)) {
        return;
    }

    echo "\n{$prefix} {$section}\n";
    $callback();
}

echo "{$prefix} Repair job-flow integrity started.\n";
echo "Options: --dry-run, --only=<section>, --limit=N, --from-date=YYYY-MM-DD, --all-history\n";
echo "Date scope: " . ($allHistory ? 'all history' : "created/dated from {$fromDate}") . "\n";
echo "Sections: install-free-service-miscreated, approved-ja-without-schedules, incomplete-completed-jobs, false-completion-evidence, verification-photos-room-link, contract-onwall-services, cancel-continued-remove-free, orphan-remove-free, empty-invoices\n";

runRepair('install-free-service-miscreated', function () use ($dryRun, $limit, &$stats, $actorId, $fromDate, $allHistory) {
    $safeStatuses = [
        'scheduled',
        'new_job',
        'pending',
        'assign_team',
        'assign_material',
        'barang_dipersiapkan',
        'barang_siap_diambil',
    ];

    $query = JobSchedule::with(['jobAdvice.rooms.rentalProduct', 'jobScheduleRooms.jobAdviceRoom.rentalProduct'])
        ->whereIn('type', ['service', 'service_first', 'service_routine'])
        ->whereIn('status', $safeStatuses)
        ->whereHas('jobAdvice', function ($q) {
            $q->whereIn(DB::raw('LOWER(type)'), ['install_free', 'install free']);
        })
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate)
                ->orWhereDate('schedule_date', '>=', $fromDate)
                ->orWhereDate('expected_date', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $jobs = $query->get()->filter(function (JobSchedule $job) {
        $hasInstallFreeSibling = JobSchedule::where('job_advice_id', $job->job_advice_id)
            ->where('room_id', $job->room_id)
            ->whereIn('type', ['install_free', 'install free'])
            ->exists();

        if ($hasInstallFreeSibling) {
            return false;
        }

        $rentalTypes = $job->jobScheduleRooms
            ->map(fn ($room) => $room->jobAdviceRoom?->rentalProduct?->rental_type)
            ->filter()
            ->unique()
            ->values();

        if ($rentalTypes->isEmpty() && $job->jobAdvice) {
            $rentalTypes = $job->jobAdvice->rooms
                ->where('room_name', $job->room_name)
                ->map(fn ($room) => $room->rentalProduct?->rental_type)
                ->filter()
                ->unique()
                ->values();
        }

        return $rentalTypes->contains('unit_only');
    });

    echo "Found {$jobs->count()} Install Free unit-only job(s) wrongly created as service/CSR.\n";

    foreach ($jobs as $job) {
        echo "- Job #{$job->id} {$job->job_number} JA={$job->jobAdvice?->job_advice_number} room={$job->room_name}: {$job->type} => install_free.\n";
        addStat($stats, 'install_free_service_miscreated');

        if ($dryRun) {
            continue;
        }

        DB::transaction(function () use ($job, $actorId) {
            $job->update([
                'job_number' => null,
                'type' => 'install_free',
                'period' => null,
                'service_frequency' => null,
                'service_period_type' => 'monthly',
                'material_checked' => false,
                'material_checked_at' => null,
                'internal_notes' => appendRepairNote($job->internal_notes, 'Converted wrongly generated CSR/service from Install Free unit-only rental back to Install Free. Job number reset so assignment will generate IF prefix.'),
                'updated_by' => $actorId,
            ]);
        });
    }
});

runRepair('approved-ja-without-schedules', function () use ($dryRun, $limit, &$stats, $fromDate, $allHistory) {
    $query = JobAdvice::with(['rooms'])
        ->where('status', 'approved')
        ->whereDoesntHave('jobSchedules')
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate)
                ->orWhereDate('date_approval', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $jobAdvices = $query->get();
    echo "Found {$jobAdvices->count()} approved Job Advice record(s) without Job Schedule.\n";

    if ($jobAdvices->isEmpty()) {
        return;
    }

    $controller = new \App\Http\Controllers\Marketing\JobAdviceController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('createJobSchedulesFromJobAdvice');
    $method->setAccessible(true);

    foreach ($jobAdvices as $jobAdvice) {
        echo "- JA #{$jobAdvice->id} {$jobAdvice->job_advice_number} type={$jobAdvice->type} rooms={$jobAdvice->rooms->count()}: create missing schedules.\n";
        addStat($stats, 'approved_ja_without_schedules');

        if ($dryRun) {
            continue;
        }

        DB::transaction(function () use ($method, $controller, $jobAdvice) {
            $method->invoke($controller, $jobAdvice->fresh());
        });
    }
});

runRepair('incomplete-completed-jobs', function () use ($dryRun, $limit, &$stats, $actorId, $fromDate, $allHistory) {
    $query = JobSchedule::with(['jobScheduleRooms', 'jobAssignSchedules'])
        ->whereIn('status', completedJobStatuses())
        ->whereHas('jobScheduleRooms', function ($roomQuery) {
            $roomQuery->where('status', '!=', JobScheduleRoom::STATUS_COMPLETED);
        })
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('schedule_date', '>=', $fromDate)
                ->orWhereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $jobs = $query->get();
    echo "Found {$jobs->count()} completed job(s) with incomplete room(s).\n";

    foreach ($jobs as $job) {
        $hasActiveAssignment = $job->jobAssignSchedules
            ->where('status', '!=', 'cancelled')
            ->isNotEmpty();
        $targetStatus = $hasActiveAssignment ? 'in_progress' : 'new_job';
        $pendingRooms = $job->jobScheduleRooms
            ->where('status', '!=', JobScheduleRoom::STATUS_COMPLETED)
            ->pluck('room_name')
            ->implode(', ');

        echo "- Job #{$job->id} {$job->job_number} {$job->type}: {$job->status} => {$targetStatus}; pending_rooms={$pendingRooms}\n";
        addStat($stats, 'incomplete_completed_jobs');

        if ($dryRun) {
            continue;
        }

        DB::transaction(function () use ($job, $targetStatus, $pendingRooms, $actorId) {
            $job->update([
                'status' => $targetStatus,
                'completed_at' => null,
                'ba_date' => null,
                'ba_number' => null,
                'internal_notes' => appendRepairNote($job->internal_notes, "Reopened because job was completed while room(s) still pending: {$pendingRooms}."),
                'updated_by' => $actorId,
            ]);

            JobReport::where('job_schedule_id', $job->id)
                ->whereNotNull('completed_at')
                ->update(['completed_at' => null, 'updated_at' => now()]);
        });
    }
});

runRepair('false-completion-evidence', function () use ($dryRun, $limit, &$stats, $actorId, $fromDate, $allHistory) {
    $nonCompletedStatuses = [
        'cancelled',
        'meninggalkan_lokasi',
        'undone',
        'new_job',
        'scheduled',
        'pending',
        'assign_team',
        'assign_material',
    ];

    $query = JobSchedule::query()
        ->whereIn('status', $nonCompletedStatuses)
        ->where(function ($q) {
            $q->whereNotNull('completed_at')
                ->orWhereNotNull('ba_date')
                ->orWhereNotNull('ba_number')
                ->orWhereHas('jobReports', function ($reportQuery) {
                    $reportQuery->whereNotNull('completed_at');
                });
        })
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('schedule_date', '>=', $fromDate)
                ->orWhereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $jobs = $query->get();
    echo "Found {$jobs->count()} non-completed job(s) with completion evidence stamp.\n";

    foreach ($jobs as $job) {
        echo "- Job #{$job->id} {$job->job_number} status={$job->status}: clearing completed_at/BA stamp only, photos stay.\n";
        addStat($stats, 'false_completion_evidence');

        if ($dryRun) {
            continue;
        }

        DB::transaction(function () use ($job, $actorId) {
            $job->update([
                'completed_at' => null,
                'ba_date' => null,
                'ba_number' => null,
                'internal_notes' => appendRepairNote($job->internal_notes, "Cleared false completion/BA stamp while status is {$job->status}. Evidence photos/files were preserved."),
                'updated_by' => $actorId,
            ]);

            JobReport::where('job_schedule_id', $job->id)
                ->whereNotNull('completed_at')
                ->update(['completed_at' => null, 'updated_at' => now()]);
        });
    }
});

runRepair('verification-photos-room-link', function () use ($dryRun, $limit, &$stats, $fromDate, $allHistory) {
    $query = \App\Models\JobPhoto::with('jobSchedule')
        ->whereNull('job_schedule_room_id')
        ->whereIn('photo_type', ['PIC Photo', 'Digital Signature', 'Work Photo'])
        ->whereHas('jobSchedule.jobScheduleRooms', function ($roomQuery) {
            $roomQuery->where('status', JobScheduleRoom::STATUS_COMPLETED);
        })
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $photos = $query->get();
    echo "Found {$photos->count()} verification photo row(s) without room link.\n";

    foreach ($photos as $photo) {
        $room = JobScheduleRoom::where('job_schedule_id', $photo->job_schedule_id)
            ->where('status', JobScheduleRoom::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->first();

        if (!$room) {
            continue;
        }

        echo "- Photo #{$photo->id} job={$photo->jobSchedule?->job_number} type={$photo->photo_type}: link to room {$room->room_name} (JSR #{$room->id}).\n";
        addStat($stats, 'verification_photos_room_link');

        if ($dryRun) {
            continue;
        }

        $photo->update([
            'job_schedule_room_id' => $room->id,
            'updated_at' => now(),
        ]);
    }
});

runRepair('contract-onwall-services', function () use ($dryRun, $limit, &$stats, $actorId, $fromDate, $allHistory) {
    $jobAdviceQuery = JobAdvice::with([
            'rooms.contractRoom.room.building',
            'rooms.rentalProduct.rentalDetails.masterProduct.productType',
            'contract.quotation',
            'customer',
        ])
        ->whereNotNull('contract_id')
        ->whereIn(DB::raw('LOWER(type)'), ['install'])
        ->orderBy('id');

    if (!$allHistory) {
        $jobAdviceQuery->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate);
        });
    }

    if ($limit) {
        $jobAdviceQuery->limit($limit);
    }

    $jobAdvices = $jobAdviceQuery->get();
    $createdCount = 0;

    foreach ($jobAdvices as $jobAdvice) {
        $roomsByPhysicalRoom = $jobAdvice->rooms->groupBy(fn (JobAdviceRoom $room) => jobAdviceRoomGroupKey($room));

        foreach ($roomsByPhysicalRoom as $roomsInGroup) {
            $jaRoom = $roomsInGroup->first();
            $jobAdviceRoomIds = $roomsInGroup->pluck('id')->all();

            if ($roomsInGroup->contains(fn (JobAdviceRoom $room) => !empty($room->service_job_schedule_id))) {
                continue;
            }

            if (activeServiceScheduleExists((int) $jobAdvice->id, $jobAdviceRoomIds)) {
                continue;
            }

            $roomId = $jaRoom->contractRoom?->room_id ?? $jaRoom->room_id;
            if (!$roomId) {
                continue;
            }

            $hasUnitOnWall = UnitOnWall::where('room_id', $roomId)
                ->whereIn('status', activeUnitStatuses())
                ->whereNotNull('serial_number_id')
                ->exists();

            if (!$hasUnitOnWall) {
                continue;
            }

            $primaryRental = $jaRoom->rentalProduct ?: $jaRoom->contractRoom?->rentalProduct;
            $hasServiceMaterials = $roomsInGroup->contains(function (JobAdviceRoom $room) {
                $rental = $room->rentalProduct ?: $room->contractRoom?->rentalProduct;
                $rentalType = strtolower((string) ($rental?->rental_type ?? 'unit_refill'));

                return $rentalType !== 'unit_only';
            });
            $serviceType = 'service_first';
            $doesNotNeedMaterial = !$hasServiceMaterials;
            $building = $jaRoom->contractRoom?->room?->building;

            echo "- JA {$jobAdvice->job_advice_number} room={$jaRoom->room_name}: active Unit On Wall found, create one {$serviceType} period 1 for {$roomsInGroup->count()} rental row(s); skip duplicate IR.\n";
            addStat($stats, 'contract_onwall_services');

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($jobAdvice, $jaRoom, $roomsInGroup, $roomId, $primaryRental, $serviceType, $doesNotNeedMaterial, $building, $actorId, &$createdCount) {
                $schedule = JobSchedule::create([
                    'job_number' => null,
                    'type' => $serviceType,
                    'status' => 'scheduled',
                    'job_advice_id' => $jobAdvice->id,
                    'building_id' => $building?->id ?? $jaRoom->contractRoom?->building_id,
                    'building_name' => $building?->nama_gedung ?? $building?->name,
                    'room_id' => $roomId,
                    'room_name' => $jaRoom->room_name,
                    'company_name' => $jobAdvice->company_name,
                    'contract_number' => $jobAdvice->contract?->contract_number,
                    'quotation_number' => $jobAdvice->contract?->quotation?->quotation_number,
                    'schedule_date' => $jobAdvice->expected_date,
                    'expected_date' => $jobAdvice->expected_date,
                    'period' => 1,
                    'service_period_type' => $primaryRental?->serviceFrequency?->name ?? 'monthly',
                    'reference_number' => $jobAdvice->job_advice_number,
                    'internal_notes' => "Auto-repaired first {$serviceType} for contract room already installed from trial/Install Free. JA: {$jobAdvice->job_advice_number}",
                    'material_checked' => $doesNotNeedMaterial,
                    'material_checked_at' => $doesNotNeedMaterial ? now() : null,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $jobScheduleRoom = JobScheduleRoom::create([
                    'job_schedule_id' => $schedule->id,
                    'job_advice_room_id' => $jaRoom->id,
                    'room_name' => $jaRoom->room_name,
                    'room_id' => $roomId,
                    'status' => JobScheduleRoom::STATUS_PENDING,
                    'material_return_status' => JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                    'notes' => 'Auto-repaired first service for existing Unit On Wall.',
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                foreach ($roomsInGroup->values() as $index => $roomInGroup) {
                    JobScheduleRoomRental::create([
                        'job_schedule_room_id' => $jobScheduleRoom->id,
                        'job_advice_room_id' => $roomInGroup->id,
                        'is_primary' => $index === 0,
                    ]);

                    $roomInGroup->update([
                        'service_job_schedule_id' => $schedule->id,
                        'rental_has_service' => true,
                        'unit_already_installed' => true,
                        'updated_by' => $actorId,
                    ]);
                }

                $createdCount++;
            });
        }
    }

    echo "Created {$createdCount} service/check schedule(s) in apply mode.\n";
});

runRepair('cancel-continued-remove-free', function () use ($dryRun, $limit, &$stats, $actorId, $fromDate, $allHistory) {
    $query = JobSchedule::with(['jobAdvice', 'jobScheduleRooms'])
        ->whereIn(DB::raw('LOWER(type)'), ['remove_free', 'remove free'])
        ->whereIn('status', cancelableRemoveStatuses())
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('schedule_date', '>=', $fromDate)
                ->orWhereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $removeJobs = $query->get();
    echo "Scanning {$removeJobs->count()} active Remove Free job(s) for contract continuation.\n";

    foreach ($removeJobs as $removeJob) {
        $quotationNumber = $removeJob->quotation_number ?: $removeJob->jobAdvice?->quotation?->quotation_number;
        if (!$quotationNumber) {
            continue;
        }

        $contract = Contract::whereHas('quotation', function ($q) use ($quotationNumber) {
                $q->where('quotation_number', $quotationNumber);
            })
            ->whereIn('contract_status', ['active', 'draft'])
            ->latest('id')
            ->first();

        if (!$contract) {
            continue;
        }

        $roomIds = $removeJob->jobScheduleRooms->pluck('room_id')
            ->push($removeJob->room_id)
            ->filter()
            ->unique()
            ->values();

        if ($roomIds->isEmpty()) {
            continue;
        }

        $hasContractRoom = $contract->contractRooms()
            ->whereIn('room_id', $roomIds)
            ->exists();

        $hasActiveUnit = UnitOnWall::whereIn('room_id', $roomIds)
            ->whereIn('status', activeUnitStatuses())
            ->whereNotNull('serial_number_id')
            ->exists();

        if (!$hasContractRoom || !$hasActiveUnit) {
            continue;
        }

        echo "- RF #{$removeJob->id} {$removeJob->job_number}: {$removeJob->status} => cancelled; contract={$contract->contract_number}; rooms={$roomIds->implode(',')}\n";
        addStat($stats, 'cancel_continued_remove_free');

        if ($dryRun) {
            continue;
        }

        DB::transaction(function () use ($removeJob, $contract, $actorId) {
            $removeJob->update([
                'status' => 'cancelled',
                'completed_at' => null,
                'ba_date' => null,
                'ba_number' => null,
                'internal_notes' => appendRepairNote($removeJob->internal_notes, "Auto-cancelled because customer continued to contract {$contract->contract_number}; Unit remains on wall."),
                'updated_by' => $actorId,
            ]);

            JobAssignSchedule::where('job_schedule_id', $removeJob->id)
                ->where('status', '!=', 'cancelled')
                ->update(['status' => 'cancelled', 'updated_by' => $actorId, 'updated_at' => now()]);

            JobReport::where('job_schedule_id', $removeJob->id)
                ->whereNotNull('completed_at')
                ->update(['completed_at' => null, 'updated_at' => now()]);
        });
    }
});

runRepair('orphan-remove-free', function () use ($dryRun, $limit, &$stats, $actorId, $fromDate, $allHistory) {
    $query = JobSchedule::whereIn(DB::raw('LOWER(type)'), ['remove_free', 'remove free'])
        ->whereIn('status', cancelableRemoveStatuses())
        ->whereDoesntHave('jobAdvice.rooms', function ($roomQuery) {
            $roomQuery->whereColumn('job_advice_rooms.remove_job_schedule_id', 'job_schedules.id');
        })
        ->whereDoesntHave('jobAssignSchedules', function ($assignQuery) {
            $assignQuery->where('status', '!=', 'cancelled');
        })
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('schedule_date', '>=', $fromDate)
                ->orWhereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $jobs = $query->get();
    echo "Found {$jobs->count()} orphan/unlinked unassigned Remove Free job(s).\n";

    foreach ($jobs as $job) {
        echo "- RF #{$job->id} {$job->job_number}: {$job->status} => cancelled; not linked from any JA room and no active assignment.\n";
        addStat($stats, 'orphan_remove_free');

        if ($dryRun) {
            continue;
        }

        $job->update([
            'status' => 'cancelled',
            'completed_at' => null,
            'ba_date' => null,
            'ba_number' => null,
            'internal_notes' => appendRepairNote($job->internal_notes, 'Auto-cancelled orphan Remove Free: not linked from any Job Advice room and no active team assignment.'),
            'updated_by' => $actorId,
        ]);
    }
});

runRepair('empty-invoices', function () use ($dryRun, $limit, &$stats, $actorId, $fromDate, $allHistory) {
    $hasTotalInvoiceColumn = Schema::hasColumn('invoices', 'total_invoice');

    $query = \App\Models\Invoice::withCount(['invoiceDetails', 'invoiceRentalDetails'])
        ->where('invoice_status', '!=', \App\Models\Invoice::STATUS_CANCELLED)
        ->where(function ($q) use ($hasTotalInvoiceColumn) {
            $q->where('subtotal', '>', 0)
                ->orWhere('total_amount', '>', 0);

            if ($hasTotalInvoiceColumn) {
                $q->orWhere('total_invoice', '>', 0);
            }
        })
        ->having('invoice_details_count', '=', 0)
        ->having('invoice_rental_details_count', '=', 0)
        ->orderBy('id');

    if (!$allHistory) {
        $query->where(function ($dateQuery) use ($fromDate) {
            $dateQuery->whereDate('invoice_date', '>=', $fromDate)
                ->orWhereDate('created_at', '>=', $fromDate)
                ->orWhereDate('updated_at', '>=', $fromDate);
        });
    }

    if ($limit) {
        $query->limit($limit);
    }

    $invoices = $query->get();
    echo "Found {$invoices->count()} non-cancelled invoice(s) with amount but no detail rows.\n";

    foreach ($invoices as $invoice) {
        $amount = (float) ($invoice->subtotal ?: ($hasTotalInvoiceColumn ? $invoice->total_invoice : null) ?: $invoice->total_amount);
        if ($amount <= 0) {
            continue;
        }

        echo "- Invoice #{$invoice->id} {$invoice->invoice_number}: create fallback detail amount={$amount}.\n";
        addStat($stats, 'empty_invoices');

        if ($dryRun) {
            continue;
        }

        DB::transaction(function () use ($invoice, $amount, $actorId, $hasTotalInvoiceColumn) {
            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'description' => 'Tagihan layanan - detail dipulihkan otomatis dari subtotal invoice',
                'quantity' => 1,
                'unit_price' => $amount,
                'total_price' => $amount,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $taxAmount = $invoice->tax_obligation ? round($amount * 0.11, 2) : 0;
            $grandTotal = $amount + $taxAmount;
            $paidAmount = (float) ($invoice->paid_amount ?? 0);

            $updatePayload = [
                'subtotal' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $grandTotal,
                'outstanding' => max($grandTotal - $paidAmount, 0),
                'notes' => appendRepairNote($invoice->notes, 'Fallback invoice detail row created because invoice had amount but no detail rows.'),
                'updated_by' => $actorId,
            ];

            if ($hasTotalInvoiceColumn) {
                $updatePayload['total_invoice'] = $grandTotal;
            }

            $invoice->update($updatePayload);
        });
    }
});

echo "\n{$prefix} Summary\n";
if (empty($stats)) {
    echo "- No candidate rows found.\n";
} else {
    foreach ($stats as $key => $count) {
        echo "- {$key}: {$count}\n";
    }
}

echo $dryRun
    ? "\nDry-run selesai. Jalankan tanpa --dry-run untuk apply.\n"
    : "\nApply selesai.\n";
