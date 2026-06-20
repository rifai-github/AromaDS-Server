<?php

namespace App\Console\Commands;

use App\Models\WarehouseProduct;
use Illuminate\Console\Command;

class BackfillWarehouseProductStockLevels extends Command
{
    protected $signature = 'warehouse:backfill-stock-levels
        {--apply : Apply the backfill. Default is dry-run}';

    protected $description = 'Copy minimum_stock/maximum_stock from each master product into its existing warehouse_products rows wherever the warehouse row is still at the legacy 0/0 default';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist the backfill.');
        }

        $rows = WarehouseProduct::with('masterProduct')
            ->where('minimum_stock', 0)
            ->where('maximum_stock', 0)
            ->get();

        $stats = ['updated' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            $masterProduct = $row->masterProduct;

            if (! $masterProduct || ((int) $masterProduct->minimum_stock === 0 && (int) $masterProduct->maximum_stock === 0)) {
                $stats['skipped']++;
                continue;
            }

            $this->line(sprintf(
                'Warehouse #%d / Product #%d (%s): 0/0 -> %d/%d',
                $row->warehouse_id,
                $row->master_product_id,
                $masterProduct->name,
                $masterProduct->minimum_stock,
                $masterProduct->maximum_stock
            ));

            if ($apply) {
                $row->update([
                    'minimum_stock' => $masterProduct->minimum_stock,
                    'maximum_stock' => $masterProduct->maximum_stock,
                ]);
            }

            $stats['updated']++;
        }

        $this->table(['Metric', 'Value'], [
            ['Candidate rows (0/0)', $rows->count()],
            ['Updated', $stats['updated']],
            ['Skipped (master also 0/0)', $stats['skipped']],
        ]);

        if (! $apply) {
            $this->warn('Dry run complete. Re-run with --apply to persist these changes.');
        }

        return self::SUCCESS;
    }
}
