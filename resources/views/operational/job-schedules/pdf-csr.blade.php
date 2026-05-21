<!DOCTYPE html>
<html>
<head>
    <title>Customer Service Report</title>
    <style>
        @page { margin: 28px 34px 74px 34px; }
        body { font-family: sans-serif; font-size: 10px; color: #111; }
        .page-break { page-break-after: always; }

        .letterhead-table { width: 100%; margin-bottom: 46px; border-collapse: collapse; }
        .letterhead-table td { vertical-align: top; }
        .logo { width: 78px; }
        .company-name { font-size: 19px; font-weight: bold; text-align: right; }
        .document-title { font-size: 24px; font-weight: bold; text-align: center; margin: 8px 0 38px; letter-spacing: 0; }

        .info-wrap { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .info-wrap td { vertical-align: top; }
        .left-info { width: 52%; padding-right: 24px; }
        .right-info { width: 48%; padding-left: 20px; }
        .section-label { font-size: 13px; margin-bottom: 8px; }
        .customer-name { font-size: 14px; font-weight: bold; margin-bottom: 6px; text-transform: uppercase; }
        .address-block { line-height: 1.25; margin-bottom: 24px; }
        .building-title { font-size: 13px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; }

        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 3px 0; vertical-align: top; font-size: 12px; }
        .meta-label { width: 122px; }
        .meta-separator { width: 12px; text-align: center; }

        .rooms-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        .rooms-table th {
            border: 1px solid #333;
            padding: 5px 6px;
            font-size: 13px;
            font-weight: bold;
            text-align: left;
            background: #fff;
            color: #111;
        }
        .rooms-table td { padding: 5px 6px; vertical-align: top; }
        .rooms-table .building-row td { padding-top: 8px; font-weight: bold; text-transform: uppercase; }
        .rooms-table .item-row td { border: 0; }

        .center { text-align: center; }
        .right { text-align: right; }

        .signature-table { width: 100%; margin-top: 195px; text-align: center; border-collapse: collapse; }
        .signature-table td { width: 50%; vertical-align: bottom; }
        .signature-line { width: 56%; border-top: 1px solid #555; margin: 54px auto 6px; }

        .footer-note {
            position: fixed;
            left: 34px;
            right: 34px;
            bottom: 78px;
            border-top: 2px solid #777;
            padding-top: 9px;
            text-align: center;
            color: #777;
            font-size: 10px;
            font-weight: bold;
        }
        .company-footer {
            position: fixed;
            left: 34px;
            right: 34px;
            bottom: 22px;
            border-top: 2px solid #777;
            padding-top: 8px;
            text-align: center;
            color: #777;
            font-size: 9px;
            line-height: 1.25;
        }
        .company-footer strong { color: #555; }

        .header-table { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #214589; padding-bottom: 10px; display: none; }
        .header-table td { vertical-align: top; }
        .title { font-size: 20px; font-weight: bold; color: #214589; text-align: right; }
        .job-number { font-size: 14px; font-weight: bold; text-align: right; margin-top: 5px; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; display: none; }
        .info-table td { padding: 5px; }
        .label { font-weight: bold; width: 120px; color: #555; }
        .footer { display: none; }
    </style>
</head>
<body>
    @foreach($groupedJobs as $jobNumber => $schedules)
        @php
            $company = \App\Models\Company::first();
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

            $building = $mainJob->building;
            $buildingName = $building?->nama_gedung
                ?? $building?->name
                ?? $building?->building_name
                ?? $mainJob->building_name
                ?? '-';

            $buildingAddressParts = collect([
                $building?->alamat_1 ?? $building?->address,
                $building?->alamat_2,
                $building?->subdistrict?->name,
                $building?->district?->name,
                $building?->city?->name,
                $building?->province?->name,
                $building?->kode_pos ?? $building?->postal_code,
            ])->filter(fn ($part) => filled($part))->unique()->values();
            $address = $buildingAddressParts->isNotEmpty()
                ? $buildingAddressParts->implode(', ')
                : ($mainJob->building_name ?? '-');
            $companyName = $company->name ?? 'PT. PINK SERVICES INDONESIA';
            $companyAddress = trim((string) ($company->address ?? 'Komplek Kedoya Center Blok C 8-9 Jl. Raya Pejuangan No. 1 Kebon Jeruk. Jakarta 11530, Indonesia.'));
            $companyPhone = trim((string) ($company->phone ?? '+62 811-9350-083'));
            $companyWebsite = trim((string) ($company->website ?? 'www.adsscent.com'));
            $companyFooterLine = trim(collect([$companyAddress, $companyPhone ? 'Whatsapp: ' . $companyPhone : null, $companyWebsite])->filter()->implode('  '));
            
            // Use completed_at as the official report date, fallback to schedule_date
            $reportDate = $schedules->max('completed_at') ?? $mainJob->schedule_date;
            $dateFormatted = $reportDate ? \Carbon\Carbon::parse($reportDate)->format('d M Y') : '-';
            
            // Collect Rooms
            $rooms = collect();
            $formatJobType = function (?string $type): string {
                $normalized = strtolower(str_replace([' ', '-'], '_', (string) $type));

                return match (true) {
                    in_array($normalized, ['service', 'service_first', 'service_routine', 'csr', 'maintenance'], true) => 'Service/Refill',
                    in_array($normalized, ['install', 'installation', 'install_free'], true) => 'Install',
                    in_array($normalized, ['check', 'cek'], true) => 'Cek',
                    str_contains($normalized, 'complain') || str_contains($normalized, 'extra') => 'No Regular',
                    str_contains($normalized, 'remove') => 'Remove',
                    str_contains($normalized, 'change') => 'Change Rental',
                    default => $type ? ucwords(str_replace('_', ' ', $type)) : '-',
                };
            };

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

                        $jobAdviceRoom = $roomPivot->jobAdviceRoom;
                        $itemName = $jobAdviceRoom?->rentalProduct?->rental_name
                            ?? $jobAdviceRoom?->rentalProduct?->name
                            ?? $jobAdviceRoom?->rental_name
                            ?? $roomPivot->display_rental_name
                            ?? '-';

                        $rooms->push([
                            'reference' => $sch->job_number ?? $jobNumber,
                            'item' => $itemName,
                            'qty' => $jobAdviceRoom?->quantity ?? 1,
                            'name' => $roomPivot->room->room_name ?? 'Unknown Room',
                            'type' => $formatJobType($sch->type ?? $mainJob->type ?? null),
                            'period' => $sch->period ?? '-'
                        ]);
                    }
                } elseif ($sch->room) {
                    $rooms->push([
                         'reference' => $sch->job_number ?? $jobNumber,
                         'item' => $sch->room->room_type ?? 'General Service',
                         'qty' => 1,
                         'name' => $sch->room->room_name,
                         'type' => $formatJobType($sch->type ?? $mainJob->type ?? null),
                         'period' => $sch->period ?? '-'
                    ]);
                } else {
                     $rooms->push([
                        'reference' => $sch->job_number ?? $jobNumber,
                        'item' => 'General Service',
                        'qty' => 1,
                        'name' => 'General Area',
                         'type' => $formatJobType($sch->type ?? $mainJob->type ?? null),
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
        @php
            $logoPath = public_path('images/logo.png');
            $logoSrc = $logoPath;
            if (file_exists($logoPath)) {
                $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoSrc = 'data:image/' . $logoType . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        @endphp

        <table class="letterhead-table">
            <tr>
                <td style="width: 34%;">
                    <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                </td>
                <td style="width: 66%;">
                    <div class="company-name">{{ strtoupper($companyName) }}</div>
                </td>
            </tr>
        </table>

        <div class="document-title">{{ $reportTitle }}</div>

        <table class="info-wrap">
            <tr>
                <td class="left-info">
                    <div class="section-label">Customer :</div>
                    <div class="customer-name">{{ $customerName }}</div>
                    <div class="address-block">{{ $mainJob->jobAdvice->customer->address ?? '-' }}</div>

                    <div class="section-label">Building :</div>
                    <div class="building-title">{{ $buildingName }}</div>
                    <div class="address-block">{{ $address }}</div>
                </td>
                <td class="right-info">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Job No</td>
                            <td class="meta-separator">:</td>
                            <td>{{ $jobNumber }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Contract No</td>
                            <td class="meta-separator">:</td>
                            <td>{{ $contractNo }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Schedule Date</td>
                            <td class="meta-separator">:</td>
                            <td>{{ $dateFormatted }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Technician</td>
                            <td class="meta-separator">:</td>
                            <td>{{ $techName }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="rooms-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Reference</th>
                    <th style="width: 27%;">Item</th>
                    <th style="width: 31%;">Room</th>
                    <th style="width: 8%;" class="center">Qty</th>
                    <th style="width: 8%;" class="center">Type</th>
                    <th style="width: 8%;" class="center">Period</th>
                </tr>
            </thead>
            <tbody>
                <tr class="building-row">
                    <td></td>
                    <td colspan="5">{{ $buildingName }}</td>
                </tr>
                @foreach($rooms as $index => $room)
                <tr class="item-row">
                    <td>{{ $room['reference'] }}</td>
                    <td>{{ $room['item'] }}</td>
                    <td>{{ $room['name'] }}</td>
                    <td class="center">{{ $room['qty'] }}</td>
                    <td class="center">{{ $room['type'] }}</td>
                    <td class="center">{{ $room['period'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="signature-table">
            <tr>
                <td>
                    <p>Customer Signature</p>
                    <div class="signature-line"></div>
                    <p>{{ $customerName }}</p>
                </td>
                <td>
                    <p>Technician Signature</p>
                    <div class="signature-line"></div>
                    <p>{{ $techName }}</p>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            This Job Report was generated on {{ date('d M Y - H:i:s') }} and is valid without the signature and seal.
        </div>

        <div class="company-footer">
            <strong>An ISO 14001:2015 Certified Company | IAS Accredited</strong><br>
            {{ $companyFooterLine }}<br>
            Bali . Bandung . Batam . Balikpapan . Banjarmasin . Jakarta . Lampung . Makassar . Manado . Medan . Palembang . Pekanbaru . Samarinda . Semarang . Surabaya
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
