@extends('layouts.app')

@section('title', 'Master Option Details')
@section('breadcrumb', 'Home / Other / Master Options / Details')

@section('content')
<style>
    .detail-container {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .detail-section {
        margin-bottom: 30px;
    }

    .detail-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }

    .detail-row {
        display: flex;
        margin-bottom: 15px;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .detail-label {
        width: 200px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        flex-shrink: 0;
    }

    .detail-value {
        flex: 1;
        font-size: 14px;
        color: #1f2937;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
    }

    .status-active {
        background-color: #dcfce7;
        color: #166534;
    }

    .status-inactive {
        background-color: #fef2f2;
        color: #dc2626;
    }

    .status-system {
        background-color: #fef3c7;
        color: #92400e;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
        color: white;
        text-decoration: none;
    }

    .btn-secondary {
        background-color: #6b7280;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background-color: #4b5563;
        color: white;
        text-decoration: none;
    }

    .options-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .options-table th {
        background-color: #f9fafb;
        padding: 12px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }

    .options-table td {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #1f2937;
    }

    .options-table tr:hover {
        background-color: #f9fafb;
    }

    .no-options {
        text-align: center;
        padding: 40px;
        color: #6b7280;
        font-style: italic;
    }
</style>

<div class="flex flex-col   w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">

        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <a href="{{ route('other.master-options.index') }}" class="btn-secondary mr-3" style="text-decoration: none; display: inline-flex; align-items: center; padding: 8px 16px;">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Master Option Details</p>
            </div>
        </div>

        <!-- Details Container -->
        <div class="detail-container w-full">

            <!-- Basic Info Section -->
            <div class="detail-section">
                <h3 class="detail-section-title">Basic Info</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Nama:</div>
                    <div class="detail-value">{{ $masterOption->name ?? 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value">{{ $masterOption->description ?? 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">System Reserved:</div>
                    <div class="detail-value">
                        <span class="status-badge {{ $masterOption->system_reserved ? 'status-system' : 'status-inactive' }}">
                            {{ $masterOption->system_reserved ? 'Yes' : 'No' }}
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status Active:</div>
                    <div class="detail-value">
                        <span class="status-badge {{ $masterOption->is_active ? 'status-active' : 'status-inactive' }}">
                            {{ $masterOption->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Created At:</div>
                    <div class="detail-value">{{ $masterOption->created_at ? \Carbon\Carbon::parse($masterOption->created_at)->format('d/M/Y H:i') : 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Latest Update:</div>
                    <div class="detail-value">{{ $masterOption->updated_at ? \Carbon\Carbon::parse($masterOption->updated_at)->format('d/M/Y H:i') : 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Update By:</div>
                    <div class="detail-value">{{ $masterOption->updater->name ?? ($masterOption->creator->name ?? 'N/A') }}</div>
                </div>
            </div>

            <!-- Options Section -->
            <div class="detail-section">
                <h3 class="detail-section-title">Options</h3>
                
                @if($masterOption->optionDetails && $masterOption->optionDetails->count() > 0)
                    <table class="options-table">
                        <thead>
                            <tr>
                                <th>Option Name</th>
                                <th>Label</th>
                                <th>Code</th>
                                <th>Latest Update</th>
                                <th>Update By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($masterOption->optionDetails as $optionDetail)
                                <tr>
                                    <td>{{ $optionDetail->option_name ?? 'N/A' }}</td>
                                    <td>{{ $optionDetail->label ?? ($optionDetail->option_name ?? 'N/A') }}</td>
                                    <td>{{ $optionDetail->code ?? '-' }}</td>
                                    <td>{{ $optionDetail->updated_at ? \Carbon\Carbon::parse($optionDetail->updated_at)->format('d/M/Y H:i') : 'N/A' }}</td>
                                    <td>
                                        @if($optionDetail->updater && $optionDetail->updater->name)
                                            {{ $optionDetail->updater->name }}
                                        @elseif($optionDetail->creator && $optionDetail->creator->name)
                                            {{ $optionDetail->creator->name }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-options">
                        <i class="fas fa-info-circle mr-2"></i>
                        No options found for this master option.
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-4 mt-6" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <a href="{{ route('other.master-options.edit', $masterOption->id) }}" class="btn-primary">
                    <i class="fas fa-edit mr-2"></i> Edit
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
