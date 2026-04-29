@extends('layouts.app')

@section('title', 'Edit Invoice Follow Up')
@section('breadcrumb', 'Home / Finance / Invoice Follow Ups / Edit')

@section('content')
<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Edit Invoice Follow Up</h1>
            </div>
        </div>
        
        <!-- Form Container -->
        <div class="w-full bg-white rounded-b-[10px] p-6">
            <form action="{{ route('finance.invoice-follow-ups.update', $followUp->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Invoice Selection -->
                    <div class="form-group">
                        <label for="invoice_id" class="form-label">Invoice <span class="text-red-500">*</span></label>
                        <select name="invoice_id" id="invoice_id" class="form-control @error('invoice_id') is-invalid @enderror" required>
                            <option value="">Select Invoice</option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}" {{ old('invoice_id', $followUp->invoice_id) == $invoice->id ? 'selected' : '' }}>
                                    {{ $invoice->invoice_number }} - {{ $invoice->company_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('invoice_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Follow Up Date -->
                    <div class="form-group">
                        <label for="follow_up_date" class="form-label">Follow Up Date <span class="text-red-500">*</span></label>
                        <input type="date" name="follow_up_date" id="follow_up_date" class="form-control @error('follow_up_date') is-invalid @enderror" value="{{ old('follow_up_date', $followUp->follow_up_date) }}" required>
                        @error('follow_up_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Follow Up Type -->
                    <div class="form-group">
                        <label for="follow_up_type" class="form-label">Follow Up Type <span class="text-red-500">*</span></label>
                        <select name="follow_up_type" id="follow_up_type" class="form-control @error('follow_up_type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            @foreach($followUpTypes as $type)
                                <option value="{{ $type }}" {{ old('follow_up_type', $followUp->follow_up_type) == $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                        @error('follow_up_type')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status" class="form-label">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="">Select Status</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ old('status', $followUp->status) == $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group mt-6">
                    <label for="notes" class="form-label">Notes <span class="text-red-500">*</span></label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="4" required>{{ old('notes', $followUp->notes) }}</textarea>
                    @error('notes')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-row justify-end items-center gap-4 mt-8">
                    <a href="{{ route('finance.invoice-follow-ups.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span>Update Follow Up</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>
@endpush
@endsection
