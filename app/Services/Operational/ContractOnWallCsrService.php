<?php

namespace App\Services\Operational;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\Contract;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use App\Models\UnitOnWall;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractOnWallCsrService
{
    private const ACTIVE_UNIT_STATUSES = ['active', 'installed', 'on_wall', 'on wall', 'onwall'];

    private const ACTIVE_JOB_STATUSES_TO_IGNORE = ['cancelled', 'canceled', 'undone'];

    public function __construct(private readonly DocumentNumberService $documentNumberService) {}

    public function createForContract(Contract $contract, ?int $userId = null, string $trigger = 'contract_activation'): int
    {
        $contract->loadMissing([
            'customer',
            'quotation',
            'contractRooms.room.building.district',
            'contractRooms.room.building.subdistrict',
            'contractRentals.masterRental.serviceFrequency',
            'contractRentals.masterRental.rentalDetails.masterProduct.productType',
        ]);

        if (! $contract->customer_id || ! $contract->contract_number) {
            return 0;
        }

        $eligibleRoomGroups = $this->eligibleRoomGroups($contract);
        if ($eligibleRoomGroups->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($contract, $eligibleRoomGroups, $userId, $trigger) {
            $serviceDate = $this->resolveFirstServiceDate($contract);
            $jobAdvice = $this->createJobAdvice($contract, $serviceDate, $userId, $trigger);
            $createdCount = 0;

            foreach ($eligibleRoomGroups as $roomGroup) {
                $serviceRoomGroup = $this->serviceEligibleRoomGroup($roomGroup);
                if ($serviceRoomGroup === null) {
                    continue;
                }

                $jobAdviceRooms = $this->createJobAdviceRooms($jobAdvice, $serviceRoomGroup, $userId);

                if ($jobAdviceRooms->isEmpty()) {
                    continue;
                }

                $jobSchedule = $this->createFirstCsrSchedule($jobAdvice, $serviceRoomGroup, $jobAdviceRooms, $serviceDate, $userId);
                $serviceRoom = $this->createJobScheduleRoom($jobSchedule, $jobAdviceRooms, $serviceRoomGroup, $userId);

                $isPrimary = true;
                foreach ($jobAdviceRooms as $jobAdviceRoom) {
                    JobScheduleRoomRental::create([
                        'job_schedule_room_id' => $serviceRoom->id,
                        'job_advice_room_id' => $jobAdviceRoom->id,
                        'is_primary' => $isPrimary,
                    ]);
                    $isPrimary = false;
                }

                $jobAdviceRooms->each->update([
                    'service_job_schedule_id' => $jobSchedule->id,
                    'rental_has_service' => true,
                ]);

                $createdCount++;
            }

            if ($createdCount === 0) {
                $jobAdvice->delete();

                return 0;
            }

            $this->cancelPendingRemoveFreeJobs($contract, $eligibleRoomGroups, $trigger);
            $generatedCheckCount = $this->generateUnitOnlyChecksForCompletedInstalls($contract);

            Log::info('Created first CSR schedules for active Unit On Wall rooms during contract activation.', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'job_advice_id' => $jobAdvice->id,
                'created_count' => $createdCount,
                'generated_unit_only_checks' => $generatedCheckCount,
                'trigger' => $trigger,
            ]);

            return $createdCount;
        });
    }

    private function eligibleRoomGroups(Contract $contract): Collection
    {
        return $contract->contractRooms
            ->filter(fn ($contractRoom) => $contractRoom->room !== null)
            ->map(function ($contractRoom) use ($contract) {
                $unitOnWall = $this->activeUnitOnWallForContractRoom($contract, $contractRoom);
                if (! $unitOnWall || $this->activeServiceScheduleExists($contract, $contractRoom)) {
                    return null;
                }

                $rentals = $contract->contractRentals
                    ->where('room_id', $contractRoom->room_id)
                    ->values();

                if ($rentals->isEmpty()) {
                    $roomName = $contractRoom->room?->room_name;
                    $rentals = $contract->contractRentals
                        ->filter(fn ($rental) => $roomName && trim((string) $rental->rental_alias) === trim((string) $roomName))
                        ->values();
                }

                if ($rentals->isEmpty()) {
                    Log::warning('Skipping on-wall CSR creation because contract room has no rentals.', [
                        'contract_id' => $contract->id,
                        'contract_room_id' => $contractRoom->id,
                        'room_id' => $contractRoom->room_id,
                    ]);

                    return null;
                }

                return [
                    'contract_room' => $contractRoom,
                    'unit_on_wall' => $unitOnWall,
                    'rentals' => $rentals,
                ];
            })
            ->filter()
            ->values();
    }

    private function serviceEligibleRoomGroup(array $roomGroup): ?array
    {
        $serviceRentals = $roomGroup['rentals']
            ->filter(fn ($contractRental) => $this->contractRentalHasService($contractRental))
            ->values();

        if ($serviceRentals->isEmpty()) {
            return null;
        }

        $roomGroup['rentals'] = $serviceRentals;

        return $roomGroup;
    }

    private function contractRentalHasService($contractRental): bool
    {
        return strtolower((string) ($contractRental->masterRental?->rental_type ?? 'unit_refill')) !== 'unit_only';
    }

    private function activeUnitOnWallForContractRoom(Contract $contract, $contractRoom): ?UnitOnWall
    {
        $room = $contractRoom->room;
        $roomName = trim((string) ($room->room_name ?? ''));
        $normalizedRoomName = mb_strtolower(preg_replace('/\s+/', ' ', $roomName));
        $buildingId = $room->building_id ?? null;

        return UnitOnWall::query()
            ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
            ->where('customer_id', $contract->customer_id)
            ->where(function ($query) use ($contractRoom, $buildingId, $normalizedRoomName) {
                if ($contractRoom->room_id) {
                    $query->where('room_id', $contractRoom->room_id);
                }

                if ($buildingId && $normalizedRoomName !== '') {
                    $query->orWhere(function ($roomQuery) use ($buildingId, $normalizedRoomName) {
                        $roomQuery->where('building_id', $buildingId)
                            ->whereRaw('LOWER(TRIM(room_name)) = ?', [$normalizedRoomName]);
                    });
                }
            })
            ->get()
            ->first(fn (UnitOnWall $unitOnWall) => $this->hasCompletedInstallSourceForContract($contract, $contractRoom, $unitOnWall));
    }

    private function hasCompletedInstallSourceForContract(Contract $contract, $contractRoom, UnitOnWall $unitOnWall): bool
    {
        $sourceContracts = $this->unitOnWallInstallSourceContracts($contract);
        $sourceContractIds = $sourceContracts->pluck('id')->filter()->unique()->values();
        $sourceQuotationIds = $sourceContracts->pluck('quotation_id')->filter()->unique()->values();
        $sourceQuotationNumbers = $sourceContracts
            ->map(fn (Contract $sourceContract) => $sourceContract->quotation?->quotation_number)
            ->filter()
            ->unique()
            ->values();

        if ($sourceContractIds->isEmpty() && $sourceQuotationIds->isEmpty() && $sourceQuotationNumbers->isEmpty()) {
            return false;
        }

        $room = $contractRoom->room;
        $roomName = trim((string) ($room->room_name ?? $unitOnWall->room_name ?? ''));
        $normalizedRoomName = mb_strtolower(preg_replace('/\s+/', ' ', $roomName));
        $buildingId = $room->building_id ?? $unitOnWall->building_id ?? null;

        return JobSchedule::query()
            ->whereIn(DB::raw("LOWER(REPLACE(COALESCE(type, ''), ' ', '_'))"), ['install', 'install_free', 'if'])
            ->whereIn('status', ['completed', 'done_job', 'selesai'])
            ->where(function ($sourceQuery) use ($sourceContractIds, $sourceQuotationIds, $sourceQuotationNumbers) {
                if ($sourceQuotationNumbers->isNotEmpty()) {
                    $sourceQuery->whereIn('quotation_number', $sourceQuotationNumbers->all());
                }

                if ($sourceQuotationIds->isNotEmpty()) {
                    $sourceQuery->orWhereHas('jobAdvice', function ($jobAdviceQuery) use ($sourceQuotationIds) {
                        $jobAdviceQuery->whereIn('quotation_id', $sourceQuotationIds->all());
                    });
                }

                if ($sourceContractIds->isNotEmpty()) {
                    $sourceQuery->orWhereHas('jobAdvice', function ($jobAdviceQuery) use ($sourceContractIds) {
                        $jobAdviceQuery->whereIn('contract_id', $sourceContractIds->all());
                    });
                }
            })
            ->where(function ($roomQuery) use ($contractRoom, $unitOnWall, $buildingId, $normalizedRoomName) {
                if ($contractRoom->room_id) {
                    $roomQuery->where('room_id', $contractRoom->room_id);
                }

                if ($unitOnWall->room_id && $unitOnWall->room_id !== $contractRoom->room_id) {
                    $roomQuery->orWhere('room_id', $unitOnWall->room_id);
                }

                if ($buildingId && $normalizedRoomName !== '') {
                    $roomQuery->orWhere(function ($namedRoomQuery) use ($buildingId, $normalizedRoomName) {
                        $namedRoomQuery->where('building_id', $buildingId)
                            ->whereRaw('LOWER(TRIM(room_name)) = ?', [$normalizedRoomName]);
                    });
                }
            })
            ->exists();
    }

    private function unitOnWallInstallSourceContracts(Contract $contract): Collection
    {
        $sources = collect([$contract]);
        $quotation = $contract->quotation;

        if ($quotation?->quotation_type === 'renewal' && $quotation->existing_contract_id) {
            $oldContract = Contract::findRenewalSource($quotation->existing_contract_id);
            if ($oldContract) {
                $oldContract->loadMissing('quotation');
                $sources->push($oldContract);
            }
        }

        return $sources
            ->filter()
            ->each->loadMissing('quotation')
            ->unique('id')
            ->values();
    }

    private function activeServiceScheduleExists(Contract $contract, $contractRoom): bool
    {
        return JobSchedule::query()
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->whereNotIn('status', self::ACTIVE_JOB_STATUSES_TO_IGNORE)
            ->where(function ($contractQuery) use ($contract) {
                $contractQuery->where('contract_number', $contract->contract_number)
                    ->orWhereHas('jobAdvice', function ($jobAdviceQuery) use ($contract) {
                        $jobAdviceQuery->where('contract_id', $contract->id);
                    });
            })
            ->where(function ($query) use ($contractRoom) {
                if ($contractRoom->room_id) {
                    $query->where('room_id', $contractRoom->room_id);
                }

                $query->orWhereHas('jobScheduleRooms.jobAdviceRoom', function ($roomQuery) use ($contractRoom) {
                    $roomQuery->where('contract_room_id', $contractRoom->id);
                })->orWhereHas('jobScheduleRooms.rentals.jobAdviceRoom', function ($rentalQuery) use ($contractRoom) {
                    $rentalQuery->where('contract_room_id', $contractRoom->id);
                });
            })
            ->exists();
    }

    private function generateUnitOnlyChecksForCompletedInstalls(Contract $contract): int
    {
        if (! $contract->quotation_id) {
            return 0;
        }

        $jobAdvices = JobAdvice::query()
            ->with([
                'rooms.rentalProduct.serviceFrequency',
                'rooms.rentalProduct.rentalDetails.productCategory',
                'rooms.rentalProduct.rentalDetails.productType',
                'rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
                'rooms.rentalProduct.rentalDetails.masterProduct.productType',
                'rooms.rentalProduct.rentalDetails.allowedProducts.productCategory',
                'rooms.rentalProduct.rentalDetails.allowedProducts.productType',
                'rooms.contractRoom.room',
                'rooms.quotationRoom.room',
            ])
            ->where('quotation_id', $contract->quotation_id)
            ->where(function ($query) use ($contract) {
                $query->whereNull('contract_id')
                    ->orWhere('contract_id', $contract->id);
            })
            ->whereRaw("LOWER(REPLACE(COALESCE(type, ''), ' ', '_')) IN (?, ?)", ['install', 'install_free'])
            ->whereHas('rooms.rentalProduct', function ($query) {
                $query->whereRaw("LOWER(REPLACE(COALESCE(rental_type, ''), '-', '_')) = ?", ['unit_only']);
            })
            ->get();

        if ($jobAdvices->isEmpty()) {
            return 0;
        }

        $controller = app(JobScheduleController::class);
        $createdCount = 0;

        foreach ($jobAdvices as $jobAdvice) {
            $jobAdvice->setRelation('contract', $contract);

            $completedInstalls = JobSchedule::query()
                ->where('job_advice_id', $jobAdvice->id)
                ->whereIn(DB::raw("LOWER(REPLACE(COALESCE(type, ''), ' ', '_'))"), ['install', 'install_free', 'if'])
                ->whereIn('status', ['completed', 'done_job', 'selesai'])
                ->orderBy('schedule_date')
                ->orderBy('id')
                ->get();

            foreach ($completedInstalls as $completedInstall) {
                if (! $completedInstall->contract_number) {
                    $completedInstall->contract_number = $contract->contract_number;
                }

                $createdCount += count($controller->generateUnitOnlyCheckSchedulesAfterInstall($completedInstall, $jobAdvice));
            }
        }

        return $createdCount;
    }

    private function createJobAdvice(Contract $contract, Carbon $serviceDate, ?int $userId, string $trigger): JobAdvice
    {
        return JobAdvice::create([
            'job_advice_number' => $this->documentNumberService->generate(
                'job_advice',
                null,
                null,
                $contract->id,
                $contract->quotation_id
            ),
            'type' => 'service',
            'company_name' => $contract->customer?->name,
            'contract_id' => $contract->id,
            'quotation_id' => $contract->quotation_id,
            'customer_id' => $contract->customer_id,
            'request_by' => $userId,
            'submitted_by' => $userId,
            'submitted_at' => now(),
            'expected_date' => $serviceDate,
            'first_service_date' => $serviceDate,
            'status' => 'approved',
            'date_approval' => now(),
            'approved_by' => $userId,
            'with_invoicing' => false,
            'with_materials' => true,
            'notes' => "Auto-generated first CSR for active Unit On Wall rooms when contract was {$trigger}.",
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function createJobAdviceRooms(JobAdvice $jobAdvice, array $roomGroup, ?int $userId): Collection
    {
        $contractRoom = $roomGroup['contract_room'];
        $unitOnWall = $roomGroup['unit_on_wall'];
        $room = $contractRoom->room;

        return $roomGroup['rentals']->map(function ($contractRental) use ($jobAdvice, $contractRoom, $unitOnWall, $room, $userId) {
            $rental = $contractRental->masterRental;

            $payload = [
                'job_advice_id' => $jobAdvice->id,
                'contract_room_id' => $contractRoom->id,
                'contract_rental_id' => $contractRental->id,
                'rental_product_id' => $contractRental->master_rental_id,
                'room_name' => $room?->room_name,
                'rental_name' => $contractRental->rental_alias ?: ($rental?->rental_name ?? 'Rental'),
                'quantity' => (int) max(1, ceil((float) ($contractRental->quantity ?? 0) + (float) ($contractRental->qty_free ?? 0))),
                'rental_specification_ml' => $this->rentalSpecificationMl($rental),
                'rental_has_installation' => false,
                'rental_has_service' => true,
                'status' => JobAdviceRoom::STATUS_PENDING,
                'unit_already_installed' => true,
                'existing_unit_on_wall_id' => $unitOnWall->id,
                'notes' => 'Auto CSR: unit already active on wall.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('job_advice_rooms', 'qty_free')) {
                $payload['qty_free'] = $contractRental->qty_free ?? 0;
            }

            return JobAdviceRoom::create($payload);
        })->values();
    }

    private function createFirstCsrSchedule(JobAdvice $jobAdvice, array $roomGroup, Collection $jobAdviceRooms, Carbon $serviceDate, ?int $userId): JobSchedule
    {
        $contractRoom = $roomGroup['contract_room'];
        $room = $contractRoom->room;
        $building = $room?->building;
        $firstRental = $jobAdviceRooms->first()?->rentalProduct;
        $serviceFrequencyObj = $firstRental?->serviceFrequency;
        $hasServiceMaterials = $jobAdviceRooms->contains(function (JobAdviceRoom $jobAdviceRoom) {
            return strtolower((string) ($jobAdviceRoom->rentalProduct?->rental_type ?? 'unit_refill')) !== 'unit_only';
        });

        return JobSchedule::create([
            'job_number' => null,
            'type' => 'service_first',
            'status' => 'scheduled',
            'job_advice_id' => $jobAdvice->id,
            'building_id' => $building?->id,
            'building_name' => $building?->nama_gedung ?? $building?->name,
            'room_id' => $contractRoom->room_id,
            'room_name' => $room?->room_name,
            'company_name' => $jobAdvice->company_name,
            'contract_number' => $jobAdvice->contract?->contract_number,
            'quotation_number' => $jobAdvice->contract?->quotation?->quotation_number,
            'schedule_date' => $serviceDate->toDateString(),
            'expected_date' => $serviceDate->toDateString(),
            'period' => 1,
            'service_frequency' => $serviceFrequencyObj?->frequency_times_per_month ?? $serviceFrequencyObj?->frequency_months,
            'service_period_type' => $serviceFrequencyObj?->name ?? 'monthly',
            'internal_notes' => "Auto-generated first CSR from contract activation: {$jobAdvice->job_advice_number} | Room: {$room?->room_name}",
            'reference_number' => $jobAdvice->job_advice_number,
            'postal_code' => $building?->kode_pos ?? $building?->postal_code,
            'district' => $building?->district?->name,
            'sub_district' => $building?->subdistrict?->name,
            'material_checked' => ! $hasServiceMaterials,
            'material_checked_at' => ! $hasServiceMaterials ? now() : null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function createJobScheduleRoom(JobSchedule $jobSchedule, Collection $jobAdviceRooms, array $roomGroup, ?int $userId): JobScheduleRoom
    {
        $contractRoom = $roomGroup['contract_room'];
        $firstJobAdviceRoom = $jobAdviceRooms->first();

        return JobScheduleRoom::create([
            'job_schedule_id' => $jobSchedule->id,
            'job_advice_room_id' => $firstJobAdviceRoom->id,
            'room_name' => $firstJobAdviceRoom->room_name,
            'room_id' => $contractRoom->room_id,
            'status' => JobScheduleRoom::STATUS_PENDING,
            'material_return_status' => JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
            'notes' => 'Auto CSR for active Unit On Wall rentals: '.$jobAdviceRooms->count(),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function cancelPendingRemoveFreeJobs(Contract $contract, Collection $roomGroups, string $trigger): int
    {
        $quotationNumber = $contract->quotation?->quotation_number;
        $roomIds = $roomGroups
            ->map(fn ($roomGroup) => $roomGroup['contract_room']->room_id)
            ->filter()
            ->unique()
            ->values();

        if (! $quotationNumber && $roomIds->isEmpty()) {
            return 0;
        }

        $query = JobSchedule::whereIn('type', ['remove_free', 'remove free'])
            ->whereIn('status', [
                'scheduled',
                'new_job',
                'assign_team',
                'assign_material',
                'barang_dipersiapkan',
                'barang_siap_diambil',
            ])
            ->where(function ($removeQuery) use ($quotationNumber, $roomIds) {
                if ($quotationNumber) {
                    $removeQuery->where('quotation_number', $quotationNumber);
                }

                if ($roomIds->isNotEmpty()) {
                    $removeQuery->orWhereIn('room_id', $roomIds);
                }
            });

        $jobs = $query->get();
        foreach ($jobs as $job) {
            $job->update([
                'status' => 'cancelled',
                'internal_notes' => trim(($job->internal_notes ? $job->internal_notes."\n" : '').
                    "Auto-cancelled because contract {$contract->contract_number} generated first CSR for active Unit On Wall ({$trigger})."),
            ]);
        }

        return $jobs->count();
    }

    private function resolveFirstServiceDate(Contract $contract): Carbon
    {
        return Carbon::parse(
            $contract->first_service_date
                ?? $contract->install_date
                ?? $contract->start_date
                ?? now()
        );
    }

    private function rentalSpecificationMl($rental): float
    {
        if (! $rental) {
            return 0;
        }

        return (float) $rental->rentalDetails->sum(function ($detail) {
            $packageSize = $detail->masterProduct?->packagingSize?->size_ml
                ?? $detail->masterProduct?->packagingSize?->volume_ml
                ?? $detail->masterProduct?->packagingSize?->size
                ?? 0;

            return ((float) ($detail->quantity ?? 0)) * ((float) $packageSize);
        });
    }
}
