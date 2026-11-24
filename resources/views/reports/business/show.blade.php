@extends('layouts.app')

@section('title', $report->title())
@section('breadcrumb', 'Home / Report / '.$report->title())

@section('content')
<style>
    .br-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.1); margin-bottom: 16px; }
    .br-head { padding: 16px 20px; border-bottom: 2px solid #1e3a8a; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
    .br-head h2 { margin: 0; font-size: 18px; color: #1e3a8a; font-weight: 700; }
    .br-head p { margin: 4px 0 0; color: #64748b; font-size: 13px; max-width: 760px; line-height: 1.45; }
    .br-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .br-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; }
    .br-btn-excel { background: #15803d; color: #fff; }
    .br-btn-pdf { background: #b91c1c; color: #fff; }
    .br-btn-primary { background: #1e3a8a; color: #fff; }
    .br-btn-light { background: #fff; color: #334155; border-color: #cbd5e1; }
    .br-btn:hover { opacity: .9; color: #fff; }
    .br-btn-light:hover { color: #1e3a8a; }
    .br-filters { padding: 14px 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
    .br-filters label { display: block; font-size: 12px; color: #475569; font-weight: 600; margin-bottom: 4px; }
    .br-filters input, .br-filters select { padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; min-width: 150px; background: #fff; }
    .br-filters .br-search { min-width: 220px; }
    .br-meta { padding: 0 20px 12px; font-size: 12px; color: #64748b; }
    .br-table-wrap { overflow-x: auto; }
    .br-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .br-table th { background: #1e3a8a; color: #fff; padding: 10px 12px; text-align: left; white-space: nowrap; font-weight: 600; }
    .br-table td { padding: 9px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .br-table tr:nth-child(even) td { background: #f8fafc; }
    .br-table tr.br-highlight td { background: #fef3c7; }
    .br-table td.num, .br-table th.num { text-align: right; white-space: nowrap; }
    .br-empty { text-align: center; color: #94a3b8; padding: 40px 12px !important; }
    .br-alert { margin: 0 20px 12px; padding: 10px 14px; border-radius: 6px; background: #fdecec; border: 1px solid #f2a7a7; color: #7f1d1d; font-size: 13px; }
    .br-pagination { padding: 12px 20px; border-top: 1px solid #e2e8f0; }
</style>

<div class="br-card">
    <div class="br-head">
        <div>
            <h2>{{ $report->title() }}</h2>
            <p>{{ $report->description() }}</p>
        </div>
        <div class="br-actions">
            <a class="br-btn br-btn-excel" href="{{ route('report.business.export', ['report' => $report->key(), 'format' => 'xlsx'] + $exportQuery) }}">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a class="br-btn br-btn-pdf" href="{{ route('report.business.export', ['report' => $report->key(), 'format' => 'pdf'] + $exportQuery) }}">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="br-alert" style="margin-top: 12px;">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('report.business.show', $report->key()) }}" class="br-filters">
        <div>
            <label for="date_from">{{ $report->dateLabel() }} dari</label>
            <input type="date" id="date_from" name="date_from" value="{{ $filters->dateFrom->toDateString() }}">
        </div>
        <div>
            <label for="date_to">sampai</label>
            <input type="date" id="date_to" name="date_to" value="{{ $filters->dateTo->toDateString() }}">
        </div>
        <div>
            <label for="branch_id">Branch</label>
            <select id="branch_id" name="branch_id" class="no-select2">
                <option value="">Semua branch</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected($filters->branchId === (int) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        @foreach($report->extraFilters() as $key => $filter)
            <div>
                <label for="extra_{{ $key }}">{{ $filter['label'] }}</label>
                <select id="extra_{{ $key }}" name="{{ $key }}" class="no-select2">
                    <option value="">{{ $filter['placeholder'] ?? 'Semua' }}</option>
                    @foreach($filter['options'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters->extra($key) === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div>
            <label for="search">Cari</label>
            <input type="text" id="search" name="search" class="br-search" value="{{ $filters->search }}" placeholder="Nomor, customer, sales...">
        </div>
        <input type="hidden" name="per_page" value="{{ $rows->perPage() }}">
        <div class="br-actions">
            <button type="submit" class="br-btn br-btn-primary"><i class="fas fa-filter"></i> Terapkan</button>
            <a href="{{ route('report.business.show', $report->key()) }}" class="br-btn br-btn-light">Reset</a>
        </div>
    </form>

    <div class="br-meta">
        Menampilkan {{ number_format($rows->total(), 0, ',', '.') }} baris ·
        {{ $report->dateLabel() }} {{ $filters->dateFrom->format('d/m/Y') }} – {{ $filters->dateTo->format('d/m/Y') }}
    </div>

    <div class="br-table-wrap">
        <table class="br-table">
            <thead>
                <tr>
                    <th class="num" style="width: 48px;">No</th>
                    @foreach($report->columns() as $key => $label)
                        <th class="{{ in_array($key, $report->numericColumns(), true) ? 'num' : '' }}">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr class="{{ $report->highlightRow($row) ? 'br-highlight' : '' }}">
                        <td class="num">{{ $rows->firstItem() + $index }}</td>
                        @foreach($report->columns() as $key => $label)
                            <td class="{{ in_array($key, $report->numericColumns(), true) ? 'num' : '' }}">{{ $row[$key] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($report->columns()) + 1 }}" class="br-empty">Tidak ada data untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->total() > 0)
        <div class="br-pagination">{{ $rows->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
