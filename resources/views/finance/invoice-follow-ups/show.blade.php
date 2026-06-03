@extends('layouts.app')

@section('title', 'Invoice Follow Up Details')
@section('breadcrumb', 'Home / Finance / Invoice Follow Ups / Details')

@section('content')
<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Invoice Follow Up Details</h1>
            </div>
            <div class="flex flex-row gap-2">
                <a href="{{ route('finance.invoice-follow-ups.edit', $followUp->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </a>
                <a href="{{ route('finance.invoice-follow-ups.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
        
        <!-- Details Container -->
        <div class="w-full bg-white rounded-b-[10px] p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Invoice Information -->
                <div class="form-group">
                    <label class="form-label">Invoice Number</label>
                    <div class="form-control-static">{{ $followUp->invoice->invoice_number ?? 'N/A' }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Customer</label>
                    <div class="form-control-static">{{ $followUp->invoice->company_name ?? 'N/A' }}</div>
                </div>

                <!-- Follow Up Information -->
                <div class="form-group">
                    <label class="form-label">Follow Up Date</label>
                    <div class="form-control-static">{{ $followUp->follow_up_date ? \Carbon\Carbon::parse($followUp->follow_up_date)->format('d/M/Y') : 'N/A' }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Follow Up Type</label>
                    <div class="form-control-static">{{ ucfirst($followUp->follow_up_type ?? 'N/A') }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div class="form-control-static">
                        @if($followUp->status == 'pending')
                            <span class="status-badge status-pending">Pending</span>
                        @elseif($followUp->status == 'completed')
                            <span class="status-badge status-completed">Completed</span>
                        @elseif($followUp->status == 'cancelled')
                            <span class="status-badge status-cancelled">Cancelled</span>
                        @else
                            <span class="status-badge status-pending">{{ ucfirst($followUp->status ?? 'Pending') }}</span>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Created By</label>
                    <div class="form-control-static">{{ $followUp->creator->name ?? 'N/A' }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Created At</label>
                    <div class="form-control-static">{{ $followUp->created_at->format('d/M/Y H:i') }}</div>
                </div>

                @if($followUp->updater)
                <div class="form-group">
                    <label class="form-label">Updated By</label>
                    <div class="form-control-static">{{ $followUp->updater->name }}</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Updated At</label>
                    <div class="form-control-static">{{ $followUp->updated_at->format('d/M/Y H:i') }}</div>
                </div>
                @endif
            </div>

            <!-- Notes -->
            <div class="form-group mt-6">
                <label class="form-label">Notes</label>
                <div class="form-control-static bg-gray-50 p-4 rounded border">
                    {{ $followUp->notes ?? 'No notes available' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
