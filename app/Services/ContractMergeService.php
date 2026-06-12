<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractMerge;
use App\Models\ContractRoom;
use App\Models\ContractRental;
use App\Models\ContractTermination;
use App\Models\JobSchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ContractMergeService
{
    /**
     * Validasi bahwa contracts bisa di-merge.
     * Mengembalikan array ['valid' => bool, 'errors' => []]
     */
    public function validate(array $sourceContractIds, int $customerId): array
    {
        $errors = [];

        if (empty($sourceContractIds)) {
            return ['valid' => true, 'errors' => []];
        }

        $sourceContracts = Contract::whereIn('id', $sourceContractIds)->get();

        // Cek semua contract ditemukan
        if ($sourceContracts->count() !== count($sourceContractIds)) {
            $errors[] = 'Beberapa contract tidak ditemukan.';
        }

        foreach ($sourceContracts as $contract) {
            // Cek customer harus sama
            if ($contract->customer_id !== $customerId) {
                $errors[] = "Contract {$contract->contract_number} tidak milik customer yang sama.";
            }

            // Cek status contract — harus aktif atau bisa di-merge
            $mergableStatuses = ['active', 'approved', 'signed'];
            if (!in_array($contract->contract_status, $mergableStatuses)) {
                $errors[] = "Contract {$contract->contract_number} berstatus '{$contract->contract_status}', tidak bisa di-merge.";
            }

            // Cek apakah contract sudah pernah di-merge (jadi source di merge lain)
            $alreadyMerged = ContractMerge::where('source_contract_id', $contract->id)->exists();
            if ($alreadyMerged) {
                $errors[] = "Contract {$contract->contract_number} sudah pernah di-merge sebelumnya.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Preview data yang akan di-copy sebelum eksekusi merge.
     * Digunakan untuk konfirmasi dari user di wizard.
     */
    public function preview(array $sourceContractIds): array
    {
        $sourceContracts = Contract::with([
            'contractRooms.room.building',
            'contractRentals.masterRental',
            'customer',
        ])->whereIn('id', $sourceContractIds)->get();

        $totalRooms = 0;
        $totalRentals = 0;
        $totalJobsToCancel = 0;
        $contractPreviews = [];

        foreach ($sourceContracts as $contract) {
            $roomCount = $contract->contractRooms->count();
            $rentalCount = $contract->contractRentals->count();

            // Hitung job schedules outstanding yang akan di-cancel
            $jobsToCancel = JobSchedule::whereHas('jobAdvice', function ($q) use ($contract) {
                    $q->where('contract_id', $contract->id);
                })
                ->whereIn('status', ['scheduled', 'in_progress', 'pending_material', 'pending', 'assigned'])
                ->count();

            $totalRooms += $roomCount;
            $totalRentals += $rentalCount;
            $totalJobsToCancel += $jobsToCancel;

            $contractPreviews[] = [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'customer_name' => $contract->customer->name ?? '-',
                'contract_status' => $contract->contract_status,
                'rooms_count' => $roomCount,
                'rentals_count' => $rentalCount,
                'jobs_to_cancel' => $jobsToCancel,
                'end_date' => $contract->end_date ? $contract->end_date->format('d/m/Y') : '-',
            ];
        }

        return [
            'source_contracts' => $contractPreviews,
            'totals' => [
                'rooms' => $totalRooms,
                'rentals' => $totalRentals,
                'jobs_to_cancel' => $totalJobsToCancel,
            ],
        ];
    }

    /**
     * Copy semua contract_rooms dan contract_rentals dari source contracts ke contract baru.
     * Room di-copy persis, dengan tambahan source_contract_id untuk tracking.
     */
    public function copyRoomsAndRentals(Contract $newContract, array $sourceContracts): array
    {
        $stats = ['rooms' => 0, 'rentals' => 0];

        foreach ($sourceContracts as $sourceContract) {
            // Copy contract rooms
            foreach ($sourceContract->contractRooms as $sourceRoom) {
                ContractRoom::create([
                    'contract_id' => $newContract->id,
                    'room_id' => $sourceRoom->room_id,
                    'billing_group_id' => null, // Reset billing group untuk contract baru
                    'source_contract_id' => $sourceContract->id,
                    'source_contract_room_id' => $sourceRoom->id,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
                $stats['rooms']++;
            }

            // Copy contract rentals (persis sesuai sumber)
            foreach ($sourceContract->contractRentals as $sourceRental) {
                ContractRental::create([
                    'contract_id' => $newContract->id,
                    'master_rental_id' => $sourceRental->master_rental_id,
                    'rental_alias' => $sourceRental->rental_alias,
                    'room_id' => $sourceRental->room_id,
                    'quantity' => $sourceRental->quantity,
                    'qty_free' => $sourceRental->qty_free ?? 0,
                    'unit_price' => $sourceRental->unit_price,
                    'total_price' => $sourceRental->total_price,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
                $stats['rentals']++;
            }
        }

        Log::info("ContractMergeService: Copied rooms/rentals for Contract #{$newContract->id}", $stats);
        return $stats;
    }

    /**
     * Terminate source contracts dengan status term-renew.
     * Record muncul otomatis di modul Contract Termination.
     */
    public function terminateSourceContracts(array $sourceContracts, Contract $newContract): void
    {
        foreach ($sourceContracts as $sourceContract) {
            // Generate termination number
            $terminationNumber = $this->generateTerminationNumber();

            // Buat record di contract_terminations (status: term-renew, auto_generated: true)
            ContractTermination::create([
                'termination_number' => $terminationNumber,
                'contract_id' => $sourceContract->id,
                'new_contract_id' => $newContract->id,
                'customer_id' => $sourceContract->customer_id,
                'reason' => 'Contract Merge',
                'termination_reason' => "Digabungkan ke Contract #{$newContract->contract_number}",
                'termination_description' => "Contract ini di-terminate karena digabungkan ke contract baru: {$newContract->contract_number}",
                'status' => ContractTermination::STATUS_TERM_RENEW,
                'termination_date' => now()->toDateString(),
                'contract_end_date' => $sourceContract->end_date,
                'auto_generated' => true,
                'auto_termination' => true,
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Update contract_status menjadi term-renew
            $sourceContract->update([
                'contract_status' => 'term-renew',
            ]);

            Log::info("ContractMergeService: Contract #{$sourceContract->id} ({$sourceContract->contract_number}) terminated as term-renew → new contract #{$newContract->id}");
        }
    }

    /**
     * Auto-cancel job schedules outstanding dari source contracts.
     * Status yang di-cancel: scheduled, in_progress, pending_material, pending, assigned
     */
    public function cancelOutstandingJobSchedules(array $sourceContracts, Contract $newContract): int
    {
        $totalCancelled = 0;

        foreach ($sourceContracts as $sourceContract) {
            $cancelled = JobSchedule::whereHas('jobAdvice', function ($q) use ($sourceContract) {
                    $q->where('contract_id', $sourceContract->id);
                })
                ->whereIn('status', ['scheduled', 'in_progress', 'pending_material', 'pending', 'assigned'])
                ->update([
                    'status' => 'cancelled',
                    'notes' => DB::raw("CONCAT(IFNULL(notes, ''), ' [Auto-cancelled: Contract merged to {$newContract->contract_number}]')"),
                    'updated_at' => now(),
                ]);

            $totalCancelled += $cancelled;
            Log::info("ContractMergeService: Cancelled {$cancelled} job schedules from Contract #{$sourceContract->id}");
        }

        return $totalCancelled;
    }

    /**
     * Rekam audit trail merge ke tabel contract_merges.
     */
    public function recordAudit(Contract $newContract, array $sourceContracts, array $stats): void
    {
        foreach ($sourceContracts as $index => $sourceContract) {
            ContractMerge::create([
                'new_contract_id' => $newContract->id,
                'source_contract_id' => $sourceContract->id,
                'rooms_copied' => $stats['rooms_per_contract'][$sourceContract->id] ?? 0,
                'rentals_copied' => $stats['rentals_per_contract'][$sourceContract->id] ?? 0,
                'jobs_cancelled' => $stats['jobs_per_contract'][$sourceContract->id] ?? 0,
                'merged_by' => Auth::id(),
                'merged_at' => now(),
            ]);
        }
    }

    /**
     * Main entry point — eksekusi full merge dalam 1 DB transaction.
     *
     * @param Contract $newContract Contract baru yang sudah dibuat
     * @param array $sourceContractIds Array of contract IDs yang akan di-merge
     * @return array Stats hasil merge
     * @throws \Exception
     */
    public function execute(Contract $newContract, array $sourceContractIds): array
    {
        if (empty($sourceContractIds)) {
            return [
                'success' => true,
                'message' => 'Tidak ada contract yang di-merge.',
                'stats' => ['rooms' => 0, 'rentals' => 0, 'jobs_cancelled' => 0],
            ];
        }

        // Load source contracts dengan relasi yang diperlukan
        $sourceContracts = Contract::with([
            'contractRooms',
            'contractRentals',
        ])->whereIn('id', $sourceContractIds)->get()->all();

        // Validasi
        $validation = $this->validate($sourceContractIds, $newContract->customer_id);
        if (!$validation['valid']) {
            throw new \Exception('Validasi merge gagal: ' . implode(', ', $validation['errors']));
        }

        // Track stats per contract untuk audit
        $statsPerContract = [
            'rooms_per_contract' => [],
            'rentals_per_contract' => [],
            'jobs_per_contract' => [],
        ];

        foreach ($sourceContracts as $sc) {
            $statsPerContract['rooms_per_contract'][$sc->id] = $sc->contractRooms->count();
            $statsPerContract['rentals_per_contract'][$sc->id] = $sc->contractRentals->count();

            $jobCount = JobSchedule::whereHas('jobAdvice', fn($q) => $q->where('contract_id', $sc->id))
                ->whereIn('status', ['scheduled', 'in_progress', 'pending_material', 'pending', 'assigned'])
                ->count();
            $statsPerContract['jobs_per_contract'][$sc->id] = $jobCount;
        }

        // 1. Copy rooms & rentals
        $copyStats = $this->copyRoomsAndRentals($newContract, $sourceContracts);

        // 2. Cancel outstanding job schedules
        $totalCancelled = $this->cancelOutstandingJobSchedules($sourceContracts, $newContract);

        // 3. Terminate source contracts
        $this->terminateSourceContracts($sourceContracts, $newContract);

        // 4. Update new contract dengan merged_from_ids
        $newContract->update([
            'merged_from_ids' => $sourceContractIds,
        ]);

        // 5. Record audit trail
        $this->recordAudit($newContract, $sourceContracts, $statsPerContract);

        $summary = [
            'success' => true,
            'message' => count($sourceContracts) . ' contract berhasil di-merge.',
            'stats' => [
                'source_contracts_merged' => count($sourceContracts),
                'rooms_copied' => $copyStats['rooms'],
                'rentals_copied' => $copyStats['rentals'],
                'jobs_cancelled' => $totalCancelled,
            ],
        ];

        Log::info("ContractMergeService::execute() completed", [
            'new_contract_id' => $newContract->id,
            'new_contract_number' => $newContract->contract_number,
            'source_contract_ids' => $sourceContractIds,
            'stats' => $summary['stats'],
        ]);

        return $summary;
    }

    /**
     * Generate termination number untuk term-renew.
     * Format: TRM-RENEW-YYYYMMDD-XXXX
     */
    private function generateTerminationNumber(): string
    {
        $prefix = 'TRM-RENEW-' . date('Ymd');
        $count = ContractTermination::where('termination_number', 'LIKE', $prefix . '%')->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
