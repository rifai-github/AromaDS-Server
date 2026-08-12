<?php

namespace App\Console\Commands;

use App\Models\MasterOption;
use Illuminate\Console\Command;

class RestoreMasterOption extends Command
{
    protected $signature = 'other:restore-master-option
                            {id? : ID of the soft-deleted master option to restore}
                            {--list : List all soft-deleted master options instead of restoring}';

    protected $description = 'Restore a soft-deleted Master Option (and its option details) by ID';

    public function handle(): int
    {
        if ($this->option('list') || !$this->argument('id')) {
            return $this->listTrashed();
        }

        $id = (int) $this->argument('id');
        $masterOption = MasterOption::onlyTrashed()->find($id);

        if (!$masterOption) {
            $this->error("No soft-deleted master option found with ID {$id}.");

            return self::FAILURE;
        }

        $this->table(
            ['ID', 'Name', 'Description', 'Deleted At'],
            [[$masterOption->id, $masterOption->name, $masterOption->description, $masterOption->deleted_at]]
        );

        if (!$this->confirm('Restore this master option?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $masterOption->restore();
        $detailCount = $masterOption->optionDetails()->onlyTrashed()->restore();

        $this->info("Restored master option #{$masterOption->id} ({$masterOption->name})"
            . ($detailCount ? " and {$detailCount} option detail(s)." : '.'));

        return self::SUCCESS;
    }

    private function listTrashed(): int
    {
        $trashed = MasterOption::onlyTrashed()->orderByDesc('deleted_at')->get();

        if ($trashed->isEmpty()) {
            $this->info('No soft-deleted master options.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Description', 'Deleted At'],
            $trashed->map(fn (MasterOption $m) => [$m->id, $m->name, $m->description, $m->deleted_at])->all()
        );

        return self::SUCCESS;
    }
}
