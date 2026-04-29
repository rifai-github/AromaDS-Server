<!DOCTYPE html>
<html>
<head>
    <title>Customer Service Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .page-break { page-break-after: always; }
        
        .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #214589; padding-bottom: 10px; }
        .header-table td { vertical-align: top; }
        
        .logo { width: 150px; }
        
        .title { font-size: 20px; font-weight: bold; color: #214589; text-align: right; }
        .job-number { font-size: 14px; font-weight: bold; text-align: right; margin-top: 5px; }
        
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; }
        .label { font-weight: bold; width: 120px; color: #555; }
        
        .rooms-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .rooms-table th { background-color: #214589; color: white; padding: 8px; text-align: left; }
        .rooms-table td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        
        .center { text-align: center; }
        
        .footer { position: fixed; bottom: 30px; left: 0; right: 0; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    @foreach($groupedJobs as $jobNumber => $schedules)
        @php
            $mainJob = $schedules->first();
            $customerName = $mainJob->jobAdvice->customer->name ?? $mainJob->company_name ?? '-';
            $contractNo = $mainJob->jobAdvice->contract->contract_number ?? '-';
            
            // Collect all technicians and team names from all schedules in this group
            $technicians = collect();
            $teamNames = collect();
            foreach($schedules as $sch) {
                // If selective room printing, ONLY include technicians from schedules 
                // that actually contain the selected rooms.
                if ($selectedRoomIds) {
                    $hasTargetRoom = $sch->jobScheduleRooms->whereIn('id', $selectedRoomIds)->isNotEmpty();
                    if (!$hasTargetRoom) continue;
                }

                if ($sch->assignedTechnician) {
                    $technicians->push($sch->assignedTechnician->name);
                }
                foreach($sch->jobAssignSchedules as $assign) {
                    if ($assign->team) {
                        $teamNames->push($assign->team->team_name);
                    }
                }
            }
            $techName = $technicians->merge($teamNames)->unique()->filter()->implode(', ') ?: '-';

            // Use the building address or building name
            $address = $mainJob->building->address ?? $mainJob->building_name ?? $mainJob->building->building_name ?? '-';
            
            // Use completed_at as the official report date, fallback to schedule_date
            $reportDate = $schedules->max('completed_at') ?? $mainJob->schedule_date;
            $dateFormatted = $reportDate ? \Carbon\Carbon::parse($reportDate)->format('d M Y') : '-';
            
            // Collect Rooms
            $rooms = collect();
            foreach($schedules as $sch) {
                // Determine Type Code
                $typeCode = 'S'; // Default Service
                $jobType = strtolower($sch->type ?? $mainJob->type ?? '');
                if (str_contains($jobType, 'rental') || str_contains($jobType, 'change')) {
                    $typeCode = 'R';
                } elseif (str_contains($jobType, 'complain')) {
                    $typeCode = 'C';
                }

                if($sch->jobScheduleRooms && $sch->jobScheduleRooms->count() > 0) {
                    foreach($sch->jobScheduleRooms as $roomPivot) {
                        // MOM-Fix: Filter selective printing if Room View
                        if ($selectedRoomIds && !in_array($roomPivot->id, $selectedRoomIds)) {
                            continue;
                        }

                        $rooms->push([
                            'item' => $roomPivot->display_rental_name,
                            'name' => $roomPivot->room->room_name ?? 'Unknown Room',
                            'job_no' => $sch->job_number ?? '-', 
                            'period' => $sch->period ?? '-'
                        ]);
                    }
                } elseif ($sch->room) {
                    $rooms->push([
                         'item' => $sch->room->room_type ?? 'General Service',
                         'name' => $sch->room->room_name,
                         'job_no' => $sch->job_number ?? '-',
                         'period' => $sch->period ?? '-'
                    ]);
                } else {
                     $rooms->push([
                        'item' => 'General Service',
                        'name' => 'General Area',
                        'job_no' => $sch->job_number ?? '-',
                         'period' => $sch->period ?? '-'
                    ]);
                }
            }
        @endphp

        @php
            // Dynamic Title Logic
            $jobTypeLower = strtolower($mainJob->type ?? '');
            
            // Default untuk CSR / Service
            $reportTitle = 'CUSTOMER SERVICE REPORT';
            
            if ($jobTypeLower === 'install') {
                $reportTitle = 'INSTALLATION REPORT';
            } elseif ($jobTypeLower === 'check') {
                $reportTitle = 'CHECK REPORT';
            } elseif (str_contains($jobTypeLower, 'remove')) {
                $reportTitle = 'REMOVE REPORT';
            } elseif (!empty($jobTypeLower) && !in_array($jobTypeLower, ['service', 'maintenance', 'complain', 'csr'])) {
                $reportTitle = strtoupper($mainJob->type) . ' REPORT';
            }
        @endphp
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="title">{{ $reportTitle }}</div>
                    <div class="job-number">{{ $jobNumber }}</div>
                </td>
            </tr>
        </table>

        <!-- Info -->
        <table class="info-table">
            <tr>
                <td class="label">Customer:</td>
                <td>{{ $customerName }}</td>
                <td class="label">Contract No:</td>
                <td>{{ $contractNo }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td>{{ $address }}</td>
                <td class="label">Technician:</td>
                <td>{{ $techName }}</td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td colspan="3">{{ $dateFormatted }}</td>
            </tr>
        </table>

        <!-- Rooms List -->
        <h3>Job Details</h3>
        <table class="rooms-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="center">No</th>
                    <th style="width: 35%;">Item</th>
                    <th style="width: 25%;">Room</th>
                    <th style="width: 20%;" class="center">Job No</th>
                    <th style="width: 15%;" class="center">Period</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rooms as $index => $room)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $room['item'] }}</td>
                    <td>{{ $room['name'] }}</td>
                    <td class="center">{{ $room['job_no'] }}</td>
                    <td class="center">{{ $room['period'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures -->
        <table style="width: 100%; margin-top: 50px; text-align: center;">
            <tr>
                <td style="width: 33%;">
                    <p>Customer Signature</p>
                    <br><br><br>
                    <hr style="width: 80%; border-top: 1px solid #ccc;">
                    <p>{{ $customerName }}</p>
                </td>
                <td style="width: 33%;"></td>
                <td style="width: 33%;">
                    <p>Technician Signature</p>
                    <br><br><br>
                    <hr style="width: 80%; border-top: 1px solid #ccc;">
                    <p>{{ $techName }}</p>
                </td>
            </tr>
        </table>

        <div class="footer">
            Printed on {{ date('d M Y H:i') }} | Page 1 of 1
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
