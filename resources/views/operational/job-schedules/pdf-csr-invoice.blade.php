<!DOCTYPE html>
{{--
    SERVICE REPORT — invoice-merge variant.
    Used ONLY by the combined invoice download (Finance\InvoiceController::downloadCombined).
    The operational standalone CSR print uses operational/job-schedules/pdf-csr.blade.php and is
    intentionally left untouched. Keep the {groupedJobs, selectedRoomIds} variable contract in sync
    with that file's data-collection logic.
--}}
<html>
<head>
    <meta charset="utf-8">
    <title>Service Report</title>
    <style>
        @page { margin: 34px 40px 40px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; color: #111; }
        .page-break { page-break-after: always; }

        .letterhead { width: 100%; border-collapse: collapse; }
        .letterhead td { vertical-align: top; }
        .logo { width: 100px; }
        .company-name { font-size: 17px; font-weight: bold; text-align: right; }
        .doc-title { font-size: 30px; font-weight: bold; text-align: center; margin: 10px 0 48px; }

        .info-wrap { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        .info-wrap > tbody > tr > td { vertical-align: top; }
        .info-left { width: 56%; padding-right: 20px; }
        .info-right { width: 44%; }

        .building-table { width: 100%; border-collapse: collapse; }
        .building-table td { vertical-align: top; font-size: 15px; padding: 0; line-height: 1.4; }
        .building-table .b-label { width: 78px; white-space: nowrap; }
        .building-table .b-sep { width: 10px; }

        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 3px 0; vertical-align: top; font-size: 15px; }
        .meta-label { width: 120px; }
        .meta-sep { width: 10px; }

        .rooms-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 14px; }
        .rooms-table th {
            border: 1px solid #222;
            padding: 8px 10px;
            font-size: 15px;
            font-weight: bold;
            text-align: left;
        }
        .rooms-table td { padding: 8px 10px; vertical-align: top; border: 0; }

        .center { text-align: center; }
        .right { text-align: right; }

        .signature-table { width: 100%; border-collapse: collapse; margin-top: 320px; }
        .signature-table td { vertical-align: bottom; font-size: 14px; }
        .sign-line { border-top: 1px solid #222; }
        .sign-name { text-align: center; padding-top: 4px; }
    </style>
</head>
<body>
    @foreach($groupedJobs as $jobNumber => $schedules)
        @php
            $company = \App\Models\Company::first();
            $mainJob = $schedules->first();
            $contractNo = $mainJob->jobAdvice->contract->contract_number ?? '-';

            // Technicians + team names across the group (respecting selective room printing).
            $technicians = collect();
            $teamNames = collect();
            foreach($schedules as $sch) {
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
                data_get($building, 'alamat_2'),
                data_get($building, 'subdistrict.name'),
                data_get($building, 'district.name'),
                data_get($building, 'city.name'),
                data_get($building, 'province.name'),
                data_get($building, 'kode_pos') ?? data_get($building, 'postal_code'),
            ])->filter(fn ($part) => filled($part))->unique()->values();
            $address = $buildingAddressParts->isNotEmpty()
                ? $buildingAddressParts->implode(', ')
                : ($mainJob->building_name ?? '-');

            $companyName = $company->name ?? 'PT. PINK SERVICES INDONESIA';

            $reportDate = $schedules->max('completed_at') ?? $mainJob->schedule_date;
            $dateFormatted = $reportDate ? \Carbon\Carbon::parse($reportDate)->format('d M Y') : '-';

            // ===== Collect rooms (same logic as operational pdf-csr) =====
            $rooms = collect();
            $isServiceJobType = function (?string $type): bool {
                $normalized = strtolower(str_replace([' ', '-'], '_', (string) $type));
                return in_array($normalized, ['service', 'service_first', 'service_routine', 'csr', 'maintenance'], true);
            };
            $detectRentalMaterialComposition = function ($rental): array {
                $hasUnit = false; $hasNonUnit = false;
                foreach (($rental?->rentalDetails ?? collect()) as $detail) {
                    $category = $detail->productCategory ?? $detail->masterProduct?->productCategory;
                    $type = $detail->productType ?? $detail->masterProduct?->productType;
                    $isUnit = $category?->is_unit ?? $type?->is_unit;
                    if ($isUnit === null) { continue; }
                    if ((bool) $isUnit) { $hasUnit = true; } else { $hasNonUnit = true; }
                    if ($hasUnit && $hasNonUnit) { break; }
                }
                return ['has_unit' => $hasUnit, 'has_non_unit' => $hasNonUnit];
            };
            $resolveRentalFlowType = function ($jobAdviceRoom) use ($detectRentalMaterialComposition): ?string {
                $rental = $jobAdviceRoom?->rentalProduct;
                $rentalType = strtolower(trim(str_replace('-', '_', (string) ($rental?->rental_type ?? ''))));
                if (in_array($rentalType, ['unit_only', 'refill_only', 'unit_refill', 'rental_only'], true)) {
                    return $rentalType;
                }
                $composition = $detectRentalMaterialComposition($rental);
                if ($composition['has_unit'] && ! $composition['has_non_unit']) { return 'unit_only'; }
                if (! $composition['has_unit'] && $composition['has_non_unit']) { return 'refill_only'; }
                if ($composition['has_unit'] && $composition['has_non_unit']) { return 'unit_refill'; }
                return $rentalType ?: null;
            };
            $rentalBelongsToJob = function ($schedule, $jobAdviceRoom, bool $linkedThroughScheduleRoom = false) use ($resolveRentalFlowType): bool {
                if (! $jobAdviceRoom) { return true; }
                $scheduleId = (int) ($schedule->id ?? 0);
                $jobType = strtolower(str_replace([' ', '-'], '_', (string) ($schedule->type ?? '')));
                $flowType = $resolveRentalFlowType($jobAdviceRoom);
                if (! $linkedThroughScheduleRoom) {
                    $linkedScheduleIds = collect([
                        (int) ($jobAdviceRoom->install_job_schedule_id ?? 0),
                        (int) ($jobAdviceRoom->service_job_schedule_id ?? 0),
                        (int) ($jobAdviceRoom->remove_job_schedule_id ?? 0),
                    ])->filter()->values();
                    if ($linkedScheduleIds->isNotEmpty()) {
                        return $linkedScheduleIds->contains($scheduleId);
                    }
                }
                if (! $flowType) { return true; }
                return match (true) {
                    in_array($jobType, ['install', 'installation', 'install_free'], true) => in_array($flowType, ['unit_only', 'unit_refill', 'rental_only'], true),
                    in_array($jobType, ['check', 'cek'], true) => $flowType === 'unit_only',
                    in_array($jobType, ['service', 'service_first', 'service_routine', 'csr', 'maintenance'], true) => in_array($flowType, ['refill_only', 'unit_refill', 'rental_only'], true),
                    str_contains($jobType, 'remove') => in_array($flowType, ['unit_only', 'unit_refill', 'rental_only'], true),
                    default => true,
                };
            };
            $resolveRentalCategory = function ($jobAdviceRoom, ?string $jobType = null, $fallback = '-') use ($isServiceJobType): ?string {
                $rental = $jobAdviceRoom?->rentalProduct;
                $details = $rental?->rentalDetails ?? collect();
                $categories = $details
                    ->map(function ($detail) {
                        $category = $detail->masterProduct?->productCategory ?? $detail->productCategory;
                        $type = $detail->masterProduct?->productType ?? $detail->productType;
                        return [
                            'name' => $category?->name ?? $type?->name,
                            'is_unit' => (bool) ($category?->is_unit ?? $type?->is_unit ?? false),
                        ];
                    })
                    ->filter(fn ($category) => filled($category['name']))
                    ->unique('name')
                    ->values();
                $unitCategory = $categories->firstWhere('is_unit', true);
                $nonUnitCategory = $categories->firstWhere('is_unit', false);
                $firstCategory = $categories->first();
                if ($isServiceJobType($jobType)) {
                    $serviceCategory = $categories
                        ->where('is_unit', false)
                        ->first(function ($category) {
                            $name = strtolower((string) ($category['name'] ?? ''));
                            return str_contains($name, 'refill') || str_contains($name, 'aroma')
                                || str_contains($name, 'scent') || str_contains($name, 'fragrance');
                        });
                    if ($serviceCategory) { return $serviceCategory['name']; }
                    $rentalText = strtolower(trim(collect([
                        data_get($rental, 'category'),
                        data_get($rental, 'rental_name'),
                        data_get($jobAdviceRoom, 'rental_name'),
                    ])->filter()->implode(' ')));
                    if (str_contains($rentalText, 'refill') || str_contains($rentalText, 'aroma')
                        || str_contains($rentalText, 'scent') || str_contains($rentalText, 'fragrance')) {
                        return 'Refill';
                    }
                    if ($unitCategory) { return null; }
                    return $nonUnitCategory ? 'Refill' : null;
                }
                $category = $unitCategory['name'] ?? $firstCategory['name'] ?? null;
                return $category
                    ?: (data_get($rental, 'category')
                    ?: (data_get($rental, 'rental_name')
                    ?: (data_get($jobAdviceRoom, 'rental_name') ?: $fallback)));
            };

            // Single-letter Type code (matches operational pdf-csr convention: R / S / C).
            $resolveTypeCode = function (?string $type): string {
                $jobType = strtolower((string) $type);
                if (str_contains($jobType, 'rental') || str_contains($jobType, 'change') || str_contains($jobType, 'refill') || str_contains($jobType, 'service')) {
                    return 'R';
                }
                if (str_contains($jobType, 'complain')) { return 'C'; }
                if (str_contains($jobType, 'install')) { return 'I'; }
                if (str_contains($jobType, 'remove')) { return 'RV'; }
                return 'S';
            };

            $printedRentalKeys = [];
            foreach($schedules as $sch) {
                $typeCode = $resolveTypeCode($sch->type ?? $mainJob->type ?? '');

                if($sch->jobScheduleRooms && $sch->jobScheduleRooms->count() > 0) {
                    foreach($sch->jobScheduleRooms as $roomPivot) {
                        if ($selectedRoomIds && !in_array($roomPivot->id, $selectedRoomIds)) { continue; }

                        $rentalLinks = method_exists($roomPivot, 'relationLoaded')
                            ? ($roomPivot->relationLoaded('rentals') ? $roomPivot->rentals : collect())
                            : collect($roomPivot->rentals ?? []);
                        $jobAdviceRoomEntries = $rentalLinks->isNotEmpty()
                            ? $rentalLinks
                                ->map(fn ($link) => ['room' => $link->jobAdviceRoom, 'linked_through_schedule_room' => true])
                                ->filter(fn ($entry) => filled($entry['room']))
                                ->values()
                            : collect([['room' => $roomPivot->jobAdviceRoom, 'linked_through_schedule_room' => false]])
                                ->filter(fn ($entry) => filled($entry['room']))->values();

                        foreach ($jobAdviceRoomEntries as $entry) {
                            $jobAdviceRoom = $entry['room'];
                            if (! $rentalBelongsToJob($sch, $jobAdviceRoom, $entry['linked_through_schedule_room'])) { continue; }
                            $itemName = $resolveRentalCategory($jobAdviceRoom, $sch->type ?? $mainJob->type ?? null);
                            if ($itemName === null) { continue; }
                            $roomName = $roomPivot->room->room_name ?? $roomPivot->room_name ?? 'Unknown Room';
                            $rentalKey = implode('|', [
                                $sch->id ?? $sch->job_number ?? $jobNumber,
                                $jobAdviceRoom?->id ?? 'no-advice-room',
                                $itemName, $roomName,
                            ]);
                            if (isset($printedRentalKeys[$rentalKey])) { continue; }
                            $printedRentalKeys[$rentalKey] = true;
                            $rooms->push([
                                'item' => $itemName,
                                'qty' => $jobAdviceRoom?->quantity ?? 1,
                                'name' => $roomName,
                                'type' => $typeCode,
                            ]);
                        }
                    }
                } elseif ($sch->room) {
                    $rooms->push([
                        'item' => $sch->room->room_type ?? 'General Service',
                        'qty' => 1,
                        'name' => $sch->room->room_name,
                        'type' => $typeCode,
                    ]);
                } else {
                    $rooms->push([
                        'item' => 'General Service',
                        'qty' => 1,
                        'name' => 'General Area',
                        'type' => $typeCode,
                    ]);
                }
            }

            $logoPath = public_path('images/logo.png');
            $logoSrc = $logoPath;
            if (file_exists($logoPath)) {
                $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoSrc = 'data:image/' . $logoType . ';base64,' . base64_encode(file_get_contents($logoPath));
            }

            // City suffix appended after the address (target shows "... 15826 Tangerang").
            $citySuffix = data_get($building, 'city.name');
        @endphp

        <table class="letterhead">
            <tr>
                <td style="width: 34%;">
                    <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                </td>
                <td style="width: 66%;">
                    <div class="company-name">{{ strtoupper($companyName) }}</div>
                </td>
            </tr>
        </table>

        <div class="doc-title">SERVICE REPORT</div>

        <table class="info-wrap">
            <tr>
                <td class="info-left">
                    <table class="building-table">
                        <tr>
                            <td class="b-label">Building</td>
                            <td class="b-sep">:</td>
                            <td>
                                {{ $buildingName }} -<br>
                                {{ $address }}@if($citySuffix) {{ $citySuffix }}@endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="info-right">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Job No</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $jobNumber }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Kontrak No</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $contractNo }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Schedule Date</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $dateFormatted }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Technician</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $techName }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="rooms-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Item</th>
                    <th style="width: 52%;">Room</th>
                    <th style="width: 10%;" class="center">Qty</th>
                    <th style="width: 10%;" class="center">Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rooms as $room)
                <tr>
                    <td>{{ $room['item'] }}</td>
                    <td>{{ $room['name'] }}</td>
                    <td class="center">{{ $room['qty'] }}</td>
                    <td class="center">{{ $room['type'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="signature-table">
            <tr>
                <td style="width: 55%;">&nbsp;</td>
                <td style="width: 45%;">
                    <div class="sign-line"></div>
                    <div class="sign-name">{{ $techName }}</div>
                </td>
            </tr>
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
