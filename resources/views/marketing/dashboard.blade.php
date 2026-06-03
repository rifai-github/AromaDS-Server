@php
    // Redirect to commission dashboard or load commission data
    $user = Auth::user();
    $currentPeriod = \App\Models\Finance\AchievementPeriod::current()->first();
    
    // Initialize variables
    $commissions = collect();
    $achievements = collect();
    $totalCommission = 0;
    $pendingCommission = 0;
    $voidCommission = 0;
    $newTarget = null;
    $renewalTarget = null;
    $summary = [
        'total_commission' => 0,
        'pending_commission' => 0,
        'void_commission' => 0,
        'total_contracts' => 0,
        'installed_contracts' => 0,
        'new_contracts' => 0,
        'renewal_contracts' => 0,
    ];
    
    if ($currentPeriod) {
        try {
            $commissions = \App\Models\Finance\CommissionCalculation::where('user_id', $user->id)
                ->where('achievement_period_id', $currentPeriod->id)
                ->with(['contract.customer', 'commissionLevel'])
                ->orderBy('calculation_date', 'desc')
                ->get();
            
            $achievements = \App\Models\Finance\Achievement::where('user_id', $user->id)
                ->where('achievement_period_id', $currentPeriod->id)
                ->with(['contract.customer', 'commissionLevel'])
                ->orderBy('achievement_date', 'desc')
                ->get();
            
            $totalCommission = $commissions->where('status', 'approved')->sum('final_amount') ?? 0;
            $pendingCommission = $commissions->where('status', 'pending')->sum('final_amount') ?? 0;
            $voidCommission = $commissions->where('status', 'void')->sum('final_amount') ?? 0;
            
            $newTarget = \App\Models\Finance\MarketingTarget::where('user_id', $user->id)
                ->where('achievement_period_id', $currentPeriod->id)
                ->where('target_type', 'new')
                ->first();
            
            $renewalTarget = \App\Models\Finance\MarketingTarget::where('user_id', $user->id)
                ->where('achievement_period_id', $currentPeriod->id)
                ->where('target_type', 'renewal')
                ->first();
            
            $summary = [
                'total_commission' => $totalCommission,
                'pending_commission' => $pendingCommission,
                'void_commission' => $voidCommission,
                'total_contracts' => $achievements->count(),
                'installed_contracts' => $achievements->where('is_installed', true)->count(),
                'new_contracts' => $achievements->where('achievement_type', 'new')->count(),
                'renewal_contracts' => $achievements->where('achievement_type', 'renewal')->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error loading commission data in marketing dashboard: ' . $e->getMessage());
        }
    }
@endphp

@extends('layouts.app')

@section('title', 'Marketing Dashboard')
@section('breadcrumb', 'Home / Marketing / Dashboard')

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
    .stat-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
    .stat-icon.primary { background: #dbeafe; color: #1d4ed8; }
    .stat-icon.success { background: #dcfce7; color: #16a34a; }
    .stat-icon.warning { background: #fef3c7; color: #d97706; }
    .stat-icon.info { background: #dbeafe; color: #0ea5e9; }
    .stat-value { font-size: 32px; font-weight: 700; color: #1f2937; margin: 0; }
    .stat-label { font-size: 14px; color: #6b7280; margin: 4px 0 0 0; }
    .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; margin-bottom: 20px; }
    .card-header { background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
    .card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin: 0; }
    .card-body { padding: 24px; }
    .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
    .action-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; text-align: center; transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; text-decoration: none; color: inherit; }
    .action-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); text-decoration: none; color: inherit; }
    .action-icon { font-size: 32px; margin-bottom: 12px; color: #214589; }
    .action-title { font-size: 16px; font-weight: 600; color: #1f2937; margin: 0; }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="container-fluid">
            <h1 class="page-title"><i class="fas fa-bullhorn"></i> Marketing Dashboard</h1>
            <p class="page-subtitle">Welcome back, {{ Auth::user()->name }}!</p>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="{{ route('marketing.commissions.dashboard') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="action-title">My Commission</div>
                    </a>
                    <a href="{{ route('marketing.quotations.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-file-invoice"></i></div>
                        <div class="action-title">Quotations</div>
                    </a>
                    <a href="{{ route('marketing.contracts.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-file-contract"></i></div>
                        <div class="action-title">Contracts</div>
                    </a>
                    <a href="{{ route('marketing.surveys.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="action-title">Surveys</div>
                    </a>
                    <a href="{{ route('marketing.pipeline.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-project-diagram"></i></div>
                        <div class="action-title">Pipeline</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Commission Summary -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Commission Summary - {{ $currentPeriod->period_name ?? 'Current Period' }}</h3>
                <a href="{{ route('marketing.commissions.dashboard') }}" class="btn btn-info btn-sm">
                    <i class="fas fa-external-link-alt"></i> View Full Dashboard
                </a>
            </div>
            <div class="card-body">
                @if($currentPeriod && isset($summary))
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-value">Rp {{ number_format($summary['total_commission'], 0, ',', '.') }}</div>
                            <div class="stat-label">Total Commission</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value">Rp {{ number_format($summary['pending_commission'], 0, ',', '.') }}</div>
                            <div class="stat-label">Pending Commission</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="stat-value">{{ $summary['total_contracts'] }}</div>
                            <div class="stat-label">Total Contracts</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value">{{ $summary['installed_contracts'] }}</div>
                            <div class="stat-label">Installed Contracts</div>
                        </div>
                    </div>
                    
                    @if($newTarget || $renewalTarget)
                    <div class="row mt-4">
                        @if($newTarget)
                        <div class="col-md-6">
                            <h5>New Contract Target</h5>
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
                        </div>
                        @endif
                        @if($renewalTarget)
                        <div class="col-md-6">
                            <h5>Renewal Contract Target</h5>
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
                        </div>
                        @endif
                    </div>
                    @endif
                @else
                    <p class="text-muted">No active achievement period found.</p>
                @endif
            </div>
        </div>
        
        <!-- Recent Commission Calculations -->
        @if($currentPeriod && isset($commissions) && $commissions->count() > 0)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Recent Commission Calculations</h3>
                <a href="{{ route('marketing.commissions.dashboard') }}" class="btn btn-info btn-sm">
                    <i class="fas fa-external-link-alt"></i> View All
                </a>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commissions->take(5) as $commission)
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
                                <td>{{ $commission->calculation_date ? $commission->calculation_date->format('d/M/Y') : '-' }}</td>
                                <td>
                                    <a href="{{ route('marketing.commissions.details', $commission->id) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Contracts</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $recentContracts = \App\Models\Contract::where('marketing_id', Auth::id())
                                ->with('customer')
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp
                        @if($recentContracts->count() > 0)
                            <ul class="list-unstyled">
                                @foreach($recentContracts as $contract)
                                <li class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $contract->contract_number }}</strong><br>
                                            <small class="text-muted">{{ $contract->customer->name ?? 'N/A' }}</small>
                                        </div>
                                        <small class="text-muted">{{ $contract->created_at->format('d/M/Y') }}</small>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('marketing.contracts.index') }}" class="btn btn-primary btn-sm">
                                View All Contracts
                            </a>
                        @else
                            <p class="text-muted">No recent contracts found.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Quotations</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $recentQuotations = \App\Models\Quotation::where('marketing_id', Auth::id())
                                ->with('customer')
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp
                        @if($recentQuotations->count() > 0)
                            <ul class="list-unstyled">
                                @foreach($recentQuotations as $quotation)
                                <li class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $quotation->quotation_number }}</strong><br>
                                            <small class="text-muted">{{ $quotation->customer->name ?? 'N/A' }}</small>
                                        </div>
                                        <small class="text-muted">{{ $quotation->created_at->format('d/M/Y') }}</small>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('marketing.quotations.index') }}" class="btn btn-primary btn-sm">
                                View All Quotations
                            </a>
                        @else
                            <p class="text-muted">No recent quotations found.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

