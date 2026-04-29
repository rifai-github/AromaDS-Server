@extends('layouts.app')

@section('title', 'Backup (Import/Export) - System')
@section('breadcrumb', 'Home / System / Backup (Import/Export)')

@section('content')
<style>
    .backup-page-shell {
        width: 100%;
        max-width: 96%;
        margin: 0 auto 32px;
    }

    .backup-panel {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .backup-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .backup-panel-title {
        font-size: 20px;
        font-weight: 700;
        color: #214589;
        margin: 0;
    }

    .backup-panel-subtitle {
        font-size: 13px;
        color: #6b7280;
        margin: 4px 0 0;
    }

    .backup-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eef2ff;
        color: #214589;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 600;
    }

    .backup-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 18px 20px 0;
        flex-wrap: wrap;
    }

    .backup-toolbar-note {
        flex: 1 1 320px;
        padding: 14px 16px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #dbeafe;
        color: #334155;
        font-size: 13px;
        line-height: 1.6;
    }

    .backup-global-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .backup-group-wrap {
        padding: 20px;
    }

    .backup-group {
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        margin-bottom: 16px;
        overflow: hidden;
        background: #fff;
    }

    .backup-group summary {
        list-style: none;
        cursor: pointer;
        padding: 16px 18px;
        background: linear-gradient(135deg, #214589 0%, #2857aa 100%);
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }

    .backup-group summary::-webkit-details-marker {
        display: none;
    }

    .backup-group-title {
        font-size: 17px;
        font-weight: 700;
        margin: 0;
    }

    .backup-group-desc {
        font-size: 12px;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.9);
        margin-top: 4px;
    }

    .backup-group-body {
        padding: 18px;
        background: #f8fafc;
    }

    .backup-group-rules {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 16px;
    }

    .backup-rule {
        display: block;
        font-size: 12px;
        color: #475569;
        line-height: 1.6;
        margin-top: 4px;
    }

    .backup-table-wrap {
        overflow-x: auto;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
    }

    .backup-card-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: collapse;
    }

    .backup-card-table th,
    .backup-card-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: top;
        font-size: 13px;
    }

    .backup-card-table th {
        background: #214589;
        color: #fff;
        font-weight: 600;
        white-space: nowrap;
    }

    .backup-card-table tbody tr:hover {
        background: #eff6ff;
    }

    .backup-card-table th:last-child {
        position: sticky;
        right: 0;
        z-index: 3;
        width: 340px;
        min-width: 340px;
        background: #214589;
        box-shadow: -10px 0 14px rgba(15, 23, 42, 0.12);
    }

    .backup-card-table td:last-child {
        position: sticky;
        right: 0;
        z-index: 2;
        width: 340px;
        min-width: 340px;
        background: #fff;
        box-shadow: -10px 0 14px rgba(15, 23, 42, 0.08);
    }

    .backup-card-table tbody tr:hover td:last-child {
        background: #eff6ff;
    }

    .backup-note {
        display: block;
        color: #4b5563;
        font-size: 12px;
        line-height: 1.5;
        margin-top: 4px;
    }

    .backup-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-width: 316px;
        justify-content: flex-start;
    }

    .backup-actions form {
        margin: 0;
    }

    .backup-empty {
        padding: 32px 20px;
        text-align: center;
        color: #6b7280;
    }

    .btn {
        padding: 8px 14px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: #214589;
        color: #fff;
    }

    .btn-secondary {
        background: #f3f4f6;
        color: #4b5563;
        border: 1px solid #d1d5db;
    }

    .btn-success {
        background: #0ea5e9;
        color: #fff;
    }

    .btn-danger {
        background: #dc2626;
        color: #fff;
    }
</style>

<div class="backup-page-shell">
    <div class="backup-panel">
        <div class="backup-panel-header">
            <div>
                <h1 class="backup-panel-title">Backup (Import/Export)</h1>
                <p class="backup-panel-subtitle">Pusat backup seluruh aplikasi dengan tampilan per grup modul besar agar lebih ringkas dan aman dipakai.</p>
            </div>
            <span class="backup-pill"><i class="fas fa-shield-alt"></i> {{ count($modules) }} Modul</span>
        </div>

        <div class="backup-toolbar">
            <div class="backup-toolbar-note">
                <strong>Catatan aman umum:</strong> tombol global di kanan memproses banyak modul sekaligus. Untuk <code>Download Template All</code> dan <code>Export All</code>, file yang diunduh berbentuk <code>ZIP</code>. Untuk <code>Import All</code>, upload file <code>ZIP</code> hasil export/template yang sudah diisi. Tombol <code>Delete All</code> memproses modul satu per satu dengan urutan aman dan bisa melakukan soft delete pada modul yang mendukungnya.
            </div>

            <div class="backup-global-actions">
                @if(auth()->user()->hasPermission('system.backup-restore.import'))
                    <a href="{{ route('system.catalyst-import.index') }}" class="btn btn-secondary">
                        <i class="fas fa-database"></i> Catalyst Import
                    </a>
                @endif
                @if(auth()->user()->hasPermission('system.backup-restore.template'))
                    <a href="{{ route('system.backup-restore.template-all') }}" class="btn btn-secondary">
                        <i class="fas fa-file-archive"></i> Download Template All
                    </a>
                @endif

                @if(auth()->user()->hasPermission('system.backup-restore.export'))
                    <a href="{{ route('system.backup-restore.export-all') }}" class="btn btn-primary">
                        <i class="fas fa-file-export"></i> Export All
                    </a>
                @endif

                @if(auth()->user()->hasPermission('system.backup-restore.import'))
                    <form action="{{ route('system.backup-restore.import-all') }}" method="POST" enctype="multipart/form-data" id="import-all-form">
                        @csrf
                        <input type="file" name="file" id="import-all-file" accept=".zip" style="display:none" onchange="submitImportAll()">
                        <button type="button" class="btn btn-success" onclick="pickImportAllFile()">
                            <i class="fas fa-file-import"></i> Import All
                        </button>
                    </form>
                @endif

                @if(auth()->user()->hasPermission('system.backup-restore.delete'))
                    <form action="{{ route('system.backup-restore.destroy-all') }}" method="POST" onsubmit="return confirmDeleteAll()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete All
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="backup-group-wrap">
            @forelse($moduleGroups as $group)
                <details class="backup-group" @if($loop->first) open @endif>
                    <summary>
                        <div>
                            <div class="backup-group-title">{{ $group['label'] }}</div>
                            <div class="backup-group-desc">{{ $group['description'] }}</div>
                        </div>
                        <span class="backup-pill">{{ $group['module_count'] }} modul</span>
                    </summary>

                    <div class="backup-group-body">
                        <div class="backup-group-rules">
                            <div style="font-weight:700; color:#214589;">Rules Aman Restore {{ $group['label'] }}</div>
                            @foreach($group['rules'] as $rule)
                                <span class="backup-rule">{{ $rule }}</span>
                            @endforeach
                        </div>

                        <div class="backup-table-wrap">
                            <table class="backup-card-table">
                                <thead>
                                    <tr>
                                        <th style="min-width: 220px;">Module</th>
                                        <th style="min-width: 320px;">Description & Notes</th>
                                        <th style="min-width: 170px;">Records</th>
                                        <th style="min-width: 320px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group['modules'] as $module)
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: #214589;">{{ $module['label'] }}</div>
                                                <span class="backup-note">Route key: <code>{{ $module['key'] }}</code></span>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: #1f2937;">{{ $module['description'] }}</div>
                                                @foreach($module['notes'] as $note)
                                                    <span class="backup-note">{{ $note }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                <span class="backup-pill">{{ $module['record_summary'] }}</span>
                                            </td>
                                            <td>
                                                <div class="backup-actions">
                                                    @if(auth()->user()->hasPermission('system.backup-restore.template'))
                                                        <a href="{{ route('system.backup-restore.template', $module['key']) }}" class="btn btn-secondary">
                                                            <i class="fas fa-file-download"></i> Download Template
                                                        </a>
                                                    @endif

                                                    @if(auth()->user()->hasPermission('system.backup-restore.export'))
                                                        <a href="{{ route('system.backup-restore.export', $module['key']) }}" class="btn btn-primary">
                                                            <i class="fas fa-file-export"></i> Export
                                                        </a>
                                                    @endif

                                                    @if(auth()->user()->hasPermission('system.backup-restore.import'))
                                                        <form action="{{ route('system.backup-restore.import', $module['key']) }}" method="POST" enctype="multipart/form-data" id="import-form-{{ $module['key'] }}">
                                                            @csrf
                                                            <input type="file" name="file" id="import-file-{{ $module['key'] }}" accept=".csv,.txt" style="display:none" onchange="submitImport('{{ $module['key'] }}')">
                                                            <button type="button" class="btn btn-success" onclick="pickImportFile('{{ $module['key'] }}')">
                                                                <i class="fas fa-file-import"></i> Import
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if(auth()->user()->hasPermission('system.backup-restore.delete'))
                                                        <form action="{{ route('system.backup-restore.destroy', $module['key']) }}" method="POST" onsubmit="return confirmDelete('{{ $module['label'] }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-trash"></i> Hapus
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            @empty
                <div class="backup-empty">Tidak ada modul backup yang tersedia.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
    function pickImportFile(moduleKey) {
        const input = document.getElementById(`import-file-${moduleKey}`);
        if (input) {
            input.click();
        }
    }

    function submitImport(moduleKey) {
        const input = document.getElementById(`import-file-${moduleKey}`);
        const form = document.getElementById(`import-form-${moduleKey}`);

        if (!input || !form || !input.files.length) {
            return;
        }

        form.submit();
    }

    function pickImportAllFile() {
        const input = document.getElementById('import-all-file');
        if (input) {
            input.click();
        }
    }

    function submitImportAll() {
        const input = document.getElementById('import-all-file');
        const form = document.getElementById('import-all-form');

        if (!input || !form || !input.files.length) {
            return;
        }

        form.submit();
    }

    function confirmDelete(moduleLabel) {
        return confirm(`Yakin ingin menghapus data modul ${moduleLabel}? Pastikan file backup/export sudah aman.`);
    }

    function confirmDeleteAll() {
        return confirm('Yakin ingin menjalankan Delete All? Aksi ini akan memproses banyak modul sekaligus dan sebagian modul akan di-soft-delete jika didukung.');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const groups = Array.from(document.querySelectorAll('.backup-group'));

        groups.forEach(function (group) {
            group.addEventListener('toggle', function () {
                if (!group.open) {
                    return;
                }

                groups.forEach(function (otherGroup) {
                    if (otherGroup !== group) {
                        otherGroup.open = false;
                    }
                });
            });
        });
    });
</script>
@endsection
