<?php

namespace App\Services\Imports\Catalyst;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upsert detail master option dari data Catalyst (UAT 19 Nov 2026).
 *
 * Aturannya sengaja konservatif:
 * - master option dicari berdasarkan NAMA (case-insensitive), bukan id hardcoded,
 *   karena id di DB hasil bootstrap dan DB lama tidak dijamin sama;
 * - master option yang tidak ada TIDAK dibuat - itu master milik sistem;
 * - detail dicocokkan per (master_option_id, option_name) case-insensitive dan
 *   tidak pernah dihapus / diubah - yang sudah ada dibiarkan, termasuk status aktifnya.
 */
class CatalystMasterOptionWriter
{
    /** @var array<string, int|null> */
    private array $masterIdByName = [];

    /** @var array<int, array<string, int>> master_option_id -> [lower(option_name) => id] */
    private array $detailIndex = [];

    private ?array $detailColumns = null;

    public function masterOptionId(string $name): ?int
    {
        $key = mb_strtolower(trim($name));

        if (array_key_exists($key, $this->masterIdByName)) {
            return $this->masterIdByName[$key];
        }

        if (!Schema::hasTable('master_options')) {
            return $this->masterIdByName[$key] = null;
        }

        $query = DB::table('master_options')->whereRaw('LOWER(TRIM(name)) = ?', [$key]);
        if (Schema::hasColumn('master_options', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $id = $query->orderBy('id')->value('id');

        return $this->masterIdByName[$key] = $id ? (int) $id : null;
    }

    /**
     * @return array{action: string, id: int|null}  action: inserted | exists | missing_master | empty
     */
    public function upsert(string $masterOptionName, ?string $optionName, bool $apply, ?int $actorId = null): array
    {
        $optionName = $optionName === null ? null : trim(preg_replace('/\s+/u', ' ', $optionName));
        if ($optionName === null || $optionName === '') {
            return ['action' => 'empty', 'id' => null];
        }

        $masterId = $this->masterOptionId($masterOptionName);
        if (!$masterId) {
            return ['action' => 'missing_master', 'id' => null];
        }

        $index = $this->detailIndex($masterId);
        $key = mb_strtolower($optionName);

        if (isset($index[$key])) {
            return ['action' => 'exists', 'id' => $index[$key]];
        }

        if (!$apply) {
            // Dry-run: tandai supaya nama yang sama di baris berikutnya tidak dihitung dua kali.
            $this->detailIndex[$masterId][$key] = 0;

            return ['action' => 'inserted', 'id' => null];
        }

        $columns = $this->detailColumns ??= array_flip(Schema::getColumnListing('option_details'));
        $payload = array_intersect_key([
            'master_option_id' => $masterId,
            'option_name' => $optionName,
            'label' => $optionName,
            'is_active' => true,
            'created_by' => $actorId,
            'updated_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $columns);

        $id = (int) DB::table('option_details')->insertGetId($payload);
        $this->detailIndex[$masterId][$key] = $id;

        return ['action' => 'inserted', 'id' => $id];
    }

    /**
     * @return array<string, int>
     */
    private function detailIndex(int $masterId): array
    {
        if (isset($this->detailIndex[$masterId])) {
            return $this->detailIndex[$masterId];
        }

        $query = DB::table('option_details')->where('master_option_id', $masterId);
        if (Schema::hasColumn('option_details', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $index = [];
        foreach ($query->orderBy('id')->get(['id', 'option_name']) as $row) {
            $key = mb_strtolower(trim((string) $row->option_name));
            $index[$key] ??= (int) $row->id;
        }

        return $this->detailIndex[$masterId] = $index;
    }
}
