<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only sweep for the damage a wrong "one row, quantity N" guess leaves behind.
 *
 * A room holding N units is stored as ONE row with a quantity column in several places
 * (job_advice_rooms, contract_rentals, inventory_issuing_items), while the physical world
 * has N serials, N wall records and N sets of photos. Every place that has to turn the row
 * into N things used to guess differently, and each wrong guess wrote silently: QA
 * 6-7 Sep 2026 lost a contract line, emptied a room, and hard-deleted a serial number that
 * unit_on_walls still pointed at. The fixes stop new damage; this finds what is already
 * there, on any environment, without writing a thing.
 *
 * Nothing here repairs anything on purpose - each finding needs a human decision about
 * which side is the truth.
 */
class AuditUnitDataIntegrity extends Command
{
    protected $signature = 'warehouse:audit-unit-data-integrity
        {--contract= : Limit every check to one contract id}
        {--limit=50 : Rows to print per finding}';

    protected $description = 'Read-only audit: dangling serial references, contract rentals that disagree with the wall, and issuing rows with fewer serials than units.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $contractId = $this->option('contract') ? (int) $this->option('contract') : null;

        $findings = 0;

        $findings += $this->auditDanglingSerialReferences($limit, $contractId);
        $this->newLine();
        $findings += $this->auditIssuingRowsMissingSerials($limit);
        $this->newLine();
        $findings += $this->auditContractRentalsAgainstTheWall($limit, $contractId);

        $this->newLine();

        if ($findings === 0) {
            $this->info('No integrity findings.');
        } else {
            $this->warn("{$findings} finding(s). Nothing was changed - each one needs a decision about which side is right.");
        }

        return self::SUCCESS;
    }

    /**
     * Rows pointing at a serial_numbers row that no longer exists.
     *
     * deleteSerialNumber() used to forceDelete the master row when a line was removed from
     * a receiving, including on the auto-return of a unit that had been installed - so the
     * unit vanished from its install job and from stock while everything else still
     * referenced it (QA 7 Sep 2026, DW300B2606022 / serial_numbers 509).
     */
    private function auditDanglingSerialReferences(int $limit, ?int $contractId): int
    {
        $this->line('<comment>Dangling serial number references</comment>');

        $found = 0;

        $wallQuery = DB::table('unit_on_walls')
            ->whereNotNull('serial_number_id')
            ->whereNotIn('serial_number_id', function ($query) {
                $query->select('id')->from('serial_numbers');
            });

        if ($contractId) {
            $wallQuery->where('contract_id', $contractId);
        }

        $walls = $wallQuery->orderBy('id')->limit($limit)->get(['id', 'serial_number', 'serial_number_id', 'contract_id', 'room_name', 'status']);

        foreach ($walls as $wall) {
            $found++;
            $this->line(sprintf(
                '  unit_on_walls %d (%s, contract %s, %s, status %s) -> serial_numbers %d is gone',
                $wall->id,
                $wall->serial_number ?: '-',
                $wall->contract_id ?: '-',
                $wall->room_name ?: '-',
                $wall->status ?: '-',
                $wall->serial_number_id
            ));
        }

        if (Schema::hasTable('inventory_issuing_item_serials')) {
            $links = DB::table('inventory_issuing_item_serials')
                ->whereNotIn('serial_number_id', function ($query) {
                    $query->select('id')->from('serial_numbers');
                })
                ->orderBy('id')
                ->limit($limit)
                ->get(['id', 'inventory_issuing_item_id', 'serial_number_id']);

            foreach ($links as $link) {
                $found++;
                $this->line(sprintf(
                    '  inventory_issuing_item_serials %d (item %d) -> serial_numbers %d is gone',
                    $link->id,
                    $link->inventory_issuing_item_id,
                    $link->serial_number_id
                ));
            }
        }

        if ($found === 0) {
            $this->line('  none');
        }

        return $found;
    }

    /**
     * Serial-tracked unit rows that were issued for more units than they have serials.
     *
     * The job only ever learns about the serials, so the extra units are invisible to it:
     * the room asks for fewer scans than it holds and closes early.
     */
    private function auditIssuingRowsMissingSerials(int $limit): int
    {
        $this->line('<comment>Issuing rows with fewer serials than units</comment>');

        if (! Schema::hasTable('inventory_issuing_item_serials')) {
            $this->line('  skipped (no inventory_issuing_item_serials table)');

            return 0;
        }

        $rows = DB::table('inventory_issuing_items as ii')
            ->join('master_products as mp', 'ii.product_id', '=', 'mp.id')
            ->join('product_categories as pc', 'mp.product_category_id', '=', 'pc.id')
            ->join('inventory_issuings as iv', 'ii.inventory_issuing_id', '=', 'iv.id')
            ->where('pc.is_unit', true)
            ->where('ii.quantity_requested', '>', 1)
            ->whereIn('iv.status', ['processed', 'sent', 'received'])
            ->orderByDesc('ii.id')
            ->get([
                'ii.id',
                'ii.room_name',
                'ii.quantity_requested',
                'iv.issuing_number',
                'iv.reference_no',
                'mp.name as product_name',
            ]);

        $found = 0;

        foreach ($rows as $row) {
            $linked = DB::table('inventory_issuing_item_serials')
                ->where('inventory_issuing_item_id', $row->id)
                ->count();

            if ($linked >= (int) $row->quantity_requested) {
                continue;
            }

            $found++;

            if ($found > $limit) {
                continue;
            }

            $this->line(sprintf(
                '  %s item %d (%s, %s) qty %d but %d serial(s) - ref %s',
                $row->issuing_number,
                $row->id,
                $row->product_name,
                $row->room_name ?: '-',
                (int) $row->quantity_requested,
                $linked,
                $row->reference_no ?: '-'
            ));
        }

        if ($found === 0) {
            $this->line('  none');
        } elseif ($found > $limit) {
            $this->line(sprintf('  ... and %d more', $found - $limit));
        }

        return $found;
    }

    /**
     * Contracts whose billed quantity for a room no longer matches the units on its wall.
     *
     * A partial Change Rental used to overwrite the contract row instead of splitting it,
     * so the units that were never touched stopped being billed (QA 7 Sep 2026, contract
     * SBY-CA/26-09/0003 went from 2 x Rental 1 to 1 x Rental07 and lost the other unit).
     * A mismatch is not automatically wrong - a unit can legitimately be awaiting install -
     * so this only reports.
     */
    private function auditContractRentalsAgainstTheWall(int $limit, ?int $contractId): int
    {
        $this->line('<comment>Contract rentals that disagree with the wall</comment>');

        $wallQuery = DB::table('unit_on_walls')
            ->whereIn('status', ['active', 'installed', 'on_wall'])
            ->whereNotNull('contract_id')
            ->whereNotNull('room_id')
            ->whereNotNull('rental_id');

        if ($contractId) {
            $wallQuery->where('contract_id', $contractId);
        }

        $wallCounts = $wallQuery
            ->groupBy('contract_id', 'room_id', 'rental_id')
            ->get([
                'contract_id',
                'room_id',
                'rental_id',
                DB::raw('COUNT(*) as units'),
            ]);

        $found = 0;

        foreach ($wallCounts as $wall) {
            $billed = (int) DB::table('contract_rentals')
                ->where('contract_id', $wall->contract_id)
                ->where('room_id', $wall->room_id)
                ->where('master_rental_id', $wall->rental_id)
                ->sum('quantity');

            if ($billed === (int) $wall->units) {
                continue;
            }

            $found++;

            if ($found > $limit) {
                continue;
            }

            $contractNumber = DB::table('contracts')->where('id', $wall->contract_id)->value('contract_number');
            $roomName = DB::table('master_rooms')->where('id', $wall->room_id)->value('room_name');

            $this->line(sprintf(
                '  contract %s (%d) room %s rental %d: %d unit(s) on the wall, %d billed',
                $contractNumber ?: '-',
                $wall->contract_id,
                $roomName ?: $wall->room_id,
                $wall->rental_id,
                (int) $wall->units,
                $billed
            ));
        }

        if ($found === 0) {
            $this->line('  none');
        } elseif ($found > $limit) {
            $this->line(sprintf('  ... and %d more', $found - $limit));
        }

        return $found;
    }
}
