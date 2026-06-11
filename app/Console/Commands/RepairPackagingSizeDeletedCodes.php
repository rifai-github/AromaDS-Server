<?php

namespace App\Console\Commands;

use App\Models\PackagingSize;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairPackagingSizeDeletedCodes extends Command
{
    protected $signature = 'warehouse:repair-packaging-size-deleted-codes
                            {--apply : Persist the repair; without this option it only previews}';

    protected $description = 'Archive codes on soft-deleted packaging sizes so their original codes can be reused';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $packagingSizes = PackagingSize::onlyTrashed()
            ->orderBy('id')
            ->get()
            ->reject(fn (PackagingSize $packagingSize) => PackagingSize::isDeletedCode($packagingSize->code));

        if ($packagingSizes->isEmpty()) {
            $this->info('No soft-deleted packaging size codes need repair.');

            return self::SUCCESS;
        }

        $rows = $packagingSizes->map(function (PackagingSize $packagingSize): array {
            return [
                'id' => $packagingSize->id,
                'name' => $packagingSize->name,
                'old_code' => $packagingSize->code,
                'new_code' => PackagingSize::makeDeletedCode(
                    (string) $packagingSize->code,
                    $packagingSize->id,
                    $packagingSize->deleted_at ?? $packagingSize->updated_at
                ),
                'deleted_at' => optional($packagingSize->deleted_at)->format('Y-m-d H:i:s'),
            ];
        });

        $this->table(['ID', 'Name', 'Old Code', 'New Code', 'Deleted At'], $rows->values()->all());

        if (!$apply) {
            $this->warn('Preview only. Run with --apply to update these codes.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($packagingSizes): void {
            foreach ($packagingSizes as $packagingSize) {
                $packagingSize->forceFill([
                    'code' => PackagingSize::makeDeletedCode(
                        (string) $packagingSize->code,
                        $packagingSize->id,
                        $packagingSize->deleted_at ?? $packagingSize->updated_at
                    ),
                ])->save();
            }
        });

        $this->info("Repaired {$packagingSizes->count()} soft-deleted packaging size code(s).");

        return self::SUCCESS;
    }
}
