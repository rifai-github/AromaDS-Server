<?php

namespace App\Services\System;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CatalystMigrationRunService
{
    public function tableExists(): bool
    {
        return Schema::hasTable('catalyst_migration_runs');
    }

    public function hasActiveRun(): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        return DB::table('catalyst_migration_runs')
            ->whereIn('status', ['pending', 'running'])
            ->exists();
    }

    public function activeRun(): ?object
    {
        if (!$this->tableExists()) {
            return null;
        }

        return DB::table('catalyst_migration_runs')
            ->whereIn('status', ['pending', 'running'])
            ->orderByDesc('id')
            ->first();
    }

    public function recentRuns(int $limit = 12): Collection
    {
        if (!$this->tableExists()) {
            return collect();
        }

        return DB::table('catalyst_migration_runs')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function find(int $runId): ?object
    {
        if (!$this->tableExists()) {
            return null;
        }

        return DB::table('catalyst_migration_runs')->where('id', $runId)->first();
    }

    public function createPendingRun(string $actionKey, array $definition, ?int $requestedBy = null): object
    {
        if (!$this->tableExists()) {
            throw new RuntimeException('Tabel catalyst_migration_runs belum tersedia. Jalankan migration lebih dulu.');
        }

        if ($this->hasActiveRun()) {
            $active = $this->activeRun();

            throw new RuntimeException(
                'Masih ada migrasi Catalyst yang aktif'
                . ($active ? ' (#' . $active->id . ' - ' . $active->label . ').' : '.')
            );
        }

        $runId = DB::table('catalyst_migration_runs')->insertGetId([
            'action_key' => $actionKey,
            'label' => (string) ($definition['label'] ?? $actionKey),
            'execution' => (string) ($definition['execution'] ?? 'background'),
            'status' => 'pending',
            'mode' => (string) ($definition['mode'] ?? 'background'),
            'log_path' => $definition['log_path'] ?? null,
            'requested_by' => $requestedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->find($runId);
    }

    public function markSpawned(int $runId, ?int $pid = null, ?string $logPath = null): void
    {
        $payload = ['updated_at' => now()];

        if ($pid !== null) {
            $payload['pid'] = $pid;
        }

        if ($logPath !== null) {
            $payload['log_path'] = $logPath;
        }

        DB::table('catalyst_migration_runs')->where('id', $runId)->update($payload);
    }

    public function markRunning(int $runId, ?string $step = null, ?string $message = null): void
    {
        DB::table('catalyst_migration_runs')->where('id', $runId)->update([
            'status' => 'running',
            'current_step' => $step,
            'progress_message' => $message,
            'started_at' => now(),
            'last_heartbeat_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function heartbeat(int $runId, ?string $step = null, ?string $message = null, ?int $batchId = null): void
    {
        $payload = [
            'last_heartbeat_at' => now(),
            'updated_at' => now(),
        ];

        if ($step !== null) {
            $payload['current_step'] = $step;
        }

        if ($message !== null) {
            $payload['progress_message'] = $message;
        }

        if ($batchId !== null) {
            $payload['batch_id'] = $batchId;
        }

        DB::table('catalyst_migration_runs')->where('id', $runId)->update($payload);
    }

    public function attachBackup(int $runId, array $backup): void
    {
        DB::table('catalyst_migration_runs')->where('id', $runId)->update([
            'backup_path' => $backup['path'] ?? null,
            'backup_sha256' => $backup['sha256'] ?? null,
            'backup_size' => $backup['size'] ?? null,
            'updated_at' => now(),
        ]);
    }

    public function attachBatch(int $runId, int $batchId): void
    {
        DB::table('catalyst_migration_runs')->where('id', $runId)->update([
            'batch_id' => $batchId,
            'updated_at' => now(),
        ]);
    }

    public function markCompleted(int $runId, array $summary = [], ?string $output = null): void
    {
        DB::table('catalyst_migration_runs')->where('id', $runId)->update([
            'status' => 'completed',
            'summary' => $summary === [] ? null : json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'output' => $output,
            'finished_at' => now(),
            'last_heartbeat_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markFailed(int $runId, string $errorMessage, array $summary = [], ?string $output = null): void
    {
        DB::table('catalyst_migration_runs')->where('id', $runId)->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'summary' => $summary === [] ? null : json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'output' => $output,
            'finished_at' => now(),
            'last_heartbeat_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
