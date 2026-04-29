@extends('layouts.app')

@section('title', 'Contract Merge Wizard')
@section('breadcrumb', 'Home / Marketing / Contract / Merge Wizard')

@section('content')
<style>
    /* ==================== MERGE WIZARD STYLES ==================== */
    .wizard-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px 40px;
    }

    /* Progress Steps */
    .wizard-steps {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 36px;
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .wizard-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        flex: 1;
        position: relative;
    }

    .wizard-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        left: calc(50% + 20px);
        right: calc(-50% + 20px);
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }

    .wizard-step.completed:not(:last-child)::after {
        background: #214589;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 15px;
        border: 2px solid #e5e7eb;
        background: white;
        color: #9ca3af;
        z-index: 1;
        transition: all 0.3s ease;
    }

    .wizard-step.active .step-circle {
        border-color: #214589;
        background: #214589;
        color: white;
        box-shadow: 0 0 0 4px rgba(33, 69, 137, 0.15);
    }

    .wizard-step.completed .step-circle {
        border-color: #10b981;
        background: #10b981;
        color: white;
    }

    .step-label {
        font-size: 12px;
        font-weight: 500;
        color: #9ca3af;
        text-align: center;
        max-width: 100px;
    }

    .wizard-step.active .step-label {
        color: #214589;
        font-weight: 600;
    }

    .wizard-step.completed .step-label {
        color: #10b981;
    }

    /* Card */
    .wizard-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .wizard-card-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        padding: 24px 32px;
        color: white;
    }

    .wizard-card-header h2 {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 4px;
    }

    .wizard-card-header p {
        font-size: 14px;
        opacity: 0.8;
        margin: 0;
    }

    .wizard-card-body {
        padding: 32px;
    }

    .wizard-card-footer {
        padding: 20px 32px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    /* Form */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .form-label .required {
        color: #ef4444;
        margin-left: 4px;
    }

    .form-input {
        width: 100%;
        padding: 11px 14px;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        transition: all 0.2s ease;
        background: white;
    }

    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Contract Checklist */
    .contract-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 380px;
        overflow-y: auto;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px;
    }

    .contract-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
    }

    .contract-item:hover {
        border-color: #214589;
        background: #eff6ff;
    }

    .contract-item.selected {
        border-color: #214589;
        background: #eff6ff;
    }

    .contract-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 2px solid #d1d5db;
        cursor: pointer;
        margin-top: 2px;
        flex-shrink: 0;
        accent-color: #214589;
    }

    .contract-item-info {
        flex: 1;
    }

    .contract-item-number {
        font-weight: 700;
        color: #214589;
        font-size: 14px;
    }

    .contract-item-meta {
        font-size: 12px;
        color: #6b7280;
        margin-top: 3px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .meta-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 500;
    }

    .meta-badge.status-active { background: #dcfce7; color: #166534; }
    .meta-badge.status-approved { background: #dbeafe; color: #1e40af; }
    .meta-badge.status-signed { background: #f3e8ff; color: #7e22ce; }
    .meta-badge.rooms { background: #fef3c7; color: #92400e; }
    .meta-badge.end-date { background: #fee2e2; color: #991b1b; }

    /* Selected Summary */
    .selected-summary {
        background: #eff6ff;
        border: 1.5px solid #bfdbfe;
        border-radius: 10px;
        padding: 16px 20px;
        margin-top: 16px;
    }

    .selected-summary-title {
        font-size: 13px;
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 10px;
    }

    .selected-summary-stats {
        display: flex;
        gap: 24px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #214589;
    }

    .stat-label {
        font-size: 11px;
        color: #6b7280;
        margin-top: 2px;
    }

    /* Preview Table */
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .preview-table th {
        background: #214589;
        color: white;
        padding: 10px 14px;
        text-align: left;
        font-weight: 600;
    }

    .preview-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #e5e7eb;
        color: #374151;
    }

    .preview-table tr:hover td {
        background: #f9fafb;
    }

    /* Warning box */
    .warning-box {
        background: #fff7ed;
        border: 1.5px solid #fed7aa;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .warning-box .warning-icon {
        color: #f97316;
        font-size: 20px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .warning-box .warning-text {
        font-size: 13px;
        color: #431407;
        line-height: 1.6;
    }

    /* Grid */
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media (max-width: 640px) {
        .grid-2 { grid-template-columns: 1fr; }
        .selected-summary-stats { flex-wrap: wrap; }
    }

    /* Buttons */
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: #214589;
        color: white;
    }

    .btn-primary:hover:not(:disabled) {
        background: #1e3a8a;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(33, 69, 137, 0.3);
    }

    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-secondary {
        background: white;
        color: #6b7280;
        border: 1.5px solid #d1d5db;
    }

    .btn-secondary:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover:not(:disabled) {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-success:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Loading spinner */
    .spinner {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* Loader overlay */
    .loader-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 16px;
    }

    .loader-overlay.show {
        display: flex;
    }

    .loader-box {
        background: white;
        border-radius: 12px;
        padding: 32px 40px;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .loader-spinner {
        width: 48px;
        height: 48px;
        border: 4px solid #e5e7eb;
        border-top-color: #214589;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 16px;
    }

    /* Section panels */
    .step-panel {
        display: none;
    }

    .step-panel.active {
        display: block;
    }

    /* Info hint */
    .info-hint {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
    }

    .empty-state svg {
        margin: 0 auto 12px;
        opacity: 0.4;
    }
</style>

<div class="wizard-container">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: white; border-radius: 12px; padding: 20px 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: #214589; margin: 0 0 4px;">
                <svg style="display:inline;vertical-align:middle;margin-right:8px;" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3M16 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
                Merge Contract Wizard
            </h1>
            <p style="font-size: 13px; color: #6b7280; margin: 0;">Gabungkan beberapa contract lama menjadi 1 contract baru</p>
        </div>
        <a href="{{ route('marketing.contracts.index') }}" class="btn btn-secondary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Kembali
        </a>
    </div>

    <!-- Progress Steps -->
    <div class="wizard-steps">
        <div class="wizard-step active" id="step-indicator-1">
            <div class="step-circle">1</div>
            <span class="step-label">Pilih Customer</span>
        </div>
        <div class="wizard-step" id="step-indicator-2">
            <div class="step-circle">2</div>
            <span class="step-label">Pilih Contract</span>
        </div>
        <div class="wizard-step" id="step-indicator-3">
            <div class="step-circle">3</div>
            <span class="step-label">Info Contract Baru</span>
        </div>
        <div class="wizard-step" id="step-indicator-4">
            <div class="step-circle">4</div>
            <span class="step-label">Review & Submit</span>
        </div>
    </div>

    <!-- STEP 1: Pilih Customer -->
    <div class="step-panel active" id="step-panel-1">
        <div class="wizard-card">
            <div class="wizard-card-header">
                <h2>Step 1: Pilih Customer</h2>
                <p>Pilih customer yang contractnya akan di-merge</p>
            </div>
            <div class="wizard-card-body">
                <div class="form-group">
                    <label class="form-label">Customer <span class="required">*</span></label>
                    <select class="form-input" id="customer_id" name="customer_id">
                        <option value="">-- Pilih Customer --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <p class="info-hint">Pilih customer yang memiliki beberapa contract aktif yang ingin digabungkan</p>
                </div>
            </div>
            <div class="wizard-card-footer">
                <div></div>
                <button class="btn btn-primary" id="btn-step1-next" onclick="goToStep2()" disabled>
                    Lanjut: Pilih Contract
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- STEP 2: Pilih Source Contracts -->
    <div class="step-panel" id="step-panel-2">
        <div class="wizard-card">
            <div class="wizard-card-header">
                <h2>Step 2: Pilih Contract yang Akan Di-Merge</h2>
                <p>Pilih minimal 1 contract lama yang akan digabungkan ke contract baru</p>
            </div>
            <div class="wizard-card-body">
                <div class="warning-box">
                    <span class="warning-icon">⚠️</span>
                    <div class="warning-text">
                        <strong>Perhatian:</strong> Contract yang dipilih akan <strong>di-terminate otomatis</strong> dengan status <code>term-renew</code>.
                        Semua job schedule outstanding dari contract tersebut akan <strong>di-cancel secara otomatis</strong>.
                        Rooms dan rentals akan di-copy ke contract baru.
                    </div>
                </div>

                <div id="contract-list-loading" style="text-align:center;padding:20px;display:none;">
                    <div class="loader-spinner" style="width:32px;height:32px;border-width:3px;"></div>
                    <p style="margin-top:8px;color:#6b7280;font-size:13px;">Memuat daftar contract...</p>
                </div>

                <div class="contract-list" id="contract-list">
                    <div class="empty-state">
                        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
                        <p>Pilih customer terlebih dahulu untuk melihat daftar contract</p>
                    </div>
                </div>

                <div class="selected-summary" id="selected-summary" style="display:none;">
                    <div class="selected-summary-title">📊 Ringkasan Contract yang Dipilih</div>
                    <div class="selected-summary-stats">
                        <div class="stat-item">
                            <div class="stat-value" id="total-contracts-selected">0</div>
                            <div class="stat-label">Contract</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="total-rooms">0</div>
                            <div class="stat-label">Total Rooms</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="total-rentals">0</div>
                            <div class="stat-label">Total Rentals</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value text-red-600" id="total-jobs-cancel">0</div>
                            <div class="stat-label">Jobs Dibatalkan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-card-footer">
                <button class="btn btn-secondary" onclick="goToStep(1)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Kembali
                </button>
                <button class="btn btn-primary" id="btn-step2-next" onclick="goToStep3()" disabled>
                    Lanjut: Isi Info Contract Baru
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- STEP 3: Info Contract Baru -->
    <div class="step-panel" id="step-panel-3">
        <div class="wizard-card">
            <div class="wizard-card-header">
                <h2>Step 3: Info Contract Baru</h2>
                <p>Isi detail untuk contract baru hasil merge</p>
            </div>
            <div class="wizard-card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Branch <span class="required">*</span></label>
                        <select class="form-input" id="branch_id" name="branch_id">
                            <option value="">-- Pilih Branch --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->code }} &mdash; {{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <p class="info-hint">Contoh nomor: <code>JKT-CA/26-03/0001</code></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Marketing PIC <span class="required">*</span></label>
                        <select class="form-input" id="marketing_id" name="marketing_id">
                            <option value="">-- Pilih Marketing --</option>
                            @foreach(\App\Models\User::where('is_active', true)->orderBy('name')->get(['id', 'name']) as $user)
                                <option value="{{ $user->id }}"{{ Auth::id() == $user->id ? ' selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Contract <span class="required">*</span></label>
                        <input type="date" class="form-input" id="contract_date" name="contract_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" class="form-input" id="start_date" name="start_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date <span class="required">*</span></label>
                        <input type="date" class="form-input" id="end_date" name="end_date">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea class="form-input" id="notes" name="notes" rows="3" placeholder="Catatan untuk contract baru ini..."></textarea>
                    <p class="info-hint">Nomor contract digenerate otomatis sesuai pakem: <code>[BRANCH]-CA/[YY]-[MM]/[NNNN]</code></p>
                </div>
            </div>
            <div class="wizard-card-footer">
                <button class="btn btn-secondary" onclick="goToStep(2)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Kembali
                </button>
                <button class="btn btn-primary" onclick="goToStep4()">
                    Lanjut: Review
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- STEP 4: Review & Submit -->
    <div class="step-panel" id="step-panel-4">
        <div class="wizard-card">
            <div class="wizard-card-header">
                <h2>Step 4: Review & Submit</h2>
                <p>Periksa kembali sebelum melakukan merge</p>
            </div>
            <div class="wizard-card-body">
                <div class="warning-box" style="background:#fef2f2;border-color:#fecaca;">
                    <span class="warning-icon">🔴</span>
                    <div class="warning-text" style="color:#450a0a;">
                        <strong>Tindakan ini tidak dapat dibatalkan!</strong><br>
                        Setelah submit, contract yang dipilih akan di-terminate dengan status <code>term-renew</code>
                        dan semua job schedule outstanding akan di-cancel otomatis.
                    </div>
                </div>

                <!-- Review Summary -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px;">
                        <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.5px;">✅ Contract Baru</div>
                        <div id="review-new-contract" style="font-size:13px;color:#374151;line-height:1.8;"></div>
                    </div>
                    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:16px;">
                        <div style="font-size:12px;font-weight:700;color:#9a3412;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.5px;">⚠️ Contract Yang Di-Terminate</div>
                        <div id="review-source-contracts" style="font-size:13px;color:#374151;line-height:1.8;"></div>
                    </div>
                </div>

                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:16px;margin-bottom:20px;">
                    <div style="font-size:12px;font-weight:700;color:#1e40af;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.5px;">📋 Ringkasan Operasi</div>
                    <div id="review-stats" style="display:flex;gap:24px;flex-wrap:wrap;">
                        <div class="stat-item">
                            <div class="stat-value" id="rev-rooms">-</div>
                            <div class="stat-label">Rooms yang Di-Copy</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="rev-rentals">-</div>
                            <div class="stat-label">Rentals yang Di-Copy</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color:#ef4444;" id="rev-jobs">-</div>
                            <div class="stat-label">Jobs Dibatalkan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-card-footer">
                <button class="btn btn-secondary" onclick="goToStep(3)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Kembali
                </button>
                <button class="btn btn-success" id="btn-submit" onclick="submitMerge()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Eksekusi Merge
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loader Overlay -->
<div class="loader-overlay" id="loader-overlay">
    <div class="loader-box">
        <div class="loader-spinner"></div>
        <p style="color:#374151;font-weight:600;font-size:15px;margin:0;">Sedang memproses merge...</p>
        <p style="color:#6b7280;font-size:13px;margin:8px 0 0;">Mohon tunggu, jangan refresh halaman</p>
    </div>
</div>

<script>
// State management
const state = {
    customerId: null,
    customerName: '',
    selectedContractIds: [],
    selectedContracts: [],
    contractDate: '',
    startDate: '',
    endDate: '',
    marketingId: null,
    marketingName: '',
    notes: '',
    previewData: null,
};

// ===================== STEP NAVIGATION =====================
function goToStep(step) {
    // Hide all panels
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.wizard-step').forEach((s, i) => {
        s.classList.remove('active');
        if (i + 1 < step) s.classList.add('completed');
        else s.classList.remove('completed');
    });

    document.getElementById(`step-panel-${step}`).classList.add('active');
    document.getElementById(`step-indicator-${step}`).classList.add('active');

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function goToStep2() {
    const sel = document.getElementById('customer_id');
    state.customerId = sel.value;
    state.customerName = sel.options[sel.selectedIndex]?.text || '';
    if (!state.customerId) return;

    goToStep(2);
    await loadMergeCandidates();
}

async function goToStep3() {
    if (state.selectedContractIds.length === 0) {
        alert('Pilih minimal 1 contract untuk di-merge.');
        return;
    }
    goToStep(3);
}

async function goToStep4() {
    // Validate step 3 fields
    const contractDate = document.getElementById('contract_date').value;
    const startDate    = document.getElementById('start_date').value;
    const endDate      = document.getElementById('end_date').value;
    const marketingId  = document.getElementById('marketing_id').value;
    const branchId     = document.getElementById('branch_id').value;

    if (!contractDate || !startDate || !endDate || !marketingId || !branchId) {
        alert('Harap lengkapi semua field yang wajib diisi (Branch, Tanggal Contract, Start Date, End Date, Marketing PIC).');
        return;
    }

    if (new Date(endDate) <= new Date(startDate)) {
        alert('End Date harus setelah Start Date.');
        return;
    }

    // Save to state
    state.contractDate  = contractDate;
    state.startDate     = startDate;
    state.endDate       = endDate;
    state.marketingId   = marketingId;
    state.branchId      = branchId;
    state.branchCode    = document.getElementById('branch_id').options[document.getElementById('branch_id').selectedIndex]?.text.split(' — ')[0] || '';
    state.marketingName = document.getElementById('marketing_id').options[document.getElementById('marketing_id').selectedIndex]?.text || '';
    state.notes         = document.getElementById('notes').value;

    // Load preview
    await loadMergePreview();
    goToStep(4);
    renderReview();
}

// ===================== DATA LOADING =====================
async function loadMergeCandidates() {
    const listEl = document.getElementById('contract-list');
    const loadingEl = document.getElementById('contract-list-loading');

    listEl.style.display = 'none';
    loadingEl.style.display = 'block';

    try {
        const resp = await fetch(`{{ route('marketing.contracts.merge-candidates') }}?customer_id=${state.customerId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await resp.json();

        loadingEl.style.display = 'none';
        listEl.style.display = 'block';

        if (!data.success || !data.data.length) {
            listEl.innerHTML = `<div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
                <p>Tidak ada contract aktif yang bisa di-merge untuk customer ini.</p>
                <p style="font-size:12px;color:#d1d5db;">Contract harus berstatus: active, approved, atau signed</p>
            </div>`;
            return;
        }

        // Store contracts data
        state.allContracts = data.data;
        renderContractList(data.data);
    } catch (e) {
        loadingEl.style.display = 'none';
        listEl.style.display = 'block';
        listEl.innerHTML = `<div class="empty-state"><p style="color:#ef4444;">Gagal memuat data: ${e.message}</p></div>`;
    }
}

function renderContractList(contracts) {
    const listEl = document.getElementById('contract-list');
    listEl.innerHTML = contracts.map(c => `
        <label class="contract-item" id="contract-item-${c.id}">
            <input type="checkbox" value="${c.id}" onchange="onContractCheck(this, ${JSON.stringify(c).replace(/"/g, '&quot;')})" />
            <div class="contract-item-info">
                <div class="contract-item-number">${c.contract_number}</div>
                <div class="contract-item-meta">
                    <span class="meta-badge status-${c.contract_status}">${c.contract_status}</span>
                    <span class="meta-badge rooms">🏠 ${c.rooms_count} rooms</span>
                    <span class="meta-badge rooms">📦 ${c.rentals_count} rentals</span>
                    <span class="meta-badge end-date">📅 s/d ${c.end_date}</span>
                </div>
            </div>
        </label>
    `).join('');

    // Reset selected states
    state.selectedContractIds = [];
    state.selectedContracts = [];
    updateSelectedSummary();
}

function onContractCheck(checkbox, contract) {
    const itemEl = document.getElementById(`contract-item-${contract.id}`);

    if (checkbox.checked) {
        state.selectedContractIds.push(contract.id);
        state.selectedContracts.push(contract);
        itemEl.classList.add('selected');
    } else {
        state.selectedContractIds = state.selectedContractIds.filter(id => id !== contract.id);
        state.selectedContracts = state.selectedContracts.filter(c => c.id !== contract.id);
        itemEl.classList.remove('selected');
    }

    updateSelectedSummary();
}

function updateSelectedSummary() {
    const summaryEl = document.getElementById('selected-summary');
    const btnNext = document.getElementById('btn-step2-next');

    if (state.selectedContractIds.length === 0) {
        summaryEl.style.display = 'none';
        btnNext.disabled = true;
        return;
    }

    summaryEl.style.display = 'block';
    btnNext.disabled = false;

    const totalRooms = state.selectedContracts.reduce((s, c) => s + c.rooms_count, 0);
    const totalRentals = state.selectedContracts.reduce((s, c) => s + c.rentals_count, 0);

    document.getElementById('total-contracts-selected').textContent = state.selectedContractIds.length;
    document.getElementById('total-rooms').textContent = totalRooms;
    document.getElementById('total-rentals').textContent = totalRentals;
    document.getElementById('total-jobs-cancel').textContent = '?'; // akan diisi setelah preview
}

async function loadMergePreview() {
    try {
        const payload = { source_contract_ids: state.selectedContractIds };
        const resp = await fetch('{{ route("marketing.contracts.merge-preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.success) {
            state.previewData = data.data;
            // Update summary with job cancel count
            document.getElementById('total-jobs-cancel').textContent = data.data.totals.jobs_to_cancel;
        }
    } catch (e) {
        console.warn('Preview load failed:', e);
    }
}

// ===================== REVIEW =====================
function renderReview() {
    const marketingName = document.getElementById('marketing_id')
        .options[document.getElementById('marketing_id').selectedIndex]?.text || '-';

    const yr = new Date().toISOString().slice(2,4);
    const mo = String(new Date().getMonth()+1).padStart(2,'0');
    const previewNum = state.branchCode ? `${state.branchCode}-CA/${yr}-${mo}/XXXX` : '[BRANCH]-CA/YY-MM/XXXX';

    document.getElementById('review-new-contract').innerHTML = `
        <b>Nomor (preview):</b> <span style="font-family:monospace;background:#f1f5f9;padding:2px 6px;border-radius:4px;">${previewNum}</span><br>
        <b>Customer:</b> ${state.customerName}<br>
        <b>Branch:</b> ${state.branchCode || '-'}<br>
        <b>Tanggal:</b> ${formatDate(state.contractDate)}<br>
        <b>Periode:</b> ${formatDate(state.startDate)} &ndash; ${formatDate(state.endDate)}<br>
        <b>Marketing:</b> ${marketingName}
    `;

    document.getElementById('review-source-contracts').innerHTML = state.selectedContracts
        .map(c => `<div>• <b>${c.contract_number}</b> (${c.contract_status})</div>`)
        .join('') || '-';

    if (state.previewData) {
        document.getElementById('rev-rooms').textContent = state.previewData.totals.rooms;
        document.getElementById('rev-rentals').textContent = state.previewData.totals.rentals;
        document.getElementById('rev-jobs').textContent = state.previewData.totals.jobs_to_cancel;
    } else {
        const totalRooms = state.selectedContracts.reduce((s, c) => s + c.rooms_count, 0);
        const totalRentals = state.selectedContracts.reduce((s, c) => s + c.rentals_count, 0);
        document.getElementById('rev-rooms').textContent = totalRooms;
        document.getElementById('rev-rentals').textContent = totalRentals;
        document.getElementById('rev-jobs').textContent = '?';
    }
}

// ===================== SUBMIT =====================
async function submitMerge() {
    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div> Memproses...';
    document.getElementById('loader-overlay').classList.add('show');

    try {
        const payload = {
            customer_id:         state.customerId,
            source_contract_ids: state.selectedContractIds,
            branch_id:           state.branchId,
            contract_date:       state.contractDate,
            start_date:          state.startDate,
            end_date:            state.endDate,
            marketing_id:        state.marketingId,
            notes:               state.notes,
        };

        const resp = await fetch('{{ route("marketing.contracts.merge-wizard.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });

        const data = await resp.json();
        document.getElementById('loader-overlay').classList.remove('show');

        if (data.success) {
            // Success alert
            const stats = data.stats;
            const msg = `✅ Merge berhasil!\n\n` +
                `Contract Baru: ${data.contract_number}\n` +
                `Contract Di-merge: ${stats.source_contracts_merged}\n` +
                `Rooms Di-copy: ${stats.rooms_copied}\n` +
                `Rentals Di-copy: ${stats.rentals_copied}\n` +
                `Jobs Dibatalkan: ${stats.jobs_cancelled}\n\n` +
                `Klik OK untuk melihat contract baru.`;

            alert(msg);
            window.location.href = data.redirect_url;
        } else {
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Eksekusi Merge';

            if (data.errors) {
                alert('❌ Validasi gagal:\n\n' + Object.values(data.errors).flat().join('\n'));
            } else {
                alert('❌ Gagal: ' + (data.message || 'Terjadi kesalahan.'));
            }
        }
    } catch (e) {
        document.getElementById('loader-overlay').classList.remove('show');
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Eksekusi Merge';
        alert('❌ Error: ' + e.message);
    }
}

// ===================== HELPERS =====================
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

// INIT
document.addEventListener('DOMContentLoaded', function () {
    // Enable next button when customer is selected
    // Support both: native change (fallback) + Select2 events
    function onCustomerChange() {
        const val = document.getElementById('customer_id').value;
        document.getElementById('btn-step1-next').disabled = !val;
    }

    // Native change (untuk select biasa)
    document.getElementById('customer_id').addEventListener('change', onCustomerChange);

    // Select2 events (jika layout mengaktifkan Select2)
    if (typeof $ !== 'undefined') {
        $('#customer_id').on('select2:select select2:clear', onCustomerChange);
    }

    // Set default end date 1 year from today
    const today = new Date();
    const nextYear = new Date(today);
    nextYear.setFullYear(today.getFullYear() + 1);
    document.getElementById('end_date').value = nextYear.toISOString().split('T')[0];
});
</script>
@endsection
