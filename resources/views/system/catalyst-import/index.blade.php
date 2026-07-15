@extends('layouts.app')

@section('title', 'Catalyst Import - System')
@section('breadcrumb', 'Home / System / Catalyst Import')

@section('content')
<style>
    .catalyst-shell {
        width: 100%;
        max-width: 96%;
        margin: 0 auto 32px;
    }

    .catalyst-panel {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .catalyst-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }

    .catalyst-title {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #214589;
    }

    .catalyst-subtitle {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.6;
        max-width: 860px;
    }

    .catalyst-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        background: #eef2ff;
        color: #214589;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
    }

    .catalyst-body {
        padding: 20px 22px 24px;
        display: grid;
        gap: 18px;
    }

    .catalyst-note {
        border: 1px solid #dbeafe;
        background: #f8fbff;
        color: #334155;
        border-radius: 12px;
        padding: 16px 18px;
        font-size: 13px;
        line-height: 1.7;
    }

    .catalyst-active-run {
        border: 1px solid #bfdbfe;
        background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
        border-radius: 14px;
        padding: 18px;
        display: grid;
        gap: 14px;
    }

    .catalyst-active-run-head {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .catalyst-active-run-title {
        margin: 0;
        font-size: 18px;
        color: #0f172a;
        font-weight: 700;
    }

    .catalyst-active-run-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }

    .catalyst-mini-card {
        border: 1px solid #dbeafe;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.88);
        padding: 12px 14px;
    }

    .catalyst-mini-label {
        color: #64748b;
        font-size: 11px;
        margin: 0 0 4px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .catalyst-mini-value {
        margin: 0;
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        word-break: break-word;
    }

    .catalyst-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .catalyst-stat {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .catalyst-stat-label {
        color: #64748b;
        font-size: 12px;
        margin: 0 0 6px;
    }

    .catalyst-stat-value {
        color: #0f172a;
        font-size: 24px;
        font-weight: 700;
        margin: 0;
    }

    .catalyst-source {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .catalyst-source-card,
    .catalyst-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        padding: 16px 18px;
    }

    .catalyst-card-title {
        margin: 0 0 10px;
        color: #214589;
        font-size: 16px;
        font-weight: 700;
    }

    .catalyst-source-line {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 13px;
        padding: 5px 0;
        border-bottom: 1px dashed #e5e7eb;
    }

    .catalyst-source-line:last-child {
        border-bottom: 0;
    }

    .catalyst-source-key {
        color: #64748b;
    }

    .catalyst-source-value {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }

    .catalyst-action-groups {
        display: grid;
        gap: 16px;
    }

    .catalyst-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 12px;
    }

    .catalyst-action-card {
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        padding: 16px;
        background: #fff;
    }

    .catalyst-action-card.is-disabled {
        opacity: 0.62;
        background: #f8fafc;
    }

    .catalyst-action-card h3 {
        margin: 0 0 8px;
        font-size: 15px;
        color: #0f172a;
    }

    .catalyst-action-card p {
        margin: 0 0 14px;
        font-size: 13px;
        color: #64748b;
        line-height: 1.6;
        min-height: 62px;
    }

    .catalyst-action-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 0 0 12px;
    }

    .catalyst-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .catalyst-chip.warning {
        background: #fff7ed;
        color: #c2410c;
    }

    .catalyst-confirm {
        display: grid;
        gap: 8px;
        margin-bottom: 12px;
    }

    .catalyst-confirm label {
        font-size: 12px;
        font-weight: 700;
        color: #92400e;
    }

    .catalyst-confirm input {
        border: 1px solid #f59e0b;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 13px;
    }

    .catalyst-confirm small {
        color: #92400e;
        font-size: 11px;
        line-height: 1.5;
    }

    .catalyst-output {
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        font-size: 12px;
        line-height: 1.65;
        overflow: auto;
        max-height: 420px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .catalyst-table-wrap {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }

    .catalyst-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    .catalyst-table th,
    .catalyst-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px;
        vertical-align: top;
    }

    .catalyst-table th {
        background: #214589;
        color: #fff;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
    }

    .catalyst-table tr:hover td {
        background: #f8fbff;
    }

    .catalyst-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
    }

    .catalyst-status.completed { background: #dcfce7; color: #166534; }
    .catalyst-status.running { background: #fef3c7; color: #92400e; }
    .catalyst-status.failed { background: #fee2e2; color: #b91c1c; }
    .catalyst-status.pending { background: #e2e8f0; color: #334155; }

    .catalyst-inline-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
</style>

@php
    $groupTitles = [
        'migration' => 'Migration Actions',
        'warehouse' => 'Warehouse Sync',
        'system' => 'System Sync',
        'users' => 'Users Import Tools',
        'post_import' => 'Post-Import',
        'tools' => 'Warehouse Tools',
    ];
@endphp

<div class="catalyst-shell">
    <div class="catalyst-panel">
        <div class="catalyst-header">
            <div>
                <h1 class="catalyst-title">Catalyst Import Console</h1>
                <p class="catalyst-subtitle">
                    Halaman operator untuk import master data dan data transaksi dari SQL Server `PinkAds` ke schema KGI.
                    Restore `.bak` tetap dilakukan di SQL Server/SSMS, lalu migrasi ke MySQL QA dijalankan dari sini.
                </p>
            </div>
            <div class="catalyst-inline-actions">
                <span class="catalyst-badge"><i class="fas fa-database"></i> {{ $sourceConfig['database'] ?: 'No Source DB' }}</span>
                <a href="{{ route('system.backup-restore.index') }}" class="btn btn-secondary">Backup / Restore</a>
            </div>
        </div>

        <div class="catalyst-body">
            <div class="catalyst-note">
                Flow aman untuk migrasi besar sekarang:
                restore `.bak` ke SQL Server `PinkAds` dulu,
                jalankan `Check Source Connection`,
                lanjut `Full Migration Dry Run`,
                lalu kalau hasilnya aman jalankan `Backup + Full Migration Apply`.
                Action migrasi besar berjalan di background supaya request web tidak timeout.
            </div>

            <div class="catalyst-note" style="border-color:#fde68a;background:#fffdf3;">
                Role system tetap mengikuti konfigurasi aplikasi KGI.
                Action `Backup + Full Migration Apply` otomatis membuat backup MySQL target sebelum import mulai,
                dan satu waktu hanya satu migrasi background yang boleh berjalan.
            </div>

            @if($activeRun)
                <div class="catalyst-active-run" id="catalyst-active-run" data-status-url="{{ route('system.catalyst-import.status') }}" data-run-id="{{ $activeRun['id'] }}">
                    <div class="catalyst-active-run-head">
                        <div>
                            <h2 class="catalyst-active-run-title">Run Aktif #<span id="active-run-id">{{ $activeRun['id'] }}</span> - <span id="active-run-label">{{ $activeRun['label'] }}</span></h2>
                            <p class="catalyst-subtitle" style="margin-top:8px;">
                                Halaman ini auto-refresh status setiap 15 detik selama migrasi berjalan.
                            </p>
                        </div>
                        <span class="catalyst-status {{ strtolower($activeRun['status']) }}" id="active-run-status">{{ strtoupper($activeRun['status']) }}</span>
                    </div>

                    <div class="catalyst-active-run-meta">
                        <div class="catalyst-mini-card">
                            <p class="catalyst-mini-label">Step</p>
                            <p class="catalyst-mini-value" id="active-run-step">{{ $activeRun['current_step'] ?: '-' }}</p>
                        </div>
                        <div class="catalyst-mini-card">
                            <p class="catalyst-mini-label">Progress</p>
                            <p class="catalyst-mini-value" id="active-run-progress">{{ $activeRun['progress_message'] ?: '-' }}</p>
                        </div>
                        <div class="catalyst-mini-card">
                            <p class="catalyst-mini-label">Batch</p>
                            <p class="catalyst-mini-value" id="active-run-batch">{{ $activeRun['batch_id'] ? '#' . $activeRun['batch_id'] : '-' }}</p>
                        </div>
                        <div class="catalyst-mini-card">
                            <p class="catalyst-mini-label">Heartbeat</p>
                            <p class="catalyst-mini-value" id="active-run-heartbeat">{{ $activeRun['last_heartbeat_at'] ?: '-' }}</p>
                        </div>
                        <div class="catalyst-mini-card">
                            <p class="catalyst-mini-label">Backup</p>
                            <p class="catalyst-mini-value" id="active-run-backup">{{ $activeRun['backup_path'] ?: '-' }}</p>
                        </div>
                        <div class="catalyst-mini-card">
                            <p class="catalyst-mini-label">Log</p>
                            <p class="catalyst-mini-value" id="active-run-log">{{ $activeRun['log_path'] ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="catalyst-grid">
                <div class="catalyst-stat">
                    <p class="catalyst-stat-label">Imported Products</p>
                    <p class="catalyst-stat-value">{{ number_format($metrics['imported_products']) }}</p>
                </div>
                <div class="catalyst-stat">
                    <p class="catalyst-stat-label">Product-Warehouse Links</p>
                    <p class="catalyst-stat-value">{{ number_format($metrics['product_warehouse_links']) }}</p>
                </div>
                <div class="catalyst-stat">
                    <p class="catalyst-stat-label">Imported Rentals</p>
                    <p class="catalyst-stat-value">{{ number_format($metrics['imported_rentals']) }}</p>
                </div>
                <div class="catalyst-stat">
                    <p class="catalyst-stat-label">Imported Users</p>
                    <p class="catalyst-stat-value">{{ number_format($metrics['imported_users']) }}</p>
                </div>
                <div class="catalyst-stat">
                    <p class="catalyst-stat-label">Rental Details</p>
                    <p class="catalyst-stat-value">{{ number_format($metrics['rental_details']) }}</p>
                </div>
                <div class="catalyst-stat">
                    <p class="catalyst-stat-label">Products With Brand/Variant</p>
                    <p class="catalyst-stat-value">{{ number_format($metrics['products_with_brand_variant']) }}</p>
                </div>
            </div>

            <div class="catalyst-source">
                <div class="catalyst-source-card">
                    <h2 class="catalyst-card-title">Source Connection</h2>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Host</span><span class="catalyst-source-value">{{ $sourceConfig['host'] }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Port</span><span class="catalyst-source-value">{{ $sourceConfig['port'] }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Database</span><span class="catalyst-source-value">{{ $sourceConfig['database'] }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">User</span><span class="catalyst-source-value">{{ $sourceConfig['username'] }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Encrypt</span><span class="catalyst-source-value">{{ $sourceConfig['encrypt'] }}</span></div>
                </div>

                <div class="catalyst-source-card">
                    <h2 class="catalyst-card-title">Warehouse Export Cache</h2>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Exists</span><span class="catalyst-source-value">{{ $sourceConfig['warehouse_export_exists'] ? 'Yes' : 'No' }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Path</span><span class="catalyst-source-value">{{ $sourceConfig['warehouse_export_path'] }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Size</span><span class="catalyst-source-value">{{ $sourceConfig['warehouse_export_size'] ? number_format($sourceConfig['warehouse_export_size']) . ' bytes' : '-' }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Updated</span><span class="catalyst-source-value">{{ $sourceConfig['warehouse_export_mtime'] ?: '-' }}</span></div>
                </div>

                <div class="catalyst-source-card">
                    <h2 class="catalyst-card-title">Users Export Cache</h2>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Exists</span><span class="catalyst-source-value">{{ $sourceConfig['users_export_exists'] ? 'Yes' : 'No' }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Path</span><span class="catalyst-source-value">{{ $sourceConfig['users_export_path'] }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Size</span><span class="catalyst-source-value">{{ $sourceConfig['users_export_size'] ? number_format($sourceConfig['users_export_size']) . ' bytes' : '-' }}</span></div>
                    <div class="catalyst-source-line"><span class="catalyst-source-key">Updated</span><span class="catalyst-source-value">{{ $sourceConfig['users_export_mtime'] ?: '-' }}</span></div>
                </div>
            </div>

            <div class="catalyst-action-groups">
                @foreach($actions as $groupKey => $groupActions)
                    <div class="catalyst-card">
                        <h2 class="catalyst-card-title">{{ $groupTitles[$groupKey] ?? 'Catalyst Actions' }}</h2>
                        <div class="catalyst-action-grid">
                            @foreach($groupActions as $action)
                                @php
                                    $isBackgroundAction = ($action['execution'] ?? 'sync') === 'background';
                                    $requiresConfirmation = (bool) ($action['requires_confirmation'] ?? false);
                                    $disabled = $hasActiveRun && $isBackgroundAction;
                                    $buttonClass = $isBackgroundAction || str_contains($action['key'], 'apply') || str_contains($action['key'], 'sync')
                                        ? 'btn btn-primary'
                                        : 'btn btn-secondary';
                                @endphp

                                <div class="catalyst-action-card {{ $disabled ? 'is-disabled' : '' }}">
                                    <h3>{{ $action['label'] }}</h3>
                                    <p>{{ $action['description'] }}</p>

                                    <div class="catalyst-action-meta">
                                        <span class="catalyst-chip">{{ $isBackgroundAction ? 'Background' : 'Sync Request' }}</span>
                                        @if($requiresConfirmation)
                                            <span class="catalyst-chip warning">Perlu Konfirmasi</span>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('system.catalyst-import.run') }}">
                                        @csrf
                                        <input type="hidden" name="action" value="{{ $action['key'] }}">

                                        @if($requiresConfirmation)
                                            <div class="catalyst-confirm">
                                                <label for="confirmation_{{ $action['key'] }}">Ketik {{ $action['confirmation_value'] ?? 'MIGRASI' }}</label>
                                                <input
                                                    id="confirmation_{{ $action['key'] }}"
                                                    type="text"
                                                    name="confirmation"
                                                    placeholder="{{ $action['confirmation_value'] ?? 'MIGRASI' }}"
                                                    {{ $disabled ? 'disabled' : '' }}
                                                >
                                                <small>Action ini akan membuat backup lalu apply migrasi ke database target staging QA.</small>
                                            </div>
                                        @endif

                                        <button type="submit" class="{{ $buttonClass }}" {{ $disabled ? 'disabled' : '' }}>
                                            {{ $disabled ? 'Menunggu Run Aktif Selesai' : $action['label'] }}
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @if(session('catalyst_command_result'))
                @php($commandResult = session('catalyst_command_result'))
                <div class="catalyst-card">
                    <h2 class="catalyst-card-title">Last Command Output</h2>
                    <div class="catalyst-note" style="margin-bottom: 12px;">
                        <strong>{{ $commandResult['label'] }}</strong>
                        | Status: {{ $commandResult['successful'] ? 'Success' : 'Failed' }}
                        | Duration: {{ number_format(($commandResult['duration_ms'] ?? 0) / 1000, 1) }} detik
                    </div>
                    <pre class="catalyst-output">{{ $commandResult['output'] ?? '' }}</pre>
                </div>
            @endif

            <div class="catalyst-card">
                <h2 class="catalyst-card-title">Recent Migration Runs</h2>
                <div class="catalyst-table-wrap">
                    <table class="catalyst-table">
                        <thead>
                            <tr>
                                <th>Run</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Batch</th>
                                <th>Progress</th>
                                <th>Backup</th>
                                <th>Started</th>
                                <th>Finished</th>
                            </tr>
                        </thead>
                        <tbody id="recent-runs-body">
                            @forelse($recentRuns as $run)
                                <tr>
                                    <td>#{{ $run['id'] }}</td>
                                    <td>{{ $run['label'] }}</td>
                                    <td><span class="catalyst-status {{ strtolower($run['status']) }}">{{ strtoupper($run['status']) }}</span></td>
                                    <td>{{ $run['batch_id'] ? '#' . $run['batch_id'] : '-' }}</td>
                                    <td>{{ $run['progress_message'] ?: ($run['error_message'] ?: '-') }}</td>
                                    <td>{{ $run['backup_path'] ?: '-' }}</td>
                                    <td>{{ $run['started_at'] ?: '-' }}</td>
                                    <td>{{ $run['finished_at'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #64748b;">Belum ada migration run Catalyst yang tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="catalyst-card">
                <h2 class="catalyst-card-title">Recent Catalyst Batches</h2>
                <div class="catalyst-table-wrap">
                    <table class="catalyst-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mode</th>
                                <th>Status</th>
                                <th>Processed</th>
                                <th>Inserted</th>
                                <th>Updated</th>
                                <th>Skipped</th>
                                <th>Failed</th>
                                <th>Started</th>
                                <th>Finished</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                                <tr>
                                    <td>#{{ $batch->id }}</td>
                                    <td>{{ $batch->mode }}</td>
                                    <td><span class="catalyst-status {{ strtolower($batch->status) }}">{{ strtoupper($batch->status) }}</span></td>
                                    <td>{{ number_format($batch->processed) }}</td>
                                    <td>{{ number_format($batch->inserted) }}</td>
                                    <td>{{ number_format($batch->updated) }}</td>
                                    <td>{{ number_format($batch->skipped) }}</td>
                                    <td>{{ number_format($batch->failed) }}</td>
                                    <td>{{ $batch->started_at ?: '-' }}</td>
                                    <td>{{ $batch->finished_at ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" style="text-align: center; color: #64748b;">Belum ada batch Catalyst yang tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="catalyst-card">
                <h2 class="catalyst-card-title">Recent Warning / Error Logs</h2>
                <div class="catalyst-table-wrap">
                    <table class="catalyst-table">
                        <thead>
                            <tr>
                                <th>Batch</th>
                                <th>Step</th>
                                <th>Level</th>
                                <th>Source</th>
                                <th>Message</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>#{{ $log->batch_id }}</td>
                                    <td>{{ $log->step }}</td>
                                    <td>{{ strtoupper($log->level) }}</td>
                                    <td>{{ $log->source_table ?: '-' }}{{ $log->source_key ? ' / ' . $log->source_key : '' }}</td>
                                    <td>{{ $log->message }}</td>
                                    <td>{{ $log->created_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #64748b;">Belum ada warning/error Catalyst.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($activeRun)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('catalyst-active-run');
            if (!container) {
                return;
            }

            const statusUrl = container.dataset.statusUrl;
            const initialRunId = Number(container.dataset.runId || '0');

            const updateText = function (id, value) {
                const node = document.getElementById(id);
                if (node) {
                    node.textContent = value || '-';
                }
            };

            const updateStatusBadge = function (status) {
                const node = document.getElementById('active-run-status');
                if (!node) {
                    return;
                }

                node.className = 'catalyst-status ' + String(status || 'pending').toLowerCase();
                node.textContent = String(status || 'pending').toUpperCase();
            };

            const refresh = async function () {
                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    const activeRun = payload.active_run;

                    if (!activeRun || Number(activeRun.id || 0) !== initialRunId) {
                        window.location.reload();
                        return;
                    }

                    updateStatusBadge(activeRun.status);
                    updateText('active-run-id', activeRun.id ? String(activeRun.id) : '-');
                    updateText('active-run-label', activeRun.label);
                    updateText('active-run-step', activeRun.current_step);
                    updateText('active-run-progress', activeRun.progress_message);
                    updateText('active-run-batch', activeRun.batch_id ? '#' + activeRun.batch_id : '-');
                    updateText('active-run-heartbeat', activeRun.last_heartbeat_at);
                    updateText('active-run-backup', activeRun.backup_path);
                    updateText('active-run-log', activeRun.log_path);

                    if (String(activeRun.status).toLowerCase() !== 'running' && String(activeRun.status).toLowerCase() !== 'pending') {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Catalyst status refresh failed', error);
                }
            };

            setInterval(refresh, 15000);
        });
    </script>
@endif
@endsection
