@extends('layouts.app')

@section('title', 'View Job Assign Schedule - Operational')
@section('breadcrumb', 'Home / Operational / Job Assign Schedule / View')

@section('content')
<div class="w-full">
    <!-- Header dengan judul dan button -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">View Job Assign Schedule</p>
        </div>
        
        <div class="flex flex-row gap-2">
            <a href="{{ route('operational.job-assign-schedules.edit', $jobAssignSchedule->id) }}" class="btn-primary">
                <i class="fas fa-edit text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Edit Assignment</span>
            </a>
            <a href="{{ route('operational.job-assign-schedules.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Back to List</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        
        <!-- Basic Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Team -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->team->name ?? 'N/A' }}
                    </p>
                </div>

                <!-- Building -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Building</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->building->name ?? 'N/A' }}
                    </p>
                </div>

                <!-- Room -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->room->room_name ?? 'N/A' }}
                    </p>
                </div>

                <!-- Job Type -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Job Type</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ ucwords(str_replace('_', ' ', $jobAssignSchedule->job_type)) ?? 'N/A' }}
                    </p>
                </div>

                <!-- Status -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $jobAssignSchedule->status == 'completed' ? 'bg-green-100 text-green-800' : 
                           ($jobAssignSchedule->status == 'in_progress' ? 'bg-blue-100 text-blue-800' : 
                           ($jobAssignSchedule->status == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                        {{ ucwords(str_replace('_', ' ', $jobAssignSchedule->status ?? 'N/A')) }}
                    </span>
                </div>

                <!-- Priority -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $jobAssignSchedule->priority == 'high' ? 'bg-red-100 text-red-800' : 
                           ($jobAssignSchedule->priority == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                        {{ ucfirst($jobAssignSchedule->priority ?? 'N/A') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Schedule Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Schedule Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Schedule Date -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Date</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->schedule_date ? \Carbon\Carbon::parse($jobAssignSchedule->schedule_date)->format('d/mmm/Y') : 'N/A' }}
                    </p>
                </div>

                <!-- Start Time -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->start_time ? \Carbon\Carbon::parse($jobAssignSchedule->start_time)->format('H:i') : 'N/A' }}
                    </p>
                </div>

                <!-- End Time -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->end_time ? \Carbon\Carbon::parse($jobAssignSchedule->end_time)->format('H:i') : 'N/A' }}
                    </p>
                </div>

                <!-- Estimated Duration -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estimated Duration</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->estimated_duration ? $jobAssignSchedule->estimated_duration . ' hours' : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Job Details Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Job Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Required Materials -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Required Materials</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->required_materials ?? 'No materials specified' }}
                    </p>
                </div>

                <!-- Special Instructions -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md min-h-[60px]">
                        {{ $jobAssignSchedule->special_instructions ?? 'No special instructions' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Additional Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Description -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md min-h-[60px]">
                        {{ $jobAssignSchedule->description ?? 'No description available' }}
                    </p>
                </div>

                <!-- Notes -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md min-h-[60px]">
                        {{ $jobAssignSchedule->notes ?? 'No notes available' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- System Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Created At -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Created At</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->created_at ? $jobAssignSchedule->created_at->format('d/mmm/Y, H:i:s') : 'N/A' }}
                    </p>
                </div>

                <!-- Updated At -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Updated</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $jobAssignSchedule->updated_at ? $jobAssignSchedule->updated_at->format('d/mmm/Y, H:i:s') : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200">
            <a href="{{ route('operational.job-assign-schedules.index') }}" class="btn-secondary">
                <i class="fas fa-list mr-2"></i>
                Back to List
            </a>
            <a href="{{ route('operational.job-assign-schedules.edit', $jobAssignSchedule->id) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i>
                Edit Assignment
            </a>
        </div>
    </div>
</div>

<style>
.content-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
    background-color: #1a365d;
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
}

.info-group {
    margin-bottom: 1rem;
}

.info-group label {
    font-weight: 500;
    color: #374151;
}

.info-group p {
    margin: 0;
    word-wrap: break-word;
}
</style>
@endsection
