<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title() }}</title>
    <style>
        @page { margin: 18px 18px 26px; }
        /* Helvetica = font bawaan PDF (tak di-embed), file jauh lebih kecil dari DejaVu. */
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8px; color: #1f2937; }
        h1 { font-size: 13px; margin: 0 0 4px; color: #1e3a8a; }
        .meta { font-size: 8px; color: #475569; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e3a8a; color: #fff; padding: 4px 5px; text-align: left; font-weight: bold; }
        td { padding: 3px 5px; border-bottom: 0.5px solid #cbd5e1; vertical-align: top; }
        tr:nth-child(even) td { background: #f1f5f9; }
        tr.hl td { background: #fef3c7; }
        .num { text-align: right; }
        .footer { position: fixed; bottom: -14px; left: 0; right: 0; font-size: 7px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>{{ $report->title() }}</h1>
    <div class="meta">
        {{ $report->dateLabel() }}: {{ $filters->dateFrom->format('d/m/Y') }} – {{ $filters->dateTo->format('d/m/Y') }}
        · Branch: {{ $branchName ?? 'Semua' }}
        @if($filters->search) · Cari: "{{ $filters->search }}" @endif
        · {{ number_format($rows->count(), 0, ',', '.') }} baris
        · Dicetak {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? '-' }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                @foreach($report->columns() as $key => $label)
                    <th class="{{ in_array($key, $report->numericColumns(), true) ? 'num' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr class="{{ $report->highlightRow($row) ? 'hl' : '' }}">
                    <td class="num">{{ $index + 1 }}</td>
                    @foreach($report->columns() as $key => $label)
                        <td class="{{ in_array($key, $report->numericColumns(), true) ? 'num' : '' }}">{{ $row[$key] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($report->columns()) + 1 }}">Tidak ada data untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Aroma ERP · {{ $report->title() }}</div>
</body>
</html>
