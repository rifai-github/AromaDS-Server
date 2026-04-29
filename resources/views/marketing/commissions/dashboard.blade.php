@extends('layouts.app')

@section('title', 'My Commission Dashboard')
@section('breadcrumb', 'Home / Marketing / My Commission')

@section('content')
<style>
    html, body { overflow-x: hidden; max-width: 100vw; }
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-primary { background-color: #214589; color: white; }
    .btn-info { background-color: #3b82f6; color: white; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
    .page-title { font-size: 28px; font-weight: 700; margin: 0; }
    .page-subtitle { font-size: 16px; opacity: 0.9; margin: 8px 0 0 0; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
    .stat-value { font-size: 32px; font-weight: 700; color: #1f2937; margin: 0; }
    .stat-label { font-size: 14px; color: #6b7280; margin: 4px 0 0 0; }
    .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 20px; }
    .card-header { background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
    .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin: 0; }
    .card-body { padding: 24px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th { background: #f1f5f9; color: #475569; font-weight: 600; padding: 12px 16px; text-align: left; border-bottom: 2px solid #e2e8f0; }
    .table td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #1f2937; }
    .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .badge-success { background-color: #dcfce7; color: #16a34a; }
    .badge-warning { background-color: #fef3c7; color: #d97706; }
    .badge-danger { background-color: #fee2e2; color: #dc2626; }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="container-fluid">
            <h1 class="page-title"><i class="fas fa-chart-line"></i> My Commission Dashboard</h1>
            <p class="page-subtitle">View your commission summary and achievements</p>
        </div>
    </div>

    <div class="container-fluid">
        @if(!$currentPeriod)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ $message ?? 'No active achievement period found.' }}
        </div>
        @else
        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">Rp {{ number_format($summary['total_commission'] ?? 0, 0, ',', '.') }}</div>
                <div class="stat-label">Total Commission</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Rp {{ number_format($summary['pending_commission'] ?? 0, 0, ',', '.') }}</div>
                <div class="stat-label">Pending Commission</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $summary['total_contracts'] ?? 0 }}</div>
                <div class="stat-label">Total Contracts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $summary['installed_contracts'] ?? 0 }}</div>
                <div class="stat-label">Installed Contracts</div>
            </div>
        </div>

        <!-- Targets -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">New Contract Target</h3>
                    </div>
                    <div class="card-body">
                        @if($newTarget)
                            <p><strong>Target:</strong> Rp {{ number_format($newTarget->target_amount, 0, ',', '.') }}</p>
                            <p><strong>Achieved:</strong> Rp {{ number_format($newTarget->achieved_amount, 0, ',', '.') }}</p>
                            @php
                                $progress = $newTarget->target_amount > 0 ? ($newTarget->achieved_amount / $newTarget->target_amount) * 100 : 0;
                            @endphp
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ min($progress, 100) }}%">
                                    {{ number_format($progress, 1) }}%
                                </div>
                            </div>
                        @else
                            <p class="text-muted">No target set for new contracts</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Renewal Contract Target</h3>
                    </div>
                    <div class="card-body">
                        @if($renewalTarget)
                            <p><strong>Target:</strong> Rp {{ number_format($renewalTarget->target_amount, 0, ',', '.') }}</p>
                            <p><strong>Achieved:</strong> Rp {{ number_format($renewalTarget->achieved_amount, 0, ',', '.') }}</p>
                            @php
                                $progress = $renewalTarget->target_amount > 0 ? ($renewalTarget->achieved_amount / $renewalTarget->target_amount) * 100 : 0;
                            @endphp
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ min($progress, 100) }}%">
                                    {{ number_format($progress, 1) }}%
                                </div>
                            </div>
                        @else
                            <p class="text-muted">No target set for renewal contracts</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Commission Calculations -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Commission Calculations</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                            <th>Contract</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Last Updated By</th>
                            <th>Last Updated At</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($commissions as $commission)
                            <tr>
                                <td>{{ $commission->contract->contract_number ?? 'N/A' }}</td>
                                <td>{{ $commission->contract->customer->name ?? 'N/A' }}</td>
                                <td><strong>Rp {{ number_format($commission->final_amount ?? 0, 0, ',', '.') }}</strong></td>
                                <td>
                                    @if($commission->status == 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($commission->status == 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-danger">Void</span>
                                    @endif
                                </td>
                                <td>{{ $commission->calculation_date ? $commission->calculation_date->format('d M Y') : '-' }}</td>
                            <td>{{ $commission->createdBy->name ?? '-' }}</td>
                            <td>
                                <div>
                                    <strong>{{ $commission->created_at ? $commission->created_at->format('d M Y') : '-' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $commission->created_at ? $commission->created_at->format('H:i') : '' }}</small>
                                </div>
                            </td>
                            <td>{{ $commission->updatedBy->name ?? '-' }}</td>
                            <td>
                                <div>
                                    <strong>{{ $commission->updated_at ? $commission->updated_at->format('d M Y') : '-' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $commission->updated_at ? $commission->updated_at->format('H:i') : '' }}</small>
                                </div>
                            </td>
                                <td>
                                    <a href="{{ route('marketing.commissions.details', $commission->id) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <p>No commission calculations found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

