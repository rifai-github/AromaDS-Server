<?php

namespace App\Console\Commands;

use App\Services\System\CatalystMigrationExecutor;
use Illuminate\Console\Command;
use Throwable;

class RunCatalystMigrationAction extends Command
{
    protected $signature = 'catalyst:run-action {runId : catalyst_migration_runs.id}';

    protected $description = 'Execute one background Catalyst migration action';

    public function handle(CatalystMigrationExecutor $executor): int
    {
        try {
            return $executor->execute((int) $this->argument('runId'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
