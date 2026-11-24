<?php

namespace App\Reports;

use App\Models\ContractSignatureRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agreement Sign (spec 24 Nov 2026): kontrak mana yang sudah ditandatangani customer dan pihak
 * PSI lewat link tanda tangan digital, dan yang Contract Balik-nya sudah diunggah.
 * Tanda tangan dibaca dari batch link aktif terakhir (contract_signature_requests yang belum
 * dicabut).
 */
class AgreementSignReport extends BusinessReport
{
    private const HIDDEN_STATUSES = ['draft', 'waiting_for_approval', 'rejected', 'cancelled'];

    public function __construct(private ReportData $data) {}

    public function key(): string
    {
        return 'agreement-sign';
    }

    public function title(): string
    {
        return 'Agreement Sign';
    }

    public function description(): string
    {
        return 'Kontrak yang sudah ditandatangani customer dan pihak PSI lewat link tanda tangan digital, '
            .'dan yang Contract Balik-nya sudah diunggah atau belum.';
    }

    public function dateLabel(): string
    {
        return 'Tanggal kontrak';
    }

    public function columns(): array
    {
        return [
            'branch' => 'Branch',
            'department' => 'Dept Name',
            'sales' => 'Sales Name',
            'contract_number' => 'Transaksi Number',
            'contract_type' => 'Contract Type',
            'customer' => 'Customer Name',
            'sign_customer' => 'Sign In Customer',
            'sign_psi' => 'Sign In PSI',
            'contract_balik' => 'Contract Balik',
        ];
    }

    public function extraFilters(): array
    {
        return [
            'sign_status' => ['label' => 'Tanda tangan', 'options' => ['complete' => 'Lengkap (kedua pihak)', 'incomplete' => 'Belum lengkap']],
            'contract_balik' => ['label' => 'Contract Balik', 'options' => ['sudah' => 'Sudah', 'belum' => 'Belum']],
        ];
    }

    public function rows(ReportFilters $filters): Collection
    {
        $contracts = $this->data->contracts()
            ->leftJoin('quotations as q', 'q.id', '=', 'contracts.quotation_id')
            ->leftJoin('customers as cu', 'cu.id', '=', 'contracts.customer_id')
            ->whereNotIn('contracts.contract_status', self::HIDDEN_STATUSES)
            ->whereBetween('contracts.contract_date', [$filters->dateFrom->toDateString(), $filters->dateTo->toDateString()])
            ->orderBy('contracts.contract_date')
            ->orderBy('contracts.contract_number')
            ->get([
                'contracts.id', 'contracts.contract_number', 'contracts.contract_type', 'contracts.marketing_id',
                'q.branch_id as quotation_branch_id', 'q.quotation_type', 'q.existing_contract_id',
                'cu.name as customer_name',
            ]);

        $ids = $contracts->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sales = $this->data->salesPeople($contracts->pluck('marketing_id')->all());
        $balik = $this->data->contractBalik($ids);
        $signatures = $this->signatures($ids);

        return $contracts->map(function ($contract) use ($sales, $balik, $signatures) {
            $branch = $this->data->branch($contract->quotation_branch_id, $contract->contract_number);
            $person = $sales->get($contract->marketing_id);
            $signed = $signatures->get((int) $contract->id, []);
            $isRenewal = $contract->quotation_type === 'renewal' || ! empty($contract->existing_contract_id) || $contract->contract_type === 'renewal';

            return [
                '_branch_id' => $branch?->id,
                'branch' => $branch?->name ?? '-',
                'department' => $person?->department ?: '-',
                'sales' => $person?->name ?: '-',
                'contract_number' => $contract->contract_number,
                'contract_type' => $isRenewal ? 'Renewal' : 'New',
                'customer' => $contract->customer_name ?: '-',
                'sign_customer' => $this->yesNo(! empty($signed[ContractSignatureRequest::ROLE_CUSTOMER])),
                'sign_psi' => $this->yesNo(! empty($signed[ContractSignatureRequest::ROLE_INTERNAL])),
                'contract_balik' => $this->yesNo((bool) $balik->get($contract->id)?->uploaded),
            ];
        })->filter(function (array $row) use ($filters) {
            if ($filters->branchId && $row['_branch_id'] !== $filters->branchId) {
                return false;
            }
            $complete = $row['sign_customer'] === 'Sudah' && $row['sign_psi'] === 'Sudah';
            if ($filters->extra('sign_status') === 'complete' && ! $complete) {
                return false;
            }
            if ($filters->extra('sign_status') === 'incomplete' && $complete) {
                return false;
            }
            if (($balik = $filters->extra('contract_balik')) && $row['contract_balik'] !== ucfirst($balik)) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * Peran yang sudah tanda tangan pada batch link aktif terakhir per kontrak.
     *
     * @param  array<int, int>  $contractIds
     * @return Collection<int, array<string, bool>>
     */
    private function signatures(array $contractIds): Collection
    {
        if ($contractIds === []) {
            return collect();
        }

        return DB::table('contract_signature_requests')
            ->whereIn('contract_id', $contractIds)
            ->whereNull('revoked_at')
            ->orderBy('id')
            ->get(['contract_id', 'batch', 'signer_role', 'signed_at'])
            ->groupBy(fn ($row) => (int) $row->contract_id)
            ->map(function (Collection $rows) {
                $latestBatch = $rows->last()->batch;

                return $rows->where('batch', $latestBatch)
                    ->mapWithKeys(fn ($row) => [$row->signer_role => $row->signed_at !== null])
                    ->all();
            });
    }
}
