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

<div class="catalyst-shell">
    <div class="catalyst-panel">
        <div class="catalyst-header">
            <div>
                <h1 class="catalyst-title">Catalyst Import Console</h1>
                <p class="catalyst-subtitle">
                    Halaman operator untuk import master data dari database staging SQL Server `PinkAds` ke schema KGI.
                    Restore `.bak` tetap dilakukan di SQL Server/SSMS, lalu sinkronisasi master dan relasi dijalankan dari sini.
                </p>
            </div>
            <div class="catalyst-inline-actions">
                <span class="catalyst-badge"><i class="fas fa-database"></i> {{ $sourceConfig['database'] ?: 'No Source DB' }}</span>
                <a href="{{ route('system.backup-restore.index') }}" class="btn btn-secondary">Backup / Restore</a>
            </div>
        </div>

        <div class="catalyst-body">
                <div class="catalyst-note">
                Flow aman yang kita pakai sekarang:
                restore `.bak` ke SQL Server `PinkAds` dulu, jalankan `Dry Run`, kalau hasilnya bersih baru `Apply Import`,
                lalu lanjut `Post-Import Sync` untuk isi relasi warehouse, product relation, dan rental details.
            </div>

            <div class="catalyst-note" style="border-color:#fde68a;background:#fffdf3;">
                Prioritas migrasi saat ini:
                `Warehouse` untuk master product, master rental, relasi warehouse, dan rental detail;
                `System` untuk branch, department, dan user.
                `Role` sengaja <strong>tidak</strong> diimport dari Catalyst dan tetap mengikuti konfigurasi role milik aplikasi KGI.
            </div>

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
                        <h2 class="catalyst-card-title">
                            {{
                                $groupKey === 'warehouse' ? 'Warehouse Sync' :
                                ($groupKey === 'system' ? 'System Sync' :
                                ($groupKey === 'users' ? 'Users Import Tools' :
                                ($groupKey === 'tools' ? 'Warehouse Tools' : 'Catalyst Actions')))
                            }}
                        </h2>
                        <div class="catalyst-action-grid">
                            @foreach($groupActions as $action)
                                <div class="catalyst-action-card">
                                    <h3>{{ $action['label'] }}</h3>
                                    <p>{{ $action['description'] }}</p>
                                    <form method="POST" action="{{ route('system.catalyst-import.run') }}">
                                        @csrf
                                        <input type="hidden" name="action" value="{{ $action['key'] }}">
                                        <button type="submit" class="btn {{ str_contains($action['key'], 'apply') || str_contains($action['key'], 'sync') ? 'btn-primary' : 'btn-secondary' }}">
                                            {{ $action['label'] }}
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
@endsection
