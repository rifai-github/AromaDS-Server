<?php

namespace App\Console\Commands;

use App\Services\System\CatalystSourceConnectionService;
use Illuminate\Console\Command;
use Throwable;

class TestCatalystSourceConnection extends Command
{
    protected $signature = 'catalyst:test-source-connection';

    protected $description = 'Verify the SQL Server Catalyst source connection';

    public function handle(CatalystSourceConnectionService $sourceConnectionService): int
    {
        try {
            $result = $sourceConnectionService->probe();
        } catch (Throwable $e) {
            $this->error('Koneksi source Catalyst gagal: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Koneksi source Catalyst berhasil.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Driver', $result['driver'] ?? '-'],
                ['Host', $result['host'] ?? '-'],
                ['Port', $result['port'] ?? '-'],
                ['Database', $result['database'] ?? '-'],
                ['Source Tables', number_format((int) ($result['table_count'] ?? 0))],
            ]
        );

        return self::SUCCESS;
    }
}
