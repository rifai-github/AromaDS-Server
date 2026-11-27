<?php

namespace App\Services\Imports\Catalyst;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 24 Nov 2026 (SMD-AG/26-04/0003): di Catalyst kontrak itu punya 3 gedung dengan billing
 * group masing-masing (ADS-BIL-09/1146, /2458, /2459), di ERP cuma 1 billing group. Bukan bug
 * importer: billing group 2458/2459 baru dibuat di Catalyst 2 Okt 2026, setelah import 20 Sep,
 * dan step importer hanya MENAMBAH link gedung - link lama gedung 2 & 3 ke 1146 tidak pernah
 * dipindah. Di prod 25 Nov: 4 kontrak / 6 billing group tertinggal.
 *
 * Per kontrak berpeta MKTContractHd, untuk setiap kode billing group yang ada di
 * MKTContractDt Catalyst tapi belum ada di ERP:
 *  - buat billing group baru (data pajak/pembayaran disalin dari billing group kontrak yang
 *    sudah ada, PIC & alamat dari MsBillingGroup Catalyst), catat peta MKTContractDt_billing;
 *  - pindahkan link gedung (contract_buildings + billing_group_buildings) gedung itu ke
 *    billing group baru;
 *  - set contract_rooms.billing_group_id room gedung itu (via peta MKTContractDt_room).
 * Invoice yang sudah terbit TIDAK diubah - hanya dilaporkan.
 */
class CatalystBillingGroupSplitRepair
{
    private const SOURCE_SYSTEM = 'catalyst';

    /** Kolom billing_groups yang TIDAK disalin dari billing group contoh. */
    private const NOT_COPIED = ['id', 'billing_group_name', 'billing_amount', 'pic_name', 'pic_phone', 'pic_email', 'pic_address',
        'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'update_by_1', 'update_at_1', 'update_by_2', 'update_at_2', 'delete_by', 'delete_at'];

    /**
     * @param  Collection<int, object>  $sourceRows  MKTContractDt: TransNmbr, BillingGroup, Building, FloorCode, RoomCode, Room, AmountForex
     * @param  Collection<string, object>  $legacyGroups  MsBillingGroup per BillingCode (Attn, Telp, Phone, EmailBilling, Address)
     * @return list<array<string, mixed>> satu entri per kontrak yang perlu diperbaiki
     */
    public function plan(Collection $sourceRows, Collection $legacyGroups, ?string $onlyContract = null): array
    {
        $byContract = $sourceRows
            ->filter(fn ($row) => $this->clean($row->TransNmbr ?? null) && $this->clean($row->BillingGroup ?? null))
            ->when($onlyContract, fn ($rows) => $rows->filter(fn ($row) => strcasecmp($this->clean($row->TransNmbr), trim($onlyContract)) === 0))
            ->groupBy(fn ($row) => $this->clean($row->TransNmbr));

        $plans = [];
        foreach ($byContract as $contractNumber => $rows) {
            $contractId = $this->mappedId('MKTContractHd', $contractNumber, 'contracts');
            if (! $contractId) {
                continue;
            }

            $existing = DB::table('billing_groups')->where('contract_id', $contractId)->whereNull('deleted_at')->orderBy('id')->get();
            $existingCodes = $existing->mapWithKeys(fn ($group) => [strtoupper(trim((string) $group->billing_group_name)) => (int) $group->id]);

            $missing = [];
            foreach ($rows->groupBy(fn ($row) => $this->clean($row->BillingGroup)) as $code => $groupRows) {
                if ($existingCodes->has(strtoupper($code))) {
                    continue;
                }

                $buildings = [];
                foreach ($groupRows->pluck('Building')->map(fn ($b) => $this->clean($b))->filter()->unique() as $buildingCode) {
                    $buildings[$buildingCode] = $this->mappedId('MsBuilding', $buildingCode, 'buildings');
                }

                $rooms = [];
                foreach ($groupRows as $row) {
                    $key = implode('||', [$contractNumber, trim((string) ($row->Building ?? '')), trim((string) ($row->FloorCode ?? '')), trim((string) ($row->RoomCode ?? '')), trim((string) ($row->Room ?? ''))]);
                    if ($roomId = $this->mappedId('MKTContractDt_room', $key, 'contract_rooms')) {
                        $rooms[$roomId] = true;
                    }
                }

                $missing[] = [
                    'code' => $code,
                    'amount' => (float) $groupRows->sum(fn ($row) => (float) ($row->AmountForex ?? 0)),
                    'legacy' => $legacyGroups[strtoupper($code)] ?? null,
                    'buildings' => $buildings,
                    'contract_room_ids' => array_keys($rooms),
                ];
            }

            if ($missing === []) {
                continue;
            }

            $plans[] = [
                'contract_number' => $contractNumber,
                'contract_id' => $contractId,
                'template' => $existing->first(),
                'existing_group_ids' => $existing->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'missing' => $missing,
                'issued_invoices' => Schema::hasTable('invoices')
                    ? DB::table('invoices')->where('contract_id', $contractId)->whereNull('deleted_at')->whereNotIn('invoice_status', ['cancelled'])->count()
                    : 0,
            ];
        }

        return $plans;
    }

    /** @return array{groups: int, building_links: int, rooms: int} */
    public function apply(array $plan, ?int $userId): array
    {
        $done = ['groups' => 0, 'building_links' => 0, 'rooms' => 0];

        DB::transaction(function () use ($plan, $userId, &$done) {
            $groupColumns = Schema::getColumnListing('billing_groups');
            $template = (array) ($plan['template'] ?? []);

            foreach ($plan['missing'] as $missing) {
                $legacy = $missing['legacy'];
                $payload = array_diff_key($template, array_flip(self::NOT_COPIED));
                $payload = array_merge($payload, [
                    'contract_id' => $plan['contract_id'],
                    'billing_group_name' => $missing['code'],
                    // Jangan mewarisi nomor transaksi BG templat: nomornya = kodenya sendiri.
                    'transaction_number' => preg_match(\App\Models\Finance\BillingGroup::TRANSACTION_NUMBER_PATTERN, (string) $missing['code']) ? $missing['code'] : null,
                    'billing_amount' => $missing['amount'],
                    'is_active' => true,
                    'pic_name' => $this->clean($legacy->Attn ?? null),
                    'pic_phone' => $this->clean($legacy->Telp ?? null) ?? $this->clean($legacy->Phone ?? null),
                    'pic_email' => $this->clean($legacy->EmailBilling ?? null),
                    'pic_address' => $this->clean($legacy->Address ?? null),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $groupId = (int) DB::table('billing_groups')->insertGetId(array_intersect_key($payload, array_flip($groupColumns)));
                $this->rememberMap('MKTContractDt_billing', $plan['contract_number'].'||'.$missing['code'], 'billing_groups', $groupId);
                $done['groups']++;

                foreach ($missing['buildings'] as $buildingId) {
                    if (! $buildingId) {
                        continue;
                    }
                    $done['building_links'] += $this->moveBuildingLink('contract_buildings', 'billing_id', $plan['existing_group_ids'], $groupId, (int) $buildingId, $userId, false);
                    if (Schema::hasTable('billing_group_buildings')) {
                        $this->moveBuildingLink('billing_group_buildings', 'billing_group_id', $plan['existing_group_ids'], $groupId, (int) $buildingId, $userId, true);
                    }
                }

                if ($missing['contract_room_ids'] !== []) {
                    $done['rooms'] += DB::table('contract_rooms')
                        ->where('contract_id', $plan['contract_id'])
                        ->whereIn('id', $missing['contract_room_ids'])
                        ->update(['billing_group_id' => $groupId, 'updated_at' => now()]);
                }
            }
        });

        return $done;
    }

    private function moveBuildingLink(string $table, string $groupColumn, array $oldGroupIds, int $newGroupId, int $buildingId, ?int $userId, bool $withAudit): int
    {
        $alreadyLinked = DB::table($table)->where($groupColumn, $newGroupId)->where('building_id', $buildingId)->exists();
        $old = DB::table($table)->whereIn($groupColumn, $oldGroupIds)->where('building_id', $buildingId);

        if ($alreadyLinked) {
            return $old->delete();
        }

        if ((clone $old)->exists()) {
            return $old->update([$groupColumn => $newGroupId]);
        }

        $row = [$groupColumn => $newGroupId, 'building_id' => $buildingId];
        if ($withAudit) {
            $row += ['is_active' => true, 'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table($table)->insert(array_intersect_key($row, array_flip(Schema::getColumnListing($table))));

        return 1;
    }

    private function mappedId(string $sourceTable, string $sourceKey, string $targetTable): ?int
    {
        $id = DB::table('source_import_maps')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('source_table', $sourceTable)
            ->where('source_key', $sourceKey)
            ->where('target_table', $targetTable)
            ->value('target_id');

        return $id ? (int) $id : null;
    }

    private function rememberMap(string $sourceTable, string $sourceKey, string $targetTable, int $targetId): void
    {
        $values = ['target_id' => $targetId, 'last_imported_at' => now(), 'updated_at' => now(), 'created_at' => now()];
        DB::table('source_import_maps')->updateOrInsert(
            ['source_system' => self::SOURCE_SYSTEM, 'source_table' => $sourceTable, 'source_key' => $sourceKey, 'target_table' => $targetTable],
            array_intersect_key($values, array_flip(Schema::getColumnListing('source_import_maps')))
        );
    }

    private function clean($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || in_array(strtolower($value), ['null', '\\n', '.', '-'], true) ? null : $value;
    }
}
