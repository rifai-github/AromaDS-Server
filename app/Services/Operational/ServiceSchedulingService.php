<?php

namespace App\Services\Operational;

use App\Models\Contract;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\ServiceHistory;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServiceSchedulingService
{
    protected $documentNumberService;

    public function __construct(DocumentNumberService $documentNumberService)
    {
        $this->documentNumberService = $documentNumberService;
    }
    /**
     * Auto-generate service schedules based on contract frequency (Berdasarkan BRD)
     */
    public function generateServiceSchedulesForContract(int $contractId): array
    {
        try {
            DB::beginTransaction();

            $contract = Contract::with(['customer', 'buildings'])->findOrFail($contractId);
            
            if (!$contract->is_active) {
                throw new \Exception('Contract is not active');
            }

            $serviceSchedules = [];
            $startDate = Carbon::parse($contract->start_date);
            $endDate = Carbon::parse($contract->end_date);

            // Get service frequency from contract (default monthly if not specified)
            $serviceFrequency = $contract->service_frequency ?? 'monthly';
            $serviceType = $contract->service_type ?? 'maintenance';
            
            // Check if service_frequency is numeric (e.g., 3 for sebulan 3x service - mom2.md)
            $isNumericFrequency = is_numeric($serviceFrequency);
            
            if ($isNumericFrequency) {
                // Logic for numeric service_frequency (e.g., 3 = sebulan 3x service)
                // Generate service schedules per bulan based on service_frequency
                $currentDate = $startDate->copy();
                
                while ($currentDate->lte($endDate)) {
                    // Calculate service interval for this month based on mom2.md formula
                    $endOfMonth = $currentDate->copy()->addMonth();
                    $daysInMonth = $currentDate->diffInDays($endOfMonth);
                    $intervalDays = ceil($daysInMonth / $serviceFrequency);
                    
                    // Generate service schedules for this month
                    $serviceDate = $currentDate->copy();
                    for ($i = 0; $i < $serviceFrequency; $i++) {
                        if ($serviceDate->lte($endDate)) {
                            // Check if service schedule already exists for this date
                            $existingSchedule = JobSchedule::where('contract_id', $contractId)
                                ->where('type', $serviceType)
                                ->whereDate('schedule_date', $serviceDate->toDateString())
                                ->first();

                            if (!$existingSchedule) {
                                $serviceSchedule = $this->createServiceSchedule($contract, $serviceDate->copy(), $serviceType, $serviceFrequency);
                                $serviceSchedules[] = $serviceSchedule;
                            }
                            
                            // Move to next service date in this month
                            $serviceDate->addDays($intervalDays);
                        }
                    }
                    
                    // Move to next month
                    $currentDate->addMonth();
                }
            } else {
                // Logic for string service_frequency (monthly, quarterly, etc.)
                // Revisi PSI Apps: kontrak 12 bulan = 12 kali service (1 per bulan)
                $currentDate = $startDate->copy();
                
                while ($currentDate->lte($endDate)) {
                    // Check if service schedule already exists for this date
                    $existingSchedule = JobSchedule::where('contract_id', $contractId)
                        ->where('type', $serviceType)
                        ->whereDate('schedule_date', $currentDate->toDateString())
                        ->first();

                    if (!$existingSchedule) {
                        $serviceSchedule = $this->createServiceSchedule($contract, $currentDate->copy(), $serviceType);
                        $serviceSchedules[] = $serviceSchedule;
                    }

                    $currentDate = $this->getNextServiceDate($currentDate, $serviceFrequency);
                }
            }

            DB::commit();

            Log::info("Auto-generated {$serviceSchedules} service schedules for contract {$contract->contract_number}");

            return [
                'success' => true,
                'schedules' => $serviceSchedules,
                'count' => count($serviceSchedules),
                'message' => 'Service schedules generated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Service schedule generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate service schedules: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create service schedule for specific date
     */
    private function createServiceSchedule(Contract $contract, Carbon $serviceDate, string $serviceType, $serviceFrequency = null): JobSchedule
    {
        // Auto-generate job advice for service
        $jobAdviceNumber = $this->documentNumberService->generate('job_advice', null, null, $contract->id);
        
        $jobAdvice = JobAdvice::create([
            'job_advice_number' => $jobAdviceNumber,
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'company_name' => $contract->customer->name,
            'type' => $serviceType,
            'expected_date' => $serviceDate,
            'status' => 'approved', // Auto-approve for scheduled services
            'with_invoicing' => false, // Service schedules don't need invoicing
            'with_materials' => true, // Services may need materials
            'notes' => "Auto-generated service schedule for contract: {$contract->contract_number}",
            'submitted_by' => 1, // System user
            'created_by' => 1,
        ]);

        // Auto-generate job schedule number using the shared document numbering rules.
        $documentTypeMap = [
            'install' => 'installation_report',
            'install_free' => 'installation_free',
            'service' => 'customer_service_report',
            'service_first' => 'customer_service_report',
            'service_routine' => 'customer_service_report',
            'remove' => 'remove',
            'removal' => 'remove',
            'remove_free' => 'remove_free',
            'remove free' => 'remove_free',
        ];
        $jobScheduleNumber = $this->documentNumberService->generate(
            $documentTypeMap[strtolower($serviceType)] ?? 'job_schedule',
            null,
            null,
            $contract->id,
            null,
            null,
            null,
            null,
            $serviceDate
        );
        
        // Get building from contract (first building)
        $building = $contract->buildings()->first();
        
        // Get service frequency from contract (for period field - mom2.md: period = service frequency)
        // Use provided serviceFrequency parameter if available, otherwise use contract's service_frequency
        $contractServiceFrequency = $serviceFrequency ?? $contract->service_frequency ?? null;
        // If service_frequency is string (monthly, quarterly, etc.), convert to numeric if needed
        // For now, we'll use service_frequency as-is for period field
        
        // Create Job Schedule for service
        $jobSchedule = JobSchedule::create([
            'job_number' => $jobScheduleNumber,
            'job_advice_id' => $jobAdvice->id,
            'contract_id' => $contract->id,
            'building_id' => $building ? $building->id : null,
            'schedule_date' => $serviceDate,
            'expected_date' => $serviceDate,
            'status' => 'scheduled',
            'type' => $serviceType,
            'company_name' => $contract->customer->name,
            'contract_number' => $contract->contract_number,
            'period' => is_numeric($contractServiceFrequency) ? $contractServiceFrequency : null, // Auto-fill period with service_frequency (mom2.md)
            'service_frequency' => is_numeric($contractServiceFrequency) ? $contractServiceFrequency : null,
            'service_period_type' => is_string($contractServiceFrequency) ? $contractServiceFrequency : ($contractServiceFrequency ? 'monthly' : null),
            'internal_notes' => "Auto-generated service schedule for contract: {$contract->contract_number}",
            'created_by' => 1, // System user
            'updated_by' => 1,
        ]);

        // Calculate service interval if service frequency is provided (mom2.md formula)
        if ($jobSchedule->service_frequency && $jobSchedule->schedule_date) {
            $jobSchedule->calculateServiceInterval();
        }

        return $jobSchedule;
    }

    /**
     * Get next service date based on frequency
     */
    private function getNextServiceDate(Carbon $currentDate, string $frequency): Carbon
    {
        switch ($frequency) {
            case 'weekly':
                return $currentDate->addWeek();
            case 'bi-weekly':
                return $currentDate->addWeeks(2);
            case 'monthly':
                return $currentDate->addMonth();
            case 'bi-monthly':
                return $currentDate->addMonths(2);
            case 'quarterly':
                return $currentDate->addMonths(3);
            case 'semi-annually':
                return $currentDate->addMonths(6);
            case 'annually':
                return $currentDate->addYear();
            default:
                return $currentDate->addMonth(); // Default to monthly
        }
    }

    /**
     * Generate service schedules for all active contracts (Berdasarkan BRD)
     */
    public function generateServiceSchedulesForAllActiveContracts(): array
    {
        try {
            $activeContracts = Contract::where('status', 'active')
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->get();

            $totalSchedules = 0;
            $results = [];

            foreach ($activeContracts as $contract) {
                $result = $this->generateServiceSchedulesForContract($contract->id);
                if ($result['success']) {
                    $totalSchedules += $result['count'];
                    $results[] = [
                        'contract_number' => $contract->contract_number,
                        'schedules_generated' => $result['count']
                    ];
                }
            }

            Log::info("Auto-generated {$totalSchedules} service schedules for {$activeContracts->count()} active contracts");

            return [
                'success' => true,
                'total_schedules' => $totalSchedules,
                'contracts_processed' => $activeContracts->count(),
                'results' => $results,
                'message' => "Generated {$totalSchedules} service schedules for {$activeContracts->count()} contracts"
            ];

        } catch (\Exception $e) {
            Log::error('Bulk service schedule generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate service schedules: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check and generate overdue service schedules
     */
    public function generateOverdueServiceSchedules(): array
    {
        try {
            $overdueContracts = Contract::where('status', 'active')
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->whereHas('jobSchedules', function ($query) {
                    $query->where('type', 'maintenance')
                        ->where('status', '!=', 'completed')
                        ->where('schedule_date', '<', now()->subDays(7)); // Overdue by 7+ days
                })
                ->get();

            $totalGenerated = 0;
            $results = [];

            foreach ($overdueContracts as $contract) {
                // Generate catch-up schedules for overdue services
                $result = $this->generateCatchUpServiceSchedules($contract);
                if ($result['success']) {
                    $totalGenerated += $result['count'];
                    $results[] = [
                        'contract_number' => $contract->contract_number,
                        'catch_up_schedules' => $result['count']
                    ];
                }
            }

            return [
                'success' => true,
                'total_generated' => $totalGenerated,
                'contracts_processed' => $overdueContracts->count(),
                'results' => $results,
                'message' => "Generated {$totalGenerated} catch-up service schedules"
            ];

        } catch (\Exception $e) {
            Log::error('Overdue service schedule generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate overdue service schedules: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate catch-up service schedules for overdue contract
     */
    private function generateCatchUpServiceSchedules(Contract $contract): array
    {
        try {
            $serviceFrequency = $contract->service_frequency ?? 'monthly';
            $lastServiceDate = $this->getLastServiceDate($contract->id);
            
            if (!$lastServiceDate) {
                $lastServiceDate = Carbon::parse($contract->start_date);
            }

            $currentDate = $lastServiceDate->copy();
            $endDate = Carbon::parse($contract->end_date);
            $catchUpSchedules = [];

            while ($currentDate->lte($endDate) && $currentDate->lt(now())) {
                $currentDate = $this->getNextServiceDate($currentDate, $serviceFrequency);
                
                if ($currentDate->lt(now())) {
                    $schedule = $this->createServiceSchedule($contract, $currentDate, 'maintenance');
                    $catchUpSchedules[] = $schedule;
                }
            }

            return [
                'success' => true,
                'count' => count($catchUpSchedules),
                'schedules' => $catchUpSchedules
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate catch-up schedules: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get last service date for contract
     */
    private function getLastServiceDate(int $contractId): ?Carbon
    {
        $lastSchedule = JobSchedule::where('contract_id', $contractId)
            ->where('type', 'maintenance')
            ->where('status', 'completed')
            ->orderBy('schedule_date', 'desc')
            ->first();

        return $lastSchedule ? Carbon::parse($lastSchedule->schedule_date) : null;
    }
}
