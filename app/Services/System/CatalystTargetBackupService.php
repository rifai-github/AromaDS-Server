<?php

namespace App\Services\System;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class CatalystTargetBackupService
{
    public function createMysqlBackup(string $label): array
    {
        $connection = (array) config('database.connections.' . config('database.default', 'mysql'), []);
        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException('Database target default belum terkonfigurasi.');
        }

        $directory = storage_path('app/catalyst/backups');
        File::ensureDirectoryExists($directory);

        $filename = sprintf(
            'aroma_qa_before_%s_%s.sql.gz',
            $this->slug($label),
            now()->format('Ymd_His')
        );

        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $gz = gzopen($path, 'wb9');

        if ($gz === false) {
            throw new RuntimeException('File backup gzip tidak bisa dibuat: ' . $path);
        }

        $command = ['mysqldump', '--single-transaction', '--quick', '--skip-lock-tables', '--default-character-set=utf8mb4'];

        if (!empty($connection['unix_socket'])) {
            $command[] = '--socket=' . $connection['unix_socket'];
        } else {
            $command[] = '--host=' . ($connection['host'] ?? '127.0.0.1');
            $command[] = '--port=' . (string) ($connection['port'] ?? 3306);
        }

        if (!empty($connection['username'])) {
            $command[] = '--user=' . $connection['username'];
        }

        $command[] = $database;

        $env = [];
        if (!empty($connection['password'])) {
            $env['MYSQL_PWD'] = (string) $connection['password'];
        }

        $stderr = '';
        $process = new Process($command, base_path(), $env);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run(function (string $type, string $buffer) use ($gz, &$stderr): void {
            if ($type === Process::ERR) {
                $stderr .= $buffer;
                return;
            }

            gzwrite($gz, $buffer);
        });

        gzclose($gz);

        if (!$process->isSuccessful()) {
            @unlink($path);
            throw new RuntimeException(trim($stderr) !== '' ? trim($stderr) : 'mysqldump gagal dijalankan.');
        }

        clearstatcache(true, $path);

        return [
            'path' => $path,
            'size' => is_file($path) ? filesize($path) : 0,
            'sha256' => is_file($path) ? hash_file('sha256', $path) : null,
        ];
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: 'catalyst_migration';
        return trim($value, '_');
    }
}
