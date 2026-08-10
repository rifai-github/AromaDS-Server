<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Building;
use App\Models\City;
use App\Models\Contract;
use App\Models\Quotation;
use App\Models\JobAdvice;
use App\Models\OperationalArea;
use App\Models\Survey;
use App\Models\MaterialIssue;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Document Number Service
 * 
 * Generate document numbers according to MOM10 format:
 * [BRANCH_CODE]-[TYPE_CODE]/[YY]-[MM]/[NNNN]
 * 
 * Example: JKT-SR/25-11/0001
 * - JKT = Branch code (Jakarta)
 * - SR = Document type code (Survey)
 * - 25 = Year (2025, 2 digits)
 * - 11 = Month (November)
 * - 0001 = Sequence number (4 digits, resets monthly)
 * 
 * Document Type Codes:
 * - CA = Contract
 * - SQ = Quotation
 * - JA = Job Advice
 * - SR = Survey
 * - MI = Material Issued
 * - CS = Customer
 * - RV = Remove
 * - RF = Remove Free
 * - RR = Remove Report
 * - IR = Installation Report
 * - CSR = Customer Service Report
 */
class DocumentNumberService
{
    /**
     * Document type codes mapping
     */
    private const TYPE_CODES = [
        'contract' => 'CA',
        'quotation' => 'SQ',
        'job_advice' => 'JA',
        'job_advice_complain' => 'COM', // Special prefix for Complain type
        'berita_acara' => 'BA',
        'survey' => 'SR',
        'material_issue' => 'MI',
        'customer' => 'CS',
        'remove' => 'RV',
        'remove_report' => 'RR',
        'remove_free' => 'RF',
        'receiving_report' => 'RR',
        'installation_report' => 'IR',
        'installation_free' => 'IF', // Installation Free (Trial/Uji Coba)
        'customer_service_report' => 'CSR',
        'job_schedule' => 'JS', // Default job schedule code
        'inventory_request' => 'IRQ', // Inventory Request
        'inventory_receiving' => 'IRC', // Inventory Receiving
        'inventory_issuing' => 'WI', // Warehouse Issued (changed from IIS)
        'master_corporate' => 'COR', // Master Corporate
        'contract_termination' => 'CT', // Contract Termination
        'material_return' => 'ADS-RTR', // Material Return (Technician Return)
        
        // Job Schedule Variations
        'job_schedule_extra' => 'EXT',
        'job_schedule_complain' => 'NR',
        'job_schedule_suspend' => 'SUS',
        'job_schedule_dpf' => 'DPF',
        'lost_unit_report' => 'RPT', // Lost Unit Report
        'stock_opname' => 'SO', // Stock Opname
        'invoice' => 'INV', // Standard Invoice
    ];

    /**
     * Model table mapping for checking existing numbers
     */
    private const MODEL_TABLES = [
        'contract' => 'contracts',
        'quotation' => 'quotations',
        'job_advice' => 'job_advices',
        'job_advice_complain' => 'job_advices', // Uses same table as job_advice
        'berita_acara' => 'berita_acara',
        'survey' => 'surveys',
        'material_issue' => 'material_issues',
        'customer' => 'customers',
        'remove' => 'job_schedules',
        'remove_report' => 'job_schedules',
        'remove_free' => 'job_schedules',
        'receiving_report' => 'inventory_receivings',
        'installation_report' => 'job_schedules',
        'installation_free' => 'job_schedules', // IF uses same table as job_schedules
        'customer_service_report' => 'job_schedules',
        'job_schedule' => 'job_schedules',
        'inventory_request' => 'inventory_requests',
        'inventory_receiving' => 'inventory_receivings',
        'inventory_issuing' => 'inventory_issuings',
        'master_corporate' => 'master_corporates',
        'contract_termination' => 'contract_terminations',
        'material_return' => 'material_returns',
        
        // Job Schedule Variations (all use job_schedules table)
        'job_schedule_extra' => 'job_schedules',
        'job_schedule_complain' => 'job_schedules',
        'job_schedule_suspend' => 'job_schedules',
        'job_schedule_dpf' => 'job_schedules',
        'lost_unit_report' => 'lost_unit_reports', // Lost Unit Report
        'stock_opname' => 'stock_opnames',
        'invoice' => 'invoices',
    ];

    /**
     * Number field mapping for each document type
     */
    private const NUMBER_FIELDS = [
        'contract' => 'contract_number',
        'quotation' => 'quotation_number',
        'job_advice' => 'job_advice_number',
        'job_advice_complain' => 'job_advice_number', // Uses same field as job_advice
        'berita_acara' => 'berita_acara_number',
        'survey' => 'survey_number',
        'material_issue' => 'issue_number',
        'customer' => 'customer_code',
        'remove' => 'job_number',
        'remove_report' => 'job_number',
        'remove_free' => 'job_number',
        'receiving_report' => 'receiving_number',
        'installation_report' => 'job_number',
        'installation_free' => 'job_number', // IF uses job_number field
        'customer_service_report' => 'job_number',
        'job_schedule' => 'job_number',
        'inventory_request' => 'request_number',
        'inventory_receiving' => 'receiving_number',
        'inventory_issuing' => 'issuing_number',
        'master_corporate' => 'code',
        'contract_termination' => 'termination_number',
        'material_return' => 'return_number',
        
        // Job Schedule Variations (all use job_number field)
        'job_schedule_extra' => 'job_number',
        'job_schedule_complain' => 'job_number',
        'job_schedule_suspend' => 'job_number',
        'job_schedule_dpf' => 'job_number',
        'lost_unit_report' => 'report_number', // Lost Unit Report
        'stock_opname' => 'opname_no',
        'invoice' => 'invoice_number',
    ];

    private const OPERATIONAL_AREA_DOCUMENT_TYPES = [
        'job_advice',
        'installation_report',
        'installation_free',
        'customer_service_report',
        'job_schedule',
        'job_schedule_extra',
        'job_schedule_complain',
        'job_schedule_suspend',
        'job_schedule_dpf',
        'remove',
        'remove_report',
        'remove_free',
    ];

    /**
     * Generate document number
     * 
     * @param string $documentType Document type (contract, quotation, job_advice, survey, material_issue, customer)
     * @param string|null $branchCode Branch code (if null, will try to get from context)
     * @param int|null $buildingId Building ID (for getting branch from location)
     * @param int|null $contractId Contract ID (for getting branch from contract)
     * @param int|null $quotationId Quotation ID (for getting branch from quotation)
     * @param int|null $surveyId Survey ID (for getting branch from survey)
     * @param int|null $warehouseId Warehouse ID (for getting branch from warehouse)
     * @param \DateTimeInterface|string|null $documentDate Date used for YY-MM prefix (defaults to today)
     * @return string Generated document number
     */
    public function generate(
        string $documentType,
        ?string $branchCode = null,
        ?int $buildingId = null,
        ?int $contractId = null,
        ?int $quotationId = null,
        ?int $surveyId = null,
        ?int $warehouseId = null,
        ?int $branchId = null,
        \DateTimeInterface|string|null $documentDate = null
    ): string {
        // Get type code
        $typeCode = self::TYPE_CODES[$documentType] ?? strtoupper(substr($documentType, 0, 2));
        
        // Operational documents must follow the service branch assignment,
        // not only the building's administrative city. A Bogor building can be
        // served by Jakarta, so JA and JS numbers must both carry JKT.
        if (!$branchCode && in_array($documentType, self::OPERATIONAL_AREA_DOCUMENT_TYPES, true)) {
            $branchCode = $this->getBranchCodeFromOperationalArea(
                $buildingId,
                $contractId,
                $quotationId,
                $surveyId
            );
        }

        // Get branch code if not provided
        if (!$branchCode) {
            $branchCode = $this->getBranchCodeFromContext(
                $buildingId,
                $contractId,
                $quotationId,
                $surveyId,
                $warehouseId,
                $branchId
            );
        }
        
        // Default to JKT if branch code still not found
        if (!$branchCode) {
            $branchCode = 'JKT';
            Log::info("DocumentNumberService: Branch code not found, using default JKT for {$documentType}");
        }
        
        $numberDate = $documentDate ? Carbon::parse($documentDate) : Carbon::now();
        $year = $numberDate->format('y');
        $month = $numberDate->format('m');
        
        // Generate prefix
        $prefix = "{$branchCode}-{$typeCode}/{$year}-{$month}/";
        
        // Get next sequence number
        $sequence = $this->getNextSequenceNumber($documentType, $prefix);
        
        // Generate full number
        $documentNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        Log::info("DocumentNumberService: Generated {$documentType} number", [
            'document_type' => $documentType,
            'branch_code' => $branchCode,
            'type_code' => $typeCode,
            'year' => $year,
            'month' => $month,
            'sequence' => $sequence,
            'document_number' => $documentNumber
        ]);
        
        return $documentNumber;
    }

    /**
     * Get branch code from context (building, contract, quotation, survey, warehouse)
     */
    private function getBranchCodeFromContext(
        ?int $buildingId = null,
        ?int $contractId = null,
        ?int $quotationId = null,
        ?int $surveyId = null,
        ?int $warehouseId = null,
        ?int $branchId = null
    ): ?string {
        // Try from branchId explicitly
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                return $branch->code;
            }
        }

        // Try from building
        if ($buildingId) {
            $building = Building::with(['city', 'province'])->find($buildingId);
            if ($building) {
                $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
                if ($branch) {
                    return $branch->code;
                }
            }
        }
        
        // Try from contract
        if ($contractId) {
            $contract = Contract::with(['quotation.survey.building.city', 'quotation.survey.building.province'])->find($contractId);
            if ($contract && $contract->quotation && $contract->quotation->survey && $contract->quotation->survey->building) {
                $building = $contract->quotation->survey->building;
                $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
                if ($branch) {
                    return $branch->code;
                }
            }
        }
        
        // Try from quotation
        if ($quotationId) {
            $quotation = Quotation::with(['survey.building.city', 'survey.building.province'])->find($quotationId);
            if ($quotation && $quotation->survey && $quotation->survey->building) {
                $building = $quotation->survey->building;
                $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
                if ($branch) {
                    return $branch->code;
                }
            }
        }
        
        // Try from survey
        if ($surveyId) {
            $survey = Survey::with(['building.city', 'building.province'])->find($surveyId);
            if ($survey && $survey->building) {
                $building = $survey->building;
                $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
                if ($branch) {
                    return $branch->code;
                }
            }
        }
        
        // Try from warehouse
        if ($warehouseId) {
            $warehouse = \App\Models\Warehouse::with(['branch'])->find($warehouseId);
            if ($warehouse && $warehouse->branch) {
                return $warehouse->branch->code;
            }
        }
        
        return null;
    }

    /**
     * Resolve branch code for Job Advice via OperationalArea (service area)
     * assignment. The building's location is matched against operational areas
     * from most specific (subdistrict) to least specific (province), and the
     * first active matching area's branch is returned.
     */
    public function getBranchCodeFromOperationalArea(
        ?int $buildingId = null,
        ?int $contractId = null,
        ?int $quotationId = null,
        ?int $surveyId = null
    ): ?string {
        $building = $this->resolveBuilding($buildingId, $contractId, $quotationId, $surveyId);

        if (!$building) {
            return null;
        }

        $locationFields = [
            'subdistrict_id' => $building->subdistrict_id,
            'district_id'    => $building->district_id,
            'city_id'        => $building->city_id,
            'province_id'    => $building->province_id,
        ];

        foreach ($locationFields as $column => $value) {
            if (!$value) {
                continue;
            }

            $area = OperationalArea::with('branch')
                ->where($column, $value)
                ->where('is_active', true)
                ->whereHas('branch', fn ($q) => $q->where('is_active', true))
                ->first();

            if ($area && $area->branch) {
                return $area->branch->code;
            }
        }

        return null;
    }

    /**
     * Resolve a Building from one of the available source documents.
     */
    private function resolveBuilding(
        ?int $buildingId = null,
        ?int $contractId = null,
        ?int $quotationId = null,
        ?int $surveyId = null
    ): ?Building {
        if ($buildingId) {
            return Building::find($buildingId);
        }

        if ($contractId) {
            $contract = Contract::with('quotation.survey.building')->find($contractId);
            $building = $contract?->quotation?->survey?->building ?? null;
            if ($building) {
                return $building;
            }
        }

        if ($quotationId) {
            $quotation = Quotation::with('survey.building')->find($quotationId);
            $building = $quotation?->survey?->building ?? null;
            if ($building) {
                return $building;
            }
        }

        if ($surveyId) {
            $survey = Survey::with('building')->find($surveyId);
            return $survey?->building ?? null;
        }

        return null;
    }

    /**
     * Get branch from location (city_id and province_id)
     */
    private function getBranchFromLocation(?int $cityId = null, ?int $provinceId = null): ?Branch
    {
        // Try to match by city first (more specific)
        if ($cityId) {
            $branch = Branch::where('city_id', $cityId)
                ->where('is_active', true)
                ->first();

            if ($branch) {
                return $branch;
            }

            // The `cities` table carries two overlapping legacy imports, so the
            // same city (e.g. "KABUPATEN SEMARANG") often exists under two
            // different IDs - a building can end up on one ID and its branch on
            // the other. Exact id match then silently fails and every document
            // for that city falls back to the JKT default. Match by normalized
            // name before giving up, since the branch's own city_id is trusted.
            $branch = $this->getBranchByCityName($cityId);
            if ($branch) {
                return $branch;
            }
        }

        // If no match by city, try by province (less specific)
        if ($provinceId) {
            $branch = Branch::where('province_id', $provinceId)
                ->where('is_active', true)
                ->first();

            if ($branch) {
                return $branch;
            }
        }

        return null;
    }

    private function getBranchByCityName(int $cityId): ?Branch
    {
        // withTrashed() on both sides: the "203 vs 582" style duplicates were
        // partly cleaned up by soft-deleting the stale row, but buildings still
        // point at the deleted id - we only need its name for matching, not an
        // active record.
        $cityName = City::withTrashed()->whereKey($cityId)->value('name');
        if (!$cityName) {
            return null;
        }

        $normalized = mb_strtolower(trim($cityName));
        if ($normalized === '') {
            return null;
        }

        return Branch::where('is_active', true)
            ->whereHas('city', function ($query) use ($normalized) {
                $query->withTrashed()->whereRaw('LOWER(TRIM(name)) = ?', [$normalized]);
            })
            ->first();
    }

    /**
     * Get next sequence number for the given document type and prefix
     * Uses database lock to prevent race conditions
     */
    private function getNextSequenceNumber(string $documentType, string $prefix): int
    {
        $table = self::MODEL_TABLES[$documentType] ?? null;
        $numberField = self::NUMBER_FIELDS[$documentType] ?? 'number';
        
        if (!$table) {
            Log::warning("DocumentNumberService: Unknown document type {$documentType}, using default sequence 1");
            return 1;
        }
        
        // Get the last number for this prefix (including soft deleted)
        // Use lockForUpdate to prevent race conditions
        $lastNumber = DB::table($table)
            ->where($numberField, 'like', $prefix . '%')
            ->whereNotNull($numberField)
            ->orderByRaw("CAST(SUBSTRING({$numberField}, -4) AS UNSIGNED) DESC")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->value($numberField);
        
        if ($lastNumber && preg_match('/(\d{4})$/', $lastNumber, $matches)) {
            $lastSeq = (int) $matches[1];
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }
        
        // Double check uniqueness (handle race conditions) with lock
        $generatedNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        $exists = DB::table($table)
            ->where($numberField, $generatedNumber)
            ->lockForUpdate()
            ->exists();
        
        // If exists, increment until we find a unique number (with max retry to prevent infinite loop)
        $maxRetries = 100;
        $retryCount = 0;
        while ($exists && $retryCount < $maxRetries) {
            $nextSeq++;
            $generatedNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            $exists = DB::table($table)
                ->where($numberField, $generatedNumber)
                ->exists();
            $retryCount++;
        }
        
        if ($retryCount >= $maxRetries) {
            Log::error("DocumentNumberService: Max retries ({$maxRetries}) reached for prefix {$prefix}");
        }
        
        return $nextSeq;
    }

    /**
     * Get branch code from building
     */
    public function getBranchCodeFromBuilding(int $buildingId): ?string
    {
        $building = Building::with(['city', 'province'])->find($buildingId);
        if (!$building) {
            return null;
        }
        
        $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
        return $branch ? $branch->code : null;
    }

    /**
     * Get branch code from contract
     */
    public function getBranchCodeFromContract(int $contractId): ?string
    {
        $contract = Contract::with(['quotation.survey.building.city', 'quotation.survey.building.province'])->find($contractId);
        if (!$contract || !$contract->quotation || !$contract->quotation->survey || !$contract->quotation->survey->building) {
            return null;
        }
        
        $building = $contract->quotation->survey->building;
        $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
        return $branch ? $branch->code : null;
    }

    /**
     * Get branch code from quotation
     */
    public function getBranchCodeFromQuotation(int $quotationId): ?string
    {
        $quotation = Quotation::with(['survey.building.city', 'survey.building.province'])->find($quotationId);
        if (!$quotation || !$quotation->survey || !$quotation->survey->building) {
            return null;
        }
        
        $building = $quotation->survey->building;
        $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
        return $branch ? $branch->code : null;
    }

    /**
     * Get branch code from survey
     */
    public function getBranchCodeFromSurvey(int $surveyId): ?string
    {
        $survey = Survey::with(['building.city', 'building.province'])->find($surveyId);
        if (!$survey || !$survey->building) {
            return null;
        }
        
        $building = $survey->building;
        $branch = $this->getBranchFromLocation($building->city_id, $building->province_id);
        return $branch ? $branch->code : null;
    }

    /**
     * Get branch code from warehouse
     */
    public function getBranchCodeFromWarehouse(int $warehouseId): ?string
    {
        $warehouse = \App\Models\Warehouse::with(['branch'])->find($warehouseId);
        if (!$warehouse || !$warehouse->branch) {
            return null;
        }
        
        return $warehouse->branch->code;
    }
}

