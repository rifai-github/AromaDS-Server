@extends('layouts.app')

@section('title', 'Tax File Import Details')
@section('breadcrumb', 'Home / Finance / Tax File Imports / Details')

@push('styles')
<style>
    .import-detail-page {
        --import-navy: #214589;
        --import-ink: #172033;
        --import-muted: #64748b;
        --import-line: #e2e8f0;
        --import-surface: #f8fafc;
    }

    .import-detail-card {
        background: #ffffff;
        border: 1px solid rgba(33, 69, 137, 0.08);
        border-radius: 14px;
        box-shadow: 0 10px 28px rgba(33, 69, 137, 0.07);
    }

    .import-detail-kicker {
        color: #5f78a8;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .import-detail-title {
        color: var(--import-navy);
        font-size: clamp(1.25rem, 2vw, 1.65rem);
        font-weight: 700;
        margin: 0.2rem 0 0;
    }

    .import-status-badge,
    .detail-status-badge {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.75rem;
        font-weight: 700;
        gap: 0.4rem;
        line-height: 1;
        padding: 0.5rem 0.75rem;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .import-status-badge::before,
    .detail-status-badge::before {
        background: currentColor;
        border-radius: 999px;
        content: '';
        height: 0.45rem;
        width: 0.45rem;
    }

    .status-completed,
    .status-approved {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-processing {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-failed,
    .status-rejected {
        background: #fee2e2;
        color: #b91c1c;
    }

    .status-warning {
        background: #ffedd5;
        color: #c2410c;
    }

    .import-stat {
        background: linear-gradient(145deg, #ffffff 0%, var(--import-surface) 100%);
        border: 1px solid var(--import-line);
        border-radius: 12px;
        min-height: 108px;
        padding: 1rem;
    }

    .import-stat-label {
        color: var(--import-muted);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .import-stat-value {
        color: var(--import-ink);
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.15;
        margin-top: 0.55rem;
    }

    .import-stat-note {
        color: var(--import-muted);
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .import-section-title {
        color: var(--import-navy);
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
    }

    .import-meta-label {
        color: var(--import-muted);
        font-size: 0.73rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        margin-bottom: 0.3rem;
        text-transform: uppercase;
    }

    .import-meta-value {
        color: var(--import-ink);
        font-size: 0.9rem;
        overflow-wrap: anywhere;
    }

    .import-detail-table-wrap {
        border: 1px solid var(--import-line);
        border-radius: 12px;
        max-height: 560px;
        overflow: auto;
    }

    .import-detail-table {
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
        min-width: 960px;
        width: 100%;
    }

    .import-detail-table th {
        background: #eef4fb;
        border-bottom: 1px solid #d8e3f0;
        color: #405477;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        padding: 0.85rem 1rem;
        position: sticky;
        text-align: left;
        text-transform: uppercase;
        top: 0;
        z-index: 1;
    }

    .import-detail-table td {
        border-bottom: 1px solid #edf2f7;
        color: #334155;
        font-size: 0.82rem;
        padding: 0.9rem 1rem;
        vertical-align: top;
    }

    .import-detail-table tr:last-child td {
        border-bottom: 0;
    }

    .import-detail-table tbody tr:hover {
        background: #f8fbff;
    }

    .import-remarks {
        line-height: 1.45;
        max-width: 420px;
        white-space: normal;
    }

    .import-empty-state {
        color: var(--import-muted);
        padding: 3.5rem 1rem !important;
        text-align: center;
    }

    .import-error-log {
        background: #fff7f7;
        border: 1px solid #fecaca;
        border-radius: 10px;
        color: #991b1b;
        font-size: 0.8rem;
        line-height: 1.55;
        margin: 0;
        max-height: 240px;
        overflow: auto;
        padding: 1rem;
        white-space: pre-wrap;
    }

    @media (max-width: 767px) {
        .import-detail-actions {
            align-items: stretch;
            flex-direction: column;
            width: 100%;
        }

        .import-detail-actions .btn {
            justify-content: center;
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $details = $import->details ?? collect();
    $approvedCount = $details->where('status', 'approved')->count();
    $warningCount = $details->where('status', 'warning')->count();
    $rejectedCount = $details->where('status', 'rejected')->count();
@endphp

<div class="import-detail-page flex flex-col w-full min-h-screen">
    <div class="flex flex-col w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px] gap-4">
        <section class="import-detail-card p-4 md:p-6" aria-labelledby="import-detail-heading">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="import-detail-kicker">Tax File Import</div>
                    <h1 id="import-detail-heading" class="import-detail-title">
                        {{ $import->import_number ?? 'Import Details' }}
                    </h1>
                    <div class="mt-3">
                        <span class="import-status-badge status-{{ $import->status ?? 'pending' }}">
                            {{ ucfirst($import->status ?? 'pending') }}
                        </span>
                    </div>
                </div>

                <div class="import-detail-actions flex flex-row gap-2">
                    @if($import->canDownload())
                        <a href="{{ route('finance.tax-file-imports.download', $import->id) }}" class="btn btn-primary">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            <span>Download Source</span>
                        </a>
                    @endif
                    @if($import->error_log)
                        <a href="{{ route('finance.tax-file-imports.error-log', $import->id) }}" class="btn btn-secondary">
                            <i class="fas fa-file-lines" aria-hidden="true"></i>
                            <span>Download Error Log</span>
                        </a>
                    @endif
                    <a href="{{ route('finance.tax-file-imports.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3" aria-label="Import summary">
            <div class="import-stat">
                <div class="import-stat-label">Total Rows</div>
                <div class="import-stat-value">{{ number_format($import->total_records ?? 0) }}</div>
                <div class="import-stat-note">Rows processed from source file</div>
            </div>
            <div class="import-stat">
                <div class="import-stat-label">Matched Rows</div>
                <div class="import-stat-value">{{ number_format($import->success_count ?? 0) }}</div>
                <div class="import-stat-note">{{ number_format($approvedCount) }} approved · {{ number_format($warningCount) }} warning</div>
            </div>
            <div class="import-stat">
                <div class="import-stat-label">Rejected Rows</div>
                <div class="import-stat-value">{{ number_format($import->failed_count ?? $rejectedCount) }}</div>
                <div class="import-stat-note">No matching invoice found</div>
            </div>
            <div class="import-stat">
                <div class="import-stat-label">Match Rate</div>
                <div class="import-stat-value">{{ $import->formatted_success_rate ?? '0.00%' }}</div>
                <div class="import-stat-note">Matched rows divided by total rows</div>
            </div>
        </section>

        <section class="import-detail-card p-4 md:p-6" aria-labelledby="source-information-heading">
            <div class="flex items-center justify-between gap-3 mb-5">
                <h2 id="source-information-heading" class="import-section-title">Source Information</h2>
                <i class="fas fa-file-import text-[#5f78a8]" aria-hidden="true"></i>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-x-6 gap-y-5">
                <div>
                    <div class="import-meta-label">File Name</div>
                    <div class="import-meta-value">{{ $import->file_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">File Format</div>
                    <div class="import-meta-value">{{ $import->format_label ?? '-' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Import Date</div>
                    <div class="import-meta-value">{{ $import->formatted_import_date ?? '-' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Processed At</div>
                    <div class="import-meta-value">{{ $import->formatted_processed_at ?? '-' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Bank</div>
                    <div class="import-meta-value">{{ $import->bank->bank_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Delimiter</div>
                    <div class="import-meta-value">{{ $import->delimiter_label ?? '-' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Skip Header</div>
                    <div class="import-meta-value">{{ $import->skip_header ? 'Yes' : 'No' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Auto Process</div>
                    <div class="import-meta-value">{{ $import->auto_process ? 'Yes' : 'No' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Created By</div>
                    <div class="import-meta-value">{{ $import->createdBy->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="import-meta-label">Created At</div>
                    <div class="import-meta-value">{{ $import->formatted_created_at ?? '-' }}</div>
                </div>
                <div class="md:col-span-2">
                    <div class="import-meta-label">Notes</div>
                    <div class="import-meta-value">{{ $import->notes ?: '-' }}</div>
                </div>
            </div>
        </section>

        @if($import->error_log)
            <section class="import-detail-card p-4 md:p-6" aria-labelledby="error-log-heading">
                <h2 id="error-log-heading" class="import-section-title mb-4">Processing Error</h2>
                <pre class="import-error-log">{{ $import->error_log }}</pre>
            </section>
        @endif

        <section class="import-detail-card p-4 md:p-6" aria-labelledby="import-results-heading">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 id="import-results-heading" class="import-section-title">Import Results</h2>
                    <p class="text-sm text-slate-500 mt-1 mb-0">Validation outcome for every processed row.</p>
                </div>
                <div class="flex flex-wrap gap-2" aria-label="Result counts">
                    <span class="detail-status-badge status-approved">{{ number_format($approvedCount) }} approved</span>
                    <span class="detail-status-badge status-warning">{{ number_format($warningCount) }} warning</span>
                    <span class="detail-status-badge status-rejected">{{ number_format($rejectedCount) }} rejected</span>
                </div>
            </div>

            <div class="import-detail-table-wrap">
                <table class="import-detail-table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Invoice Number</th>
                            <th scope="col">Tax Number</th>
                            <th scope="col">Tax Date</th>
                            <th scope="col">Tax Amount</th>
                            <th scope="col">Status</th>
                            <th scope="col">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $detail)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-semibold text-slate-700">{{ $detail->invoice_number ?? 'N/A' }}</td>
                                <td>{{ $detail->tax_number ?? 'N/A' }}</td>
                                <td>{{ $detail->formatted_tax_date ?? '-' }}</td>
                                <td>{{ $detail->formatted_tax_amount ?? 'Rp 0' }}</td>
                                <td>
                                    <span class="detail-status-badge status-{{ $detail->status ?? 'pending' }}">
                                        {{ $detail->status_label ?? ucfirst($detail->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td class="import-remarks">{{ $detail->remarks ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="import-empty-state">
                                    <i class="fas fa-inbox text-2xl mb-2" aria-hidden="true"></i>
                                    <div>No import details are available yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
