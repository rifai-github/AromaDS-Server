@extends('layouts.app')

@section('title', 'Create Achievement')
@section('breadcrumb', 'Home / Finance / Achievement Management / Create Achievement')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* Button Styles */
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #e5e7eb;
        color: #4b5563;
    }

    /* Page Header */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 0;
        margin-bottom: 30px;
    }

    .page-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }

    .page-subtitle {
        font-size: 16px;
        opacity: 0.9;
        margin: 8px 0 0 0;
    }

    /* Form Styles */
    .form-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .text-danger {
        color: #dc2626;
        font-size: 12px;
        margin-top: 4px;
    }

    .required {
        color: #dc2626;
    }
</style>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Create Achievement</h1>
                <p class="page-subtitle">Create a new achievement for performance tracking</p>
            </div>
            <div>
                <a href="{{ route('finance.achievements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="form-container">
        <form method="POST" action="{{ route('finance.achievements.store') }}">
            @csrf
            
            <div class="form-row">
                <div class="form-group">
                    <label for="user_id" class="form-label">User <span class="required">*</span></label>
                    <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="achievement_period_id" class="form-label">Achievement Period <span class="required">*</span></label>
                    <select name="achievement_period_id" id="achievement_period_id" class="form-control @error('achievement_period_id') is-invalid @enderror" required>
                        <option value="">Select Period</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" {{ old('achievement_period_id') == $period->id ? 'selected' : '' }}>
                                {{ $period->period_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('achievement_period_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="contract_id" class="form-label">Contract</label>
                    <select name="contract_id" id="contract_id" class="form-control @error('contract_id') is-invalid @enderror">
                        <option value="">Select Contract (Optional)</option>
                        @foreach($contracts as $contract)
                            <option value="{{ $contract->id }}" {{ old('contract_id') == $contract->id ? 'selected' : '' }}>
                                {{ $contract->contract_number }} - {{ $contract->customer->name ?? 'Unknown Customer' }}
                            </option>
                        @endforeach
                    </select>
                    @error('contract_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="achievement_type" class="form-label">Achievement Type <span class="required">*</span></label>
                    <select name="achievement_type" id="achievement_type" class="form-control @error('achievement_type') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        <option value="sales" {{ old('achievement_type') == 'sales' ? 'selected' : '' }}>Sales</option>
                        <option value="service" {{ old('achievement_type') == 'service' ? 'selected' : '' }}>Service</option>
                        <option value="installation" {{ old('achievement_type') == 'installation' ? 'selected' : '' }}>Installation</option>
                    </select>
                    @error('achievement_type')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="target_amount" class="form-label">Target Amount <span class="required">*</span></label>
                    <input type="number" step="0.01" name="target_amount" id="target_amount" 
                           class="form-control @error('target_amount') is-invalid @enderror" 
                           value="{{ old('target_amount') }}" required>
                    @error('target_amount')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="achieved_amount" class="form-label">Achieved Amount</label>
                    <input type="number" step="0.01" name="achieved_amount" id="achieved_amount" 
                           class="form-control @error('achieved_amount') is-invalid @enderror" 
                           value="{{ old('achieved_amount', 0) }}">
                    @error('achieved_amount')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                    <input type="number" step="0.01" name="commission_rate" id="commission_rate" 
                           class="form-control @error('commission_rate') is-invalid @enderror" 
                           value="{{ old('commission_rate', 0) }}">
                    @error('commission_rate')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="achievement_date" class="form-label">Achievement Date</label>
                    <input type="date" name="achievement_date" id="achievement_date" 
                           class="form-control @error('achievement_date') is-invalid @enderror" 
                           value="{{ old('achievement_date') }}">
                    @error('achievement_date')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notes</label>
                <textarea name="notes" id="notes" rows="4" 
                          class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Achievement
                </button>
                <a href="{{ route('finance.achievements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-calculate commission amount when achieved amount changes
    $('#achieved_amount, #commission_rate').on('input', function() {
        const achievedAmount = parseFloat($('#achieved_amount').val()) || 0;
        const commissionRate = parseFloat($('#commission_rate').val()) || 0;
        const commissionAmount = (achievedAmount * commissionRate) / 100;
        
        // You can display this in a read-only field if needed
        console.log('Commission Amount:', commissionAmount);
    });
});
</script>
@endpush
