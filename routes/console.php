<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Command to list all users
Artisan::command('user:list', function () {
    $this->info('Users in database:');
    $this->newLine();

    $users = \App\Models\User::all(['id', 'name', 'email', 'username', 'is_active']);

    if ($users->isEmpty()) {
        $this->warn('No users found in database!');

        return 1;
    }

    foreach ($users as $user) {
        $status = $user->is_active ? '✅ Active' : '❌ Inactive';
        $this->line("ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Username: {$user->username} | Status: {$status}");
    }

    return 0;
})->purpose('List all users in database');

// Register custom commands using Artisan::command
Artisan::command('user:check-login', function () {
    $this->info('Checking all users for login issues...');
    $this->newLine();

    $users = \App\Models\User::all();
    $issues = [];

    foreach ($users as $user) {
        $userIssues = [];

        // Check if user is active
        if (! $user->is_active) {
            $userIssues[] = 'User is inactive';
        }

        // Check if email is set
        if (! $user->email) {
            $userIssues[] = 'Email is missing';
        }

        // Check if username is set
        if (! $user->username) {
            $userIssues[] = 'Username is missing';
        }

        // Check if password is set
        if (! $user->password) {
            $userIssues[] = 'Password is missing';
        }

        if (! empty($userIssues)) {
            $issues[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'is_active' => $user->is_active,
                'issues' => $userIssues,
            ];
        }
    }

    if (empty($issues)) {
        $this->info('✅ All users are properly configured for login!');

        return 0;
    }

    $this->warn('❌ Found '.count($issues).' users with login issues:');
    $this->newLine();

    foreach ($issues as $issue) {
        $this->line("User ID: {$issue['id']}");
        $this->line("Name: {$issue['name']}");
        $this->line("Email: {$issue['email']}");
        $this->line("Username: {$issue['username']}");
        $this->line('Active: '.($issue['is_active'] ? 'Yes' : 'No'));
        $this->line('Issues:');
        foreach ($issue['issues'] as $userIssue) {
            $this->line("  - {$userIssue}");
        }
        $this->newLine();
    }

    $this->info('To fix a specific user, use:');
    $this->line('php artisan user:fix-login --email=user@example.com --password=newpassword');
    $this->line('php artisan user:fix-login --username=username --password=newpassword');

    return 1;
})->purpose('Check all users for login issues');

Artisan::command('user:fix-login {--email=} {--username=} {--password=}', function () {
    $email = $this->option('email');
    $username = $this->option('username');
    $password = $this->option('password');

    if (! $email && ! $username) {
        $this->error('Please provide either --email or --username option');

        return 1;
    }

    // Find user by email or username
    $user = null;
    if ($email) {
        $user = \App\Models\User::where('email', $email)->first();
    } elseif ($username) {
        $user = \App\Models\User::where('username', $username)->first();
    }

    if (! $user) {
        $this->error('User not found!');

        return 1;
    }

    $this->info("Found user: {$user->name} (ID: {$user->id})");
    $this->info("Current email: {$user->email}");
    $this->info("Current username: {$user->username}");
    $this->info('Is active: '.($user->is_active ? 'Yes' : 'No'));

    // Update password if provided
    if ($password) {
        $user->password = \Illuminate\Support\Facades\Hash::make($password);
        $this->info('Password updated');
    }

    // Ensure user is active
    if (! $user->is_active) {
        $user->is_active = true;
        $this->info('User activated');
    }

    // Ensure email and username are set
    if (! $user->email) {
        $user->email = $user->username.'@aroma.com';
        $this->info('Email set to: '.$user->email);
    }

    if (! $user->username) {
        $user->username = explode('@', $user->email)[0];
        $this->info('Username set to: '.$user->username);
    }

    $user->save();

    $this->info('User data updated successfully!');
    $this->info('You can now login with:');
    $this->info("- Email: {$user->email}");
    $this->info("- Username: {$user->username}");
    if ($password) {
        $this->info("- Password: {$password}");
    }

    return 0;
})->purpose('Fix user login issues by updating user data');

// Service Scheduling Commands (Berdasarkan BRD)
Artisan::command('service:generate-schedules {--contract=} {--overdue} {--all}', function () {
    $serviceSchedulingService = new \App\Services\Operational\ServiceSchedulingService;

    if ($this->option('contract')) {
        $contractId = $this->option('contract');
        $this->info("Generating service schedules for contract ID: {$contractId}");

        $result = $serviceSchedulingService->generateServiceSchedulesForContract($contractId);

        if ($result['success']) {
            $this->info("✅ Successfully generated {$result['count']} service schedules");
        } else {
            $this->error("❌ Failed: {$result['message']}");
        }
    } elseif ($this->option('overdue')) {
        $this->info('Generating overdue service schedules...');

        $result = $serviceSchedulingService->generateOverdueServiceSchedules();

        if ($result['success']) {
            $this->info("✅ Generated {$result['total_generated']} overdue schedules for {$result['contracts_processed']} contracts");
        } else {
            $this->error("❌ Failed: {$result['message']}");
        }
    } elseif ($this->option('all')) {
        $this->info('Generating service schedules for all active contracts...');

        $result = $serviceSchedulingService->generateServiceSchedulesForAllActiveContracts();

        if ($result['success']) {
            $this->info("✅ Generated {$result['total_schedules']} schedules for {$result['contracts_processed']} contracts");
        } else {
            $this->error("❌ Failed: {$result['message']}");
        }
    } else {
        $this->info('Please specify an option: --contract=ID, --overdue, or --all');

        return 1;
    }

    return 0;
})->purpose('Auto-generate service schedules based on contract frequency');

// Job Schedule Expected Date Update Command
Artisan::command('job-schedules:update-expected-dates', function () {
    $this->info('Starting to update job schedule expected dates...');

    try {
        $updatedCount = \App\Models\JobSchedule::updateAllExpectedDates();

        $this->info("Successfully updated {$updatedCount} job schedules.");

        if ($updatedCount > 0) {
            $this->info('Updated job schedules:');
            $this->line('- Expected dates have been recalculated based on service frequency');
            $this->line('- Next service dates are now accurate');
        } else {
            $this->info('No job schedules needed updating.');
        }

        return 0;

    } catch (\Exception $e) {
        $this->error('Error updating job schedule expected dates: '.$e->getMessage());

        return 1;
    }
})->purpose('Update expected dates for job schedules based on service frequency');

Artisan::command('finance:repair-contract-invoices {contract : Contract ID or contract number} {--period= : Optional rental period label, e.g. "Period 1"} {--sync-contract-id : Sync existing invoice rows that match the contract number} {--dedupe : Cancel duplicate invoices per period before generating} {--generate-missing : Generate missing invoices for completed periods}', function () {
    $contractInput = trim((string) $this->argument('contract'));
    $periodFilter = $this->option('period');
    $syncContractId = (bool) $this->option('sync-contract-id');
    $dedupe = (bool) $this->option('dedupe');
    $generateMissing = (bool) $this->option('generate-missing');

    if (is_numeric($contractInput)) {
        $contract = \App\Models\Contract::find((int) $contractInput);
    } else {
        $contract = \App\Models\Contract::where('contract_number', $contractInput)->first();
    }

    if (! $contract) {
        $this->error("Contract not found: {$contractInput}");

        return 1;
    }

    $this->info("Target contract: {$contract->contract_number} (ID: {$contract->id})");

    if ($syncContractId) {
        $synced = \App\Models\Finance\Invoice::where('contract_number', $contract->contract_number)
            ->whereNull('contract_id')
            ->update([
                'contract_id' => $contract->id,
                'updated_by' => auth()->id() ?? null,
            ]);

        $this->info("Synced {$synced} invoice row(s) to contract_id={$contract->id}");
    }

    if ($dedupe) {
        $invoices = \App\Models\Finance\Invoice::where(function ($query) use ($contract) {
            $query->where('contract_id', $contract->id)
                ->orWhere('contract_number', $contract->contract_number);
        })
            ->where('invoice_status', '!=', \App\Models\Finance\Invoice::STATUS_CANCELLED)
            ->orderBy('id')
            ->get();

        $groups = $invoices->groupBy('period_invoice');
        $cancelled = 0;

        foreach ($groups as $period => $periodInvoices) {
            if ($periodInvoices->count() <= 1) {
                continue;
            }

            $keep = $periodInvoices->first();
            foreach ($periodInvoices->skip(1) as $duplicate) {
                $duplicate->update([
                    'invoice_status' => \App\Models\Finance\Invoice::STATUS_CANCELLED,
                    'additional_notes' => trim(($duplicate->additional_notes ? $duplicate->additional_notes."\n" : '').'Cancelled as duplicate by finance:repair-contract-invoices'),
                ]);
                $cancelled++;
                $this->line("CANCELLED duplicate invoice {$duplicate->invoice_number} for period {$period} (kept {$keep->invoice_number})");
            }
        }

        $this->info("Cancelled {$cancelled} duplicate invoice row(s).");
    }

    if (! $generateMissing) {
        $this->warn('Skipping invoice generation because --generate-missing was not provided.');

        return 0;
    }

    $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
    $periods = collect($invoiceService->getRentalPeriodsForContract($contract->id));

    if ($periodFilter) {
        $periods = $periods->filter(fn ($period) => strcasecmp($period['rental_period'] ?? '', $periodFilter) === 0);
        if ($periods->isEmpty()) {
            $this->error("Period not found: {$periodFilter}");

            return 1;
        }
    }

    $generated = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($periods as $period) {
        if (($period['status'] ?? null) !== 'completed') {
            $this->line("SKIP {$period['rental_period']}: status={$period['status']}");
            $skipped++;

            continue;
        }

        $result = $invoiceService->autoGenerateInvoiceForRentalPeriod(
            $contract->id,
            $period['rental_period'],
            \Carbon\Carbon::parse($period['period_start']),
            \Carbon\Carbon::parse($period['period_end'])
        );

        if (! empty($result['success'])) {
            $this->info("OK {$period['rental_period']}: {$result['invoice']->invoice_number}");
            $generated++;
        } else {
            $message = $result['message'] ?? 'Unknown error';
            if (str_contains($message, 'Invoice already exists')) {
                $this->line("SKIP {$period['rental_period']}: {$message}");
                $skipped++;
            } else {
                $this->error("FAIL {$period['rental_period']}: {$message}");
                $failed++;
            }
        }
    }

    $this->newLine();
    $this->info("Done. Generated: {$generated}, Skipped: {$skipped}, Failed: {$failed}");

    return $failed > 0 ? 1 : 0;
})->purpose('Repair and backfill invoices for a contract');

Artisan::command('operational:repair-material-issue-items {job_number? : Optional job number to repair} {--user=1 : User ID used for audit fields}', function () {
    $jobNumber = $this->argument('job_number');
    $userId = (int) $this->option('user');

    if (! $userId || ! \App\Models\User::whereKey($userId)->exists()) {
        $fallbackUserId = \App\Models\User::query()->orderBy('id')->value('id');
        if (! $fallbackUserId) {
            $this->error('No valid user found for audit fields. Provide --user=<existing_user_id>.');

            return 1;
        }

        $this->warn("User ID {$userId} not found; using existing user ID {$fallbackUserId} for audit fields.");
        $userId = (int) $fallbackUserId;
    }

    \Illuminate\Support\Facades\Auth::loginUsingId($userId);

    $query = \App\Models\JobAssignSchedule::query()
        ->with(['jobSchedule.jobAdvice', 'jobAssignMaterialIssues.materialIssue']);

    if ($jobNumber) {
        $query->whereHas('jobSchedule', function ($q) use ($jobNumber) {
            $q->where('job_number', $jobNumber);
        });
    }

    $assignments = $query->get();
    if ($assignments->isEmpty()) {
        $this->warn($jobNumber ? "No assignment found for {$jobNumber}" : 'No assignments found.');

        return 0;
    }

    $controller = app(\App\Http\Controllers\Operational\JobScheduleController::class);
    $method = new \ReflectionMethod($controller, 'autoCreateMaterialIssue');
    $method->setAccessible(true);

    $processed = 0;
    foreach ($assignments as $assignment) {
        $before = \App\Models\MaterialIssueItem::where('job_assign_schedule_id', $assignment->id)->count();
        $method->invoke($controller, $assignment);
        $after = \App\Models\MaterialIssueItem::where('job_assign_schedule_id', $assignment->id)->count();
        $created = max(0, $after - $before);
        $processed++;

        $number = $assignment->jobSchedule?->job_number ?? 'No Job Number';
        $this->line("{$number} / assignment {$assignment->id}: +{$created} item(s)");
    }

    $this->info("Done. Processed {$processed} assignment(s).");

    return 0;
})->purpose('Repair missing material issue items from rental details');

Artisan::command('operational:repair-ba-evidence {job_number? : Optional job number to repair} {--apply : Persist the repair; without this option it only previews}', function () {
    $jobNumber = $this->argument('job_number');
    $apply = (bool) $this->option('apply');

    $jobQuery = \App\Models\JobSchedule::query();
    if ($jobNumber) {
        $jobQuery->where('job_number', $jobNumber);
    }

    $jobIds = $jobQuery->pluck('id');
    if ($jobIds->isEmpty()) {
        $this->warn($jobNumber ? "No job schedule found for {$jobNumber}" : 'No job schedules found.');

        return 0;
    }

    $reports = \App\Models\JobReport::whereIn('job_schedule_id', $jobIds)
        ->whereNull('completed_at')
        ->where(function ($query) {
            $query->whereNotNull('photos')
                ->orWhereNotNull('photo_before')
                ->orWhereNotNull('photo_after')
                ->orWhereNotNull('photo_pic')
                ->orWhereNotNull('signature_file')
                ->orWhereNotNull('signature_at');
        })
        ->with('jobSchedule')
        ->get();

    if ($reports->isEmpty()) {
        $this->info('No BA evidence timestamps need repair.');

        return 0;
    }

    $repairedReports = 0;
    $repairedSchedules = 0;

    foreach ($reports as $report) {
        $fallbackCompletedAt = $report->signature_at
            ?? $report->jobSchedule?->completed_at
            ?? $report->updated_at
            ?? $report->created_at
            ?? now();

        $this->line(sprintf(
            '%s / report %s: completed_at => %s%s',
            $report->jobSchedule?->job_number ?? 'No Job Number',
            $report->id,
            \Carbon\Carbon::parse($fallbackCompletedAt)->format('Y-m-d H:i:s'),
            $apply ? '' : ' (preview)'
        ));

        if (! $apply) {
            continue;
        }

        $report->completed_at = $fallbackCompletedAt;
        $report->save();
        $repairedReports++;

        $jobSchedule = $report->jobSchedule;
        if ($jobSchedule && ! $jobSchedule->completed_at) {
            $jobSchedule->completed_at = $fallbackCompletedAt;
            $jobSchedule->save();
            $repairedSchedules++;
        }
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist these repairs.');

        return 0;
    }

    $this->info("Done. Repaired {$repairedReports} report(s) and {$repairedSchedules} job schedule timestamp(s).");

    return 0;
})->purpose('Restore evidence timestamps accidentally cleared by BA cancellation');

Artisan::command('operational:repair-job-schedule-rentals {job_number? : Optional job number to repair} {--apply : Persist the repair; without this option it only previews}', function () {
    $jobNumber = $this->argument('job_number');
    $apply = (bool) $this->option('apply');

    $jobs = \App\Models\JobSchedule::query()
        ->when($jobNumber, fn ($query) => $query->where('job_number', $jobNumber))
        ->whereNotNull('job_advice_id')
        ->with([
            'jobAdvice.rooms.contractRoom.room',
            'jobAdvice.rooms.quotationRoom.room',
            'jobScheduleRooms.rentals',
        ])
        ->get();

    if ($jobs->isEmpty()) {
        $this->warn($jobNumber ? "No job schedule found for {$jobNumber}" : 'No job schedules found.');

        return 0;
    }

    $physicalKey = function ($jaRoom): string {
        $buildingId = $jaRoom->contractRoom?->room?->building_id
            ?? $jaRoom->quotationRoom?->room?->building_id
            ?? $jaRoom->contractRoom?->building_id
            ?? $jaRoom->quotationRoom?->building_id
            ?? 'no-building';

        $roomId = $jaRoom->contractRoom?->room_id
            ?? $jaRoom->quotationRoom?->room_id
            ?? $jaRoom->room_id
            ?? null;

        return $roomId
            ? "room:{$buildingId}:{$roomId}"
            : 'name:'.$buildingId.':'.strtolower(trim((string) $jaRoom->room_name));
    };

    $physicalRoomId = function ($jaRoom): ?int {
        return $jaRoom->contractRoom?->room_id
            ?? $jaRoom->quotationRoom?->room_id
            ?? $jaRoom->room_id
            ?? null;
    };

    $createdLinks = 0;
    $createdRooms = 0;
    $processedJobs = 0;
    $skippedWrongFlow = 0;

    // Rentals only belong to a job of a given type if their flow calls for it
    // (e.g. a refill_only rental must never be linked into an install/IR job)
    // -- reuse the same decision JobAdviceController uses when jobs are first
    // created, instead of re-deriving it here and risking drift.
    $jobAdviceController = app(\App\Http\Controllers\Marketing\JobAdviceController::class);
    $jobTypeFlowKey = [
        'install' => 'needs_install',
        'service' => 'needs_service',
        'service_first' => 'needs_service',
        'service_routine' => 'needs_service',
        'check' => 'needs_check',
    ];

    foreach ($jobs as $job) {
        if (! $job->jobAdvice || $job->jobAdvice->rooms->isEmpty()) {
            continue;
        }

        $processedJobs++;
        $groups = $job->jobAdvice->rooms->groupBy($physicalKey);
        $targetRoomId = $job->room_id ? (int) $job->room_id : null;
        $targetBuildingId = $job->building_id ? (int) $job->building_id : null;
        $targetRoomName = strtolower(trim((string) $job->room_name));
        $flowKey = $jobTypeFlowKey[strtolower(trim((string) $job->type))] ?? null;

        foreach ($groups as $roomGroup) {
            $primaryJaRoom = $roomGroup->first();
            $primaryRoomId = $physicalRoomId($primaryJaRoom);
            $primaryBuildingId = $primaryJaRoom->contractRoom?->room?->building_id
                ?? $primaryJaRoom->quotationRoom?->room?->building_id
                ?? $primaryJaRoom->contractRoom?->building_id
                ?? $primaryJaRoom->quotationRoom?->building_id
                ?? null;

            $belongsToJob = $targetRoomId
                ? ((int) $primaryRoomId === $targetRoomId)
                : ($targetRoomName !== '' && strtolower(trim((string) $primaryJaRoom->room_name)) === $targetRoomName);

            if ($belongsToJob && $targetBuildingId && $primaryBuildingId) {
                $belongsToJob = (int) $primaryBuildingId === $targetBuildingId;
            }

            if (! $belongsToJob) {
                continue;
            }

            // A physical room can mix rental flows (e.g. a unit_refill item
            // that needs install+service alongside a refill_only item that
            // only ever needs service). Only link the rentals that actually
            // belong on this job's type.
            if ($flowKey !== null) {
                $roomGroup = $roomGroup->filter(function ($jaRoom) use ($jobAdviceController, $flowKey) {
                    $flow = $jobAdviceController->determineRentalJobFlow($jaRoom);

                    return (bool) ($flow[$flowKey] ?? false);
                })->values();

                if ($roomGroup->isEmpty()) {
                    $skippedWrongFlow++;

                    continue;
                }

                $primaryJaRoom = $roomGroup->first();
            }

            $jobScheduleRoom = $job->jobScheduleRooms
                ->firstWhere('job_advice_room_id', $primaryJaRoom->id)
                ?? $job->jobScheduleRooms->first(function ($room) use ($roomGroup) {
                    return $roomGroup->pluck('id')->contains($room->job_advice_room_id);
                });

            if (! $jobScheduleRoom) {
                $this->line("{$job->job_number}: would create JobScheduleRoom for {$primaryJaRoom->room_name} ({$roomGroup->count()} rental(s))");
                if (! $apply) {
                    continue;
                }

                $jobScheduleRoom = \App\Models\JobScheduleRoom::create([
                    'job_schedule_id' => $job->id,
                    'job_advice_room_id' => $primaryJaRoom->id,
                    'room_name' => $primaryJaRoom->room_name,
                    'room_id' => $physicalRoomId($primaryJaRoom),
                    'status' => \App\Models\JobScheduleRoom::STATUS_PENDING,
                    'material_return_status' => \App\Models\JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                    'notes' => $roomGroup->count() > 1 ? 'Rentals in this room: '.$roomGroup->count() : null,
                    'created_by' => auth()->id() ?? null,
                    'updated_by' => auth()->id() ?? null,
                ]);
                $createdRooms++;
            }

            $isFirst = true;
            foreach ($roomGroup as $jaRoom) {
                $exists = \App\Models\JobScheduleRoomRental::withTrashed()
                    ->where('job_schedule_room_id', $jobScheduleRoom->id)
                    ->where('job_advice_room_id', $jaRoom->id)
                    ->exists();

                if (! $exists) {
                    $this->line("{$job->job_number}: would link JSR {$jobScheduleRoom->id} to JA room {$jaRoom->id} / {$jaRoom->rental_name}");
                    if ($apply) {
                        \App\Models\JobScheduleRoomRental::create([
                            'job_schedule_room_id' => $jobScheduleRoom->id,
                            'job_advice_room_id' => $jaRoom->id,
                            'is_primary' => $isFirst,
                        ]);
                        $createdLinks++;
                    }
                }

                $isFirst = false;
            }
        }
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist these rental links.');

        return 0;
    }

    $this->info("Done. Processed {$processedJobs} job(s), created {$createdRooms} room row(s), created {$createdLinks} rental link(s), skipped {$skippedWrongFlow} room-group(s) with no rental matching this job's type.");

    return 0;
})->purpose('Repair JobScheduleRoom multi-rental links from JobAdviceRoom data');

Artisan::command('marketing:repair-ja-room-materials
    {--ja= : Job Advice number, example JKT-JA/26-04/0006}
    {--map=* : Explicit quotation room/detail map, format quotation_room_id:quotation_detail_id,quotation_detail_id}
    {--prune : Soft-delete JA rooms and schedule-room links outside the explicit map}
    {--rebuild-material-issues : Rebuild material issue items from repaired schedule-room rental links}
    {--apply : Persist the repair; without this option it only previews}', function () {
    $jaNumber = trim((string) $this->option('ja'));
    $maps = (array) $this->option('map');
    $prune = (bool) $this->option('prune');
    $rebuildMaterialIssues = (bool) $this->option('rebuild-material-issues');
    $apply = (bool) $this->option('apply');

    if ($jaNumber === '') {
        $this->error('Option --ja is required.');

        return 1;
    }

    $jobAdvice = \App\Models\JobAdvice::with([
        'rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
        'rooms.rentalProduct.rentalDetails.masterProduct.productType',
        'rooms.rentalProduct.rentalDetails.allowedProducts.productCategory',
        'rooms.rentalProduct.rentalDetails.allowedProducts.productType',
        'rooms.contractRoom.room.building',
        'rooms.quotationRoom.room.building',
        'quotation.quotationDetails.masterRental',
    ])->where('job_advice_number', $jaNumber)->first();

    if (! $jobAdvice) {
        $this->error("Job Advice {$jaNumber} not found.");

        return 1;
    }

    $this->line($apply ? 'APPLY mode: changes will be persisted.' : 'Preview only. Re-run with --apply to persist these repairs.');

    $parsedMap = collect($maps)->flatMap(function ($map) {
        if (! str_contains($map, ':')) {
            return [];
        }

        [$quotationRoomId, $detailIds] = explode(':', $map, 2);

        return collect(explode(',', $detailIds))
            ->map(fn ($detailId) => trim($detailId))
            ->filter()
            ->map(fn ($detailId) => [
                'quotation_room_id' => (int) trim($quotationRoomId),
                'quotation_detail_id' => (int) $detailId,
            ]);
    })->values();

    $desiredJaRoomIds = collect();

    if ($parsedMap->isNotEmpty()) {
        foreach ($parsedMap as $entry) {
            $quotationRoom = \App\Models\QuotationRoom::with('room.building')
                ->whereKey($entry['quotation_room_id'])
                ->where('quotation_id', $jobAdvice->quotation_id)
                ->first();
            $quotationDetail = \App\Models\QuotationDetail::with('masterRental')
                ->whereKey($entry['quotation_detail_id'])
                ->where('quotation_id', $jobAdvice->quotation_id)
                ->first();

            if (! $quotationRoom || ! $quotationDetail || ! $quotationDetail->master_rental_id) {
                $this->warn("Skip invalid map {$entry['quotation_room_id']}:{$entry['quotation_detail_id']}");

                continue;
            }

            $existingRooms = $jobAdvice->rooms()
                ->withTrashed()
                ->where('quotation_room_id', $quotationRoom->id)
                ->where(function ($query) use ($quotationDetail) {
                    $query->where('quotation_detail_id', $quotationDetail->id)
                        ->orWhere(function ($fallback) use ($quotationDetail) {
                            $fallback->whereNull('quotation_detail_id')
                                ->where('rental_product_id', $quotationDetail->master_rental_id);
                        });
                })
                ->orderByRaw('deleted_at IS NOT NULL')
                ->orderBy('id')
                ->get();

            $jaRoom = $existingRooms->first();
            $rentalName = $quotationDetail->rental_alias
                ?: ($quotationDetail->masterRental?->rental_name ?? 'N/A');

            if (! $jaRoom) {
                $this->line("Would create JA room {$quotationRoom->id} / detail {$quotationDetail->id} / rental {$rentalName}");

                if ($apply) {
                    $jaRoom = \App\Models\JobAdviceRoom::create([
                        'job_advice_id' => $jobAdvice->id,
                        'quotation_room_id' => $quotationRoom->id,
                        'quotation_detail_id' => $quotationDetail->id,
                        'rental_product_id' => $quotationDetail->master_rental_id,
                        'room_name' => $quotationRoom->room_name,
                        'rental_name' => $rentalName,
                        'quantity' => $quotationDetail->quantity ?: 1,
                        'rental_has_installation' => true,
                        'rental_has_service' => false,
                        'status' => \App\Models\JobAdviceRoom::STATUS_PENDING,
                        'is_trial' => in_array(strtolower((string) $jobAdvice->type), ['install_free', 'install free'], true),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            } else {
                $this->line("Would update JA room {$jaRoom->id}: qroom {$quotationRoom->id}, qdetail {$quotationDetail->id}, rental {$rentalName}");

                if ($apply) {
                    if (method_exists($jaRoom, 'restore') && $jaRoom->trashed()) {
                        $jaRoom->restore();
                    }

                    $jaRoom->update([
                        'quotation_room_id' => $quotationRoom->id,
                        'quotation_detail_id' => $quotationDetail->id,
                        'rental_product_id' => $quotationDetail->master_rental_id,
                        'room_name' => $quotationRoom->room_name,
                        'rental_name' => $rentalName,
                        'quantity' => $quotationDetail->quantity ?: 1,
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            if ($jaRoom) {
                $desiredJaRoomIds->push((int) $jaRoom->id);
            }

            $existingRooms->skip(1)->each(function ($duplicate) use ($apply) {
                $this->line("Would soft-delete duplicate JA room {$duplicate->id}");
                if ($apply && ! $duplicate->trashed()) {
                    $duplicate->delete();
                }
            });
        }

        if ($prune && $desiredJaRoomIds->isNotEmpty()) {
            $jobAdvice->rooms()->whereNotIn('id', $desiredJaRoomIds)->get()->each(function ($jaRoom) use ($apply) {
                $this->line("Would soft-delete JA room outside map {$jaRoom->id} / {$jaRoom->room_name} / {$jaRoom->rental_name}");
                if ($apply) {
                    $jaRoom->delete();
                }
            });
        }
    }

    $jobAdvice->load([
        'rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
        'rooms.rentalProduct.rentalDetails.masterProduct.productType',
        'rooms.rentalProduct.rentalDetails.allowedProducts.productCategory',
        'rooms.rentalProduct.rentalDetails.allowedProducts.productType',
        'rooms.contractRoom.room.building',
        'rooms.quotationRoom.room.building',
    ]);

    if ($prune && $desiredJaRoomIds->isNotEmpty()) {
        $jobAdvice->setRelation(
            'rooms',
            $jobAdvice->rooms->whereIn('id', $desiredJaRoomIds->unique()->values())->values()
        );
    }

    $roomKey = function ($jaRoom): string {
        $buildingId = $jaRoom->contractRoom?->room?->building_id
            ?? $jaRoom->quotationRoom?->room?->building_id
            ?? 'no-building';
        $roomId = $jaRoom->contractRoom?->room_id
            ?? $jaRoom->quotationRoom?->room_id
            ?? null;

        return $roomId
            ? "room:{$buildingId}:{$roomId}"
            : 'name:'.$buildingId.':'.strtolower(trim((string) $jaRoom->room_name));
    };

    $roomIdOf = fn ($jaRoom) => $jaRoom->contractRoom?->room_id ?? $jaRoom->quotationRoom?->room_id ?? null;
    $buildingIdOf = fn ($jaRoom) => $jaRoom->contractRoom?->room?->building_id ?? $jaRoom->quotationRoom?->room?->building_id ?? null;

    $jobs = \App\Models\JobSchedule::with([
        'jobScheduleRooms.rentals',
        'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
    ])->where('job_advice_id', $jobAdvice->id)->get();

    $groups = $jobAdvice->rooms->groupBy($roomKey);
    $touchedMaterialIssues = collect();

    // Rentals only belong to a job of a given type if their flow calls for it
    // (e.g. a refill_only rental must never be linked into an install/IR job)
    // -- reuse the same decision JobAdviceController uses when jobs are first
    // created, instead of re-deriving it here and risking drift.
    $jobAdviceController = app(\App\Http\Controllers\Marketing\JobAdviceController::class);
    $jobTypeFlowKey = [
        'install' => 'needs_install',
        'service' => 'needs_service',
        'service_first' => 'needs_service',
        'service_routine' => 'needs_service',
        'check' => 'needs_check',
    ];

    foreach ($jobs as $job) {
        $matchingGroups = $groups->filter(function ($roomGroup) use ($job, $roomIdOf, $buildingIdOf) {
            $primary = $roomGroup->first();
            $sameRoom = $job->room_id
                ? (int) $roomIdOf($primary) === (int) $job->room_id
                : strtolower(trim((string) $primary->room_name)) === strtolower(trim((string) $job->room_name));

            if (! $sameRoom) {
                return false;
            }

            return ! $job->building_id || ! $buildingIdOf($primary) || (int) $buildingIdOf($primary) === (int) $job->building_id;
        });

        if ($matchingGroups->isEmpty() && $jobs->count() === 1) {
            $matchingGroups = $groups;
        }

        $allowedJaRoomIds = $matchingGroups->flatMap(fn ($group) => $group->pluck('id'))->map(fn ($id) => (int) $id)->values();

        if ($prune) {
            $job->jobScheduleRooms->each(function ($scheduleRoom) use ($allowedJaRoomIds, $apply) {
                $linkedIds = $scheduleRoom->rentals->pluck('job_advice_room_id')->push($scheduleRoom->job_advice_room_id)->filter()->map(fn ($id) => (int) $id);
                if ($linkedIds->intersect($allowedJaRoomIds)->isEmpty()) {
                    $this->line("Would remove unrelated JobScheduleRoom {$scheduleRoom->id}");
                    if ($apply) {
                        \App\Models\JobScheduleRoomRental::where('job_schedule_room_id', $scheduleRoom->id)->delete();
                        $scheduleRoom->delete();
                    }
                }
            });
        }

        $flowKey = $jobTypeFlowKey[strtolower(trim((string) $job->type))] ?? null;

        foreach ($matchingGroups as $roomGroup) {
            // A physical room can mix rental flows (e.g. a unit_refill item
            // that needs install+service alongside a refill_only item that
            // only ever needs service). Only sync the rentals that actually
            // belong on this job's type.
            if ($flowKey !== null) {
                $roomGroup = $roomGroup->filter(function ($jaRoom) use ($jobAdviceController, $flowKey) {
                    $flow = $jobAdviceController->determineRentalJobFlow($jaRoom);

                    return (bool) ($flow[$flowKey] ?? false);
                })->values();

                if ($roomGroup->isEmpty()) {
                    continue;
                }
            }

            $primary = $roomGroup->first();
            $scheduleRoom = $job->jobScheduleRooms
                ->first(function ($scheduleRoom) use ($roomGroup) {
                    $linkedIds = $scheduleRoom->rentals->pluck('job_advice_room_id')->push($scheduleRoom->job_advice_room_id)->filter();

                    return $linkedIds->intersect($roomGroup->pluck('id'))->isNotEmpty();
                });

            $this->line("Would sync job {$job->job_number} room {$primary->room_name} with {$roomGroup->count()} rental(s)");

            if ($apply) {
                if (! $scheduleRoom) {
                    $scheduleRoom = \App\Models\JobScheduleRoom::create([
                        'job_schedule_id' => $job->id,
                        'job_advice_room_id' => $primary->id,
                        'room_name' => $primary->room_name,
                        'room_id' => $roomIdOf($primary),
                        'status' => \App\Models\JobScheduleRoom::STATUS_PENDING,
                        'material_return_status' => \App\Models\JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                        'notes' => $roomGroup->count() > 1 ? 'Rentals in this room: '.$roomGroup->count() : null,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                } else {
                    $scheduleRoom->update([
                        'job_advice_room_id' => $primary->id,
                        'room_name' => $primary->room_name,
                        'room_id' => $roomIdOf($primary),
                        'notes' => $roomGroup->count() > 1 ? 'Rentals in this room: '.$roomGroup->count() : $scheduleRoom->notes,
                        'updated_by' => auth()->id(),
                    ]);
                }

                \App\Models\JobScheduleRoomRental::where('job_schedule_room_id', $scheduleRoom->id)
                    ->whereNotIn('job_advice_room_id', $roomGroup->pluck('id'))
                    ->delete();

                $isFirst = true;
                foreach ($roomGroup as $jaRoom) {
                    \App\Models\JobScheduleRoomRental::firstOrCreate([
                        'job_schedule_room_id' => $scheduleRoom->id,
                        'job_advice_room_id' => $jaRoom->id,
                    ], [
                        'is_primary' => $isFirst,
                    ]);
                    $isFirst = false;
                }
            }
        }

        if ($rebuildMaterialIssues && $apply) {
            $job->load('jobScheduleRooms.rentals.jobAdviceRoom.rentalProduct.rentalDetails.masterProduct.productCategory', 'jobScheduleRooms.rentals.jobAdviceRoom.rentalProduct.rentalDetails.allowedProducts.productCategory');
            $assignedRooms = $job->jobScheduleRooms
                ->flatMap(fn ($scheduleRoom) => $scheduleRoom->rentals->pluck('jobAdviceRoom')->filter())
                ->unique('id')
                ->values();

            foreach ($job->jobAssignSchedules as $assignment) {
                foreach ($assignment->jobAssignMaterialIssues as $link) {
                    $materialIssue = $link->materialIssue;
                    if (! $materialIssue || ! in_array($materialIssue->status, ['draft', 'pending', 'approved'], true)) {
                        continue;
                    }

                    $materialIssue->items()
                        ->where('job_assign_schedule_id', $assignment->id)
                        ->delete();

                    $totalQty = 0;
                    $firstProductId = null;
                    foreach ($assignedRooms as $jaRoom) {
                        $rental = $jaRoom->rentalProduct;
                        if (! $rental) {
                            continue;
                        }

                        foreach ($rental->rentalDetails as $detail) {
                            $product = $detail->masterProduct ?: $detail->allowedProducts->first();
                            if (! $product) {
                                continue;
                            }

                            $qty = (float) ($detail->quantity ?: 0);
                            if ($qty <= 0) {
                                continue;
                            }

                            $firstProductId ??= $product->id;
                            $totalQty += $qty;

                            \App\Models\MaterialIssueItem::create([
                                'material_issue_id' => $materialIssue->id,
                                'job_assign_schedule_id' => $assignment->id,
                                'product_id' => $product->id,
                                'room_name' => $jaRoom->room_name,
                                'quantity' => $qty,
                                'convert' => 1,
                                'bom_quantity' => $product->bom_quantity ?? 0,
                                'unit_price' => $product->last_unit_price ?? 0,
                                'total_price' => ($product->last_unit_price ?? 0) * $qty,
                                'notes' => "Rebuilt from JA {$jobAdvice->job_advice_number}, room {$jaRoom->room_name}, rental {$rental->rental_name}",
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);
                        }
                    }

                    $materialIssue->update([
                        'quantity' => $totalQty,
                        'product_id' => $firstProductId,
                        'updated_by' => auth()->id(),
                    ]);
                    $touchedMaterialIssues->push($materialIssue->issue_number);
                }
            }
        }
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist these repairs.');

        return 0;
    }

    $this->info('Done. Material issues rebuilt: '.$touchedMaterialIssues->filter()->unique()->implode(', '));

    return 0;
})->purpose('Repair quotation-based Job Advice room/rental links and related material issue items');

Artisan::command('ops:repair-patch-data
    {--job=* : Job number(s) to repair BA evidence, schedule-room rental links, and material issue items}
    {--if-job=* : Completed Install Free job number(s) whose Remove Free rooms must be resynced}
    {--service-job=* : Completed service/CSR job number(s) used to prune future service room scope}
    {--quotation= : Quotation number for aroma switching data repair}
    {--job-advice= : Job Advice number for aroma switching data repair}
    {--inventory-request= : Inventory request number to backfill approved/issued/received qty defaults}
    {--all-inventory-requests : Backfill qty defaults for all inventory requests}
    {--apply : Persist the repair; without this option it only previews}', function () {
    $apply = (bool) $this->option('apply');
    $jobs = collect((array) $this->option('job'))->filter()->values();
    $ifJobs = collect((array) $this->option('if-job'))->filter()->values();
    $serviceJobs = collect((array) $this->option('service-job'))->filter()->values();
    $quotationNumber = trim((string) $this->option('quotation'));
    $jobAdviceNumber = trim((string) $this->option('job-advice'));
    $inventoryRequestNumber = trim((string) $this->option('inventory-request'));
    $allInventoryRequests = (bool) $this->option('all-inventory-requests');
    $auditUserId = \Illuminate\Support\Facades\Auth::id() ?: (int) \App\Models\User::query()->orderBy('id')->value('id');

    $this->line($apply ? 'APPLY mode: changes will be persisted.' : 'Preview only. Re-run with --apply to persist repairs.');

    $callRepair = function (string $command, array $parameters) use ($apply) {
        if ($apply) {
            $parameters['--apply'] = true;
        }

        $this->newLine();
        $this->info('$ php artisan '.$command.' '.collect($parameters)->map(function ($value, $key) {
            if ($value === true) {
                return $key;
            }

            return $key.'='.$value;
        })->implode(' '));

        Artisan::call($command, $parameters);
        $this->line(Artisan::output());
    };

    foreach ($jobs as $jobNumber) {
        $callRepair('operational:repair-ba-evidence', ['job_number' => $jobNumber]);
        $callRepair('operational:repair-job-schedule-rentals', ['job_number' => $jobNumber]);

        if ($apply) {
            $this->newLine();
            $this->info('$ php artisan operational:repair-material-issue-items '.$jobNumber.' --user='.$auditUserId);
            Artisan::call('operational:repair-material-issue-items', ['job_number' => $jobNumber, '--user' => $auditUserId]);
            $this->line(Artisan::output());
        } else {
            $this->warn("Material issue item rebuild for {$jobNumber} is skipped in preview because the existing command writes immediately.");
        }
    }

    foreach ($serviceJobs as $jobNumber) {
        $callRepair('operational:repair-service-period-room-scope', ['--job' => $jobNumber]);
    }

    if ($quotationNumber !== '' || $jobAdviceNumber !== '') {
        $params = [];
        if ($quotationNumber !== '') {
            $params['--quotation'] = $quotationNumber;
        }
        if ($jobAdviceNumber !== '') {
            $params['--job-advice'] = $jobAdviceNumber;
        }
        $callRepair('operational:repair-aroma-switching-data', $params);
    }

    $repairInventoryRequests = function () use ($inventoryRequestNumber, $allInventoryRequests, $apply) {
        if ($inventoryRequestNumber === '' && ! $allInventoryRequests) {
            return;
        }

        $query = \App\Models\InventoryRequest::with('items');
        if ($inventoryRequestNumber !== '') {
            $query->where('request_number', $inventoryRequestNumber);
        }

        $requests = $query->get();
        if ($requests->isEmpty()) {
            $this->warn($inventoryRequestNumber ? "Inventory request {$inventoryRequestNumber} not found." : 'No inventory requests found.');

            return;
        }

        $updated = 0;
        foreach ($requests as $request) {
            foreach ($request->items as $item) {
                $changes = [];
                if (($item->approved_qty === null || (float) $item->approved_qty <= 0) && (float) $item->quantity > 0) {
                    $changes['approved_qty'] = $item->quantity;
                }
                if (in_array($request->status, ['approved', 'issued', 'completed', 'received'], true)
                    && ($item->issued_qty === null || (float) $item->issued_qty <= 0)
                    && (float) ($changes['approved_qty'] ?? $item->approved_qty ?? $item->quantity) > 0) {
                    $changes['issued_qty'] = $changes['approved_qty'] ?? $item->approved_qty ?? $item->quantity;
                }
                if (in_array($request->status, ['completed', 'received'], true)
                    && ($item->received_qty === null || (float) $item->received_qty <= 0)
                    && (float) ($changes['issued_qty'] ?? $item->issued_qty ?? $item->approved_qty ?? $item->quantity) > 0) {
                    $changes['received_qty'] = $changes['issued_qty'] ?? $item->issued_qty ?? $item->approved_qty ?? $item->quantity;
                }

                if (empty($changes)) {
                    continue;
                }

                $this->line("InventoryRequest {$request->request_number} item {$item->id}: would set ".collect($changes)->map(fn ($value, $field) => "{$field}={$value}")->implode(', '));

                if ($apply) {
                    $item->update($changes + ['updated_by' => auth()->id()]);
                }

                $updated++;
            }
        }

        $this->info(($apply ? 'Updated' : 'Previewed')." {$updated} inventory request item(s).");
    };

    $repairInventoryRequests();

    foreach ($ifJobs as $ifJobNumber) {
        $installJobs = \App\Models\JobSchedule::with(['jobAdvice', 'jobScheduleRooms.rentals'])
            ->where('job_number', $ifJobNumber)
            ->whereIn('type', ['install_free', 'Install Free'])
            ->get();

        if ($installJobs->isEmpty()) {
            $this->warn("Install Free job {$ifJobNumber} not found.");

            continue;
        }

        foreach ($installJobs as $installJob) {
            $completedRoomIds = $installJob->jobScheduleRooms
                ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                ->pluck('room_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($completedRoomIds->isEmpty()) {
                $completedRoomIds = $installJob->jobScheduleRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
            }

            if ($completedRoomIds->isEmpty()) {
                $this->warn("Install Free {$ifJobNumber}: no room_id found; cannot safely repair Remove Free scope.");

                continue;
            }

            $removeJobs = \App\Models\JobSchedule::with('jobScheduleRooms.rentals')
                ->where('job_advice_id', $installJob->job_advice_id)
                ->whereIn('type', ['remove_free', 'remove free'])
                ->whereNotIn('status', ['done_job', 'completed', 'cancelled', 'suspend', 'dpf'])
                ->get();

            foreach ($removeJobs as $removeJob) {
                $badRooms = $removeJob->jobScheduleRooms->filter(fn ($room) => $room->room_id && ! $completedRoomIds->contains((int) $room->room_id));
                $goodRooms = $removeJob->jobScheduleRooms->filter(fn ($room) => ! $room->room_id || $completedRoomIds->contains((int) $room->room_id));

                foreach ($badRooms as $room) {
                    $this->line("Remove Free {$removeJob->job_number}: would remove room {$room->id} {$room->room_name}");
                    if ($apply) {
                        \App\Models\JobScheduleRoomRental::where('job_schedule_room_id', $room->id)->delete();
                        $room->delete();
                    }
                }

                if ($goodRooms->isEmpty()) {
                    $this->line("Remove Free {$removeJob->job_number}: would cancel because no eligible installed room remains.");
                    if ($apply) {
                        $removeJob->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);
                    }
                }
            }
        }
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist repairs.');
    }

    return 0;
})->purpose('One-line repair runner for data affected by recent operational, invoice, inventory, and aroma patches');

Artisan::command('operational:repair-active-grouped-room-sn
    {--ir-job=JKT-IR/26-04/0009 : Grouped IR job number}
    {--csr-job=JKT-CSR/26-04/0012 : Grouped CSR job number}
    {--pending-sn=JKTDFRG0005 : Serial number that belongs to the unfinished/pending IR room}
    {--done-sn=JKTDFRG0006 : Serial number that belongs to the completed IR room}
    {--technician-id=54 : Technician/user ID that should hold the pending SN}
    {--user=1 : User ID used for audit fields}
    {--apply : Persist the repair; without this option it only previews}', function () {
    $irJobNumber = (string) $this->option('ir-job');
    $csrJobNumber = (string) $this->option('csr-job');
    $pendingSnCode = strtoupper(trim((string) $this->option('pending-sn')));
    $doneSnCode = strtoupper(trim((string) $this->option('done-sn')));
    $technicianId = (int) $this->option('technician-id');
    $auditUserId = (int) $this->option('user');
    $apply = (bool) $this->option('apply');

    $this->line($apply ? 'APPLY mode: changes will be persisted.' : 'Preview only. Re-run with --apply to persist these repairs.');

    $pendingSn = \App\Models\SerialNumber::whereRaw('UPPER(TRIM(serial_number)) = ?', [$pendingSnCode])->first();
    $doneSn = \App\Models\SerialNumber::whereRaw('UPPER(TRIM(serial_number)) = ?', [$doneSnCode])->first();

    if (! $pendingSn) {
        $this->error("Pending-room SN {$pendingSnCode} not found.");

        return 1;
    }

    if (! $doneSn) {
        $this->error("Completed-room SN {$doneSnCode} not found.");

        return 1;
    }

    $activePendingUow = \App\Models\UnitOnWall::where('serial_number_id', $pendingSn->id)
        ->where('status', 'active')
        ->first();

    if ($activePendingUow) {
        $this->warn("SN {$pendingSnCode} has active Unit On Wall #{$activePendingUow->id}; status will NOT be changed automatically.");
    } else {
        $this->line("SN {$pendingSnCode}: {$pendingSn->status} -> on_hand, location technician #{$technicianId}");
        if ($apply) {
            $pendingSn->update([
                'status' => 'on_hand',
                'location_type' => 'technician',
                'location_id' => $technicianId,
                'updated_by' => $auditUserId,
                'updated_at' => now(),
            ]);
        }
    }

    $activeDoneUow = \App\Models\UnitOnWall::where('serial_number_id', $doneSn->id)
        ->where('status', 'active')
        ->first();

    if ($activeDoneUow) {
        $this->line("SN {$doneSnCode}: active Unit On Wall #{$activeDoneUow->id} found; ensure status in_use/customer.");
        if ($apply) {
            $doneSn->update([
                'status' => 'in_use',
                'location_type' => 'customer',
                'location_id' => $activeDoneUow->customer_id,
                'updated_by' => $auditUserId,
                'updated_at' => now(),
            ]);
        }
    } else {
        $this->warn("SN {$doneSnCode} has no active Unit On Wall; leaving SN status unchanged.");
    }

    $irJobs = \App\Models\JobSchedule::where('job_number', $irJobNumber)
        ->with(['jobScheduleRooms', 'jobAssignSchedules'])
        ->get();

    if ($irJobs->isEmpty()) {
        $this->error("IR job {$irJobNumber} not found.");

        return 1;
    }

    foreach ($irJobs as $job) {
        $hasCompletedRoom = $job->jobScheduleRooms->contains('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED);
        $hasPendingRoom = $job->jobScheduleRooms->contains(fn ($room) => $room->status !== \App\Models\JobScheduleRoom::STATUS_COMPLETED);

        foreach ($job->jobAssignSchedules as $assignment) {
            if ($hasPendingRoom && $assignment->status === 'completed') {
                $this->line("Assignment #{$assignment->id} ({$job->job_number} / {$job->room_name}): completed -> in_progress");
                if ($apply) {
                    $assignment->update([
                        'status' => 'in_progress',
                        'updated_by' => $auditUserId,
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($hasCompletedRoom && $assignment->status !== 'completed') {
                $this->line("Assignment #{$assignment->id} ({$job->job_number} / {$job->room_name}): {$assignment->status} -> completed");
                if ($apply) {
                    $assignment->update([
                        'status' => 'completed',
                        'updated_by' => $auditUserId,
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    $csrJobs = \App\Models\JobSchedule::where('job_number', $csrJobNumber)
        ->with('jobScheduleRooms')
        ->get();

    foreach ($csrJobs as $job) {
        if (in_array($job->status, ['done_job', 'completed', 'selesai'], true)) {
            continue;
        }

        $this->line("CSR {$job->job_number} / {$job->room_name}: status remains {$job->status}; room rows pending: ".$job->jobScheduleRooms->where('status', '!=', \App\Models\JobScheduleRoom::STATUS_COMPLETED)->count());
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist these repairs.');

        return 0;
    }

    $this->info('Done. Active grouped room/SN data repaired.');

    return 0;
})->purpose('Repair active grouped IR/CSR room data where one room completion incorrectly locked sibling serial numbers');

Artisan::command('operational:repair-aroma-switching-data
    {--quotation= : Optional quotation number, e.g. JKT-SQ/26-04/0021}
    {--job-advice= : Optional job advice number, e.g. JKT-JA/26-04/0048}
    {--user=1 : User ID used for audit fields}
    {--apply : Persist the repair; without this option it only previews}', function () {
    $quotationNumber = $this->option('quotation');
    $jobAdviceNumber = $this->option('job-advice');
    $auditUserId = (int) $this->option('user');
    $apply = (bool) $this->option('apply');

    if (! \App\Models\User::whereKey($auditUserId)->exists()) {
        $fallbackUserId = \App\Models\User::query()->orderBy('id')->value('id');
        if (! $fallbackUserId) {
            $this->error('No valid user found for audit fields. Provide --user=<existing_user_id>.');

            return 1;
        }

        $this->warn("User ID {$auditUserId} not found; using existing user ID {$fallbackUserId} for audit fields.");
        $auditUserId = (int) $fallbackUserId;
    }

    $this->line($apply ? 'APPLY mode: changes will be persisted.' : 'Preview only. Re-run with --apply to persist these repairs.');

    $isValidAromaProduct = function ($product): bool {
        if (! $product) {
            return false;
        }

        $haystack = strtolower(implode(' ', array_filter([
            $product->name ?? null,
            $product->variant_name ?? null,
            $product->brand_line ?? null,
            $product->productCategory?->name ?? null,
            $product->productType?->name ?? null,
        ])));

        $isUnit = (bool) ($product->productCategory?->is_unit ?? $product->productType?->is_unit ?? false);
        $hasSerialNumber = (bool) ($product->productCategory?->has_serial_number ?? $product->productType?->has_serial_number ?? false);

        return ! $isUnit
            && ! $hasSerialNumber
            && ! str_contains(strtolower($product->name ?? ''), 'test')
            && (str_contains($haystack, 'refill')
                || str_contains($haystack, 'aroma')
                || str_contains($haystack, 'fragrance')
                || str_contains($haystack, 'scent')
                || str_contains($haystack, 'squash')
                || str_contains($haystack, 'essence')
                || str_contains($haystack, 'variant')
                || preg_match('/\boil\b/', $haystack) === 1
                || str_contains($haystack, 'luxo')
                || str_contains($haystack, 'artisan')
                || str_contains($haystack, 'signature'));
    };

    $resolveAromaProduct = function ($product) use ($isValidAromaProduct) {
        if (! $product) {
            return null;
        }

        if ($isValidAromaProduct($product)) {
            return $product;
        }

        $variantName = trim((string) $product->variant_name);
        if ($variantName === '') {
            return null;
        }

        $brandLine = trim(strtolower((string) $product->brand_line));

        return \App\Models\MasterProduct::with(['productCategory', 'productType', 'packagingSize'])
            ->where('is_active', true)
            ->where('variant_name', $variantName)
            ->when($brandLine !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(brand_line)) = ?', [$brandLine]))
            ->get()
            ->filter($isValidAromaProduct)
            ->sortBy(function ($candidate) {
                $packageName = strtolower($candidate->packagingSize?->name ?? '');
                $categoryName = strtolower($candidate->productCategory?->name ?? '');

                return [
                    $packageName === '100ml' ? 0 : 1,
                    str_contains($categoryName, 'refill') ? 0 : 1,
                    $candidate->id,
                ];
            })
            ->first();
    };

    $quotationIds = collect();

    if ($quotationNumber) {
        $quotation = \App\Models\Quotation::where('quotation_number', $quotationNumber)->first();
        if (! $quotation) {
            $this->error("Quotation {$quotationNumber} not found.");

            return 1;
        }
        $quotationIds->push($quotation->id);
    }

    if ($jobAdviceNumber) {
        $jobAdvice = \App\Models\JobAdvice::where('job_advice_number', $jobAdviceNumber)->first();
        if (! $jobAdvice) {
            $this->error("Job Advice {$jobAdviceNumber} not found.");

            return 1;
        }

        if ($jobAdvice->quotation_id) {
            $quotationIds->push($jobAdvice->quotation_id);
        }

        if ($jobAdvice->contract_id) {
            $contract = \App\Models\Contract::find($jobAdvice->contract_id);
            if ($contract?->quotation_id) {
                $quotationIds->push($contract->quotation_id);
            }
        }
    }

    $quotationIds = $quotationIds->filter()->unique()->values();

    if ($quotationIds->isEmpty()) {
        $this->warn('No quotation scope provided. Use --quotation=... or --job-advice=...');

        return 1;
    }

    $quotationRooms = \App\Models\QuotationRoom::with(['aromaProduct.productCategory', 'aromaProduct.productType', 'aromaProduct.packagingSize'])
        ->whereIn('quotation_id', $quotationIds)
        ->get();

    $fixedQuotationRooms = 0;

    foreach ($quotationRooms as $quotationRoom) {
        $currentProduct = $quotationRoom->aromaProduct;
        $replacement = $resolveAromaProduct($currentProduct);

        if ($currentProduct && $replacement && (int) $currentProduct->id !== (int) $replacement->id) {
            $this->line("QuotationRoom #{$quotationRoom->id} {$quotationRoom->room_name}: {$currentProduct->name} -> {$replacement->name}");
            if ($apply) {
                $quotationRoom->update([
                    'aroma_product_id' => $replacement->id,
                    'aroma_variant' => $replacement->variant_name,
                    'updated_by' => $auditUserId,
                    'updated_at' => now(),
                ]);
                $fixedQuotationRooms++;
            }
        } elseif (! $currentProduct) {
            $this->warn("QuotationRoom #{$quotationRoom->id} {$quotationRoom->room_name}: aroma is empty; use an AromaChange record or edit SQ to pick the intended variant.");
        }
    }

    $contracts = \App\Models\Contract::whereIn('quotation_id', $quotationIds)->get();
    $contractIds = $contracts->pluck('id')->filter()->all();

    $aromaChanges = \App\Models\AromaChange::with(['newProductType', 'room'])
        ->whereIn('contract_id', $contractIds)
        ->where('status', \App\Models\AromaChange::STATUS_COMPLETED)
        ->get();

    foreach ($aromaChanges as $change) {
        $candidate = \App\Models\MasterProduct::with(['productCategory', 'productType', 'packagingSize'])
            ->find($change->new_product_id ?: $change->new_product_type_id);
        $replacement = $resolveAromaProduct($candidate);
        $targetQuotationRoom = \App\Models\QuotationRoom::whereIn('quotation_id', $quotationIds)
            ->where('room_id', $change->room_id)
            ->with(['aromaProduct.productCategory', 'aromaProduct.productType'])
            ->first();
        $targetNeedsApply = $targetQuotationRoom
            && (! $targetQuotationRoom->aromaProduct
                || ! $isValidAromaProduct($targetQuotationRoom->aromaProduct)
                || ($replacement && (int) $targetQuotationRoom->aroma_product_id !== (int) $replacement->id));

        if ($replacement && ((int) $change->new_product_id !== (int) $replacement->id || $targetNeedsApply)) {
            $this->line("AromaChange {$change->change_number}: new_product_id ".($change->new_product_id ?: 'NULL')." -> {$replacement->id} ({$replacement->name})");
            if ($apply) {
                $change->new_product_id = $replacement->id;
                $change->new_aroma_name = $replacement->variant_name ?: $change->new_aroma_name;
                $change->new_aroma_code = $replacement->variant_name ?: $change->new_aroma_code;
                $change->updated_by = $auditUserId;
                $change->save();
                $change->applyChange($auditUserId);
            }
        }
    }

    $jobAdviceIds = \App\Models\JobAdvice::query()
        ->where(function ($query) use ($quotationIds, $contractIds) {
            $query->whereIn('quotation_id', $quotationIds);
            if (! empty($contractIds)) {
                $query->orWhereIn('contract_id', $contractIds);
            }
        })
        ->pluck('id')
        ->all();

    $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule.jobSchedule', function ($query) use ($jobAdviceIds) {
        $query->whereIn('job_advice_id', $jobAdviceIds);
    })
        ->whereNotIn('status', ['issued', 'sent', 'received', 'completed'])
        ->with('jobAssignMaterialIssues')
        ->get();

    $regeneratedIssues = 0;

    if ($materialIssues->isNotEmpty()) {
        $controller = app(\App\Http\Controllers\Operational\JobAssignMaterialIssueController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('saveMaterialIssueItems');
        $method->setAccessible(true);

        foreach ($materialIssues as $materialIssue) {
            $link = $materialIssue->jobAssignMaterialIssues->first();
            if (! $link) {
                continue;
            }

            $this->line("MaterialIssue {$materialIssue->issue_number}: regenerate items from quotation aroma mapping");
            if ($apply) {
                $method->invoke($controller, $materialIssue, $link, null);
                $regeneratedIssues++;
            }
        }
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist these repairs.');

        return 0;
    }

    $this->info("Done. Fixed {$fixedQuotationRooms} quotation room(s), regenerated {$regeneratedIssues} material issue(s).");

    return 0;
})->purpose('Repair quotation/job-advice/material issue aroma data after aroma switching stored an invalid or empty product');

Artisan::command('operational:repair-inventory-warehouse-scope {--job=} {--apply} {--rebalance-stock}', function () {
    $jobNumber = trim((string) $this->option('job'));
    $apply = (bool) $this->option('apply');
    $rebalanceStock = (bool) $this->option('rebalance-stock');

    if ($jobNumber === '') {
        $this->error('Use --job=<job_number>, example: --job=MKS-IF/26-04/0002');

        return 1;
    }

    $jobs = \App\Models\JobSchedule::with([
        'building.city.province',
        'jobAssignSchedules.team',
        'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.warehouse.branch',
    ])->where('job_number', $jobNumber)->get();

    if ($jobs->isEmpty()) {
        $this->error("Job {$jobNumber} not found.");

        return 1;
    }

    $resolveWarehouseForJob = function ($job) {
        $building = $job->building;
        $branch = null;

        if ($building?->branch_id) {
            $branch = \App\Models\Branch::where('id', $building->branch_id)->where('is_active', true)->first();
        }

        if (! $branch && $building?->city_id) {
            $branch = \App\Models\Branch::where('city_id', $building->city_id)->where('is_active', true)->first();
        }

        if (! $branch && $building?->province_id) {
            $branch = \App\Models\Branch::where('province_id', $building->province_id)->where('is_active', true)->first();
        }

        if (! $branch && $building?->city?->province_id) {
            $branch = \App\Models\Branch::where('province_id', $building->city->province_id)->where('is_active', true)->first();
        }

        if (! $branch) {
            $assignment = $job->jobAssignSchedules->first(fn ($jas) => $jas->team?->branch_office);
            if ($assignment?->team?->branch_office) {
                $branch = \App\Models\Branch::where('id', $assignment->team->branch_office)->where('is_active', true)->first();
            }
        }

        if (! $branch) {
            return [null, null];
        }

        $warehouse = \App\Models\Warehouse::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        return [$branch, $warehouse];
    };

    $materialIssues = collect();
    $targetBranch = null;
    $targetWarehouse = null;

    foreach ($jobs as $job) {
        [$branch, $warehouse] = $resolveWarehouseForJob($job);
        $this->line("Job {$job->job_number}#{$job->id}: building={$job->building?->nama_gedung}, city={$job->building?->city?->name}, target_branch=".($branch?->name ?: '-').', target_warehouse='.($warehouse?->name ?: '-'));

        if (! $branch || ! $warehouse) {
            $this->error("Cannot resolve target warehouse for job {$job->id}.");

            return 1;
        }

        $targetBranch = $targetBranch ?: $branch;
        $targetWarehouse = $targetWarehouse ?: $warehouse;

        foreach ($job->jobAssignSchedules as $assignment) {
            foreach ($assignment->jobAssignMaterialIssues as $link) {
                if ($link->materialIssue) {
                    $materialIssues->push($link->materialIssue);
                }
            }
        }
    }

    $materialIssues = $materialIssues->unique('id')->values();
    if ($materialIssues->isEmpty()) {
        $this->warn('No Material Issue linked to this job.');

        return 0;
    }

    $this->line($apply ? 'APPLY mode: changes will be persisted.' : 'Preview only. Re-run with --apply to persist these repairs.');

    foreach ($materialIssues as $materialIssue) {
        $oldWarehouseId = (int) $materialIssue->warehouse_id;
        $this->line("MaterialIssue {$materialIssue->issue_number}: {$materialIssue->warehouse?->name} -> {$targetWarehouse->name}");

        $issuing = \App\Models\InventoryIssuing::where('reference_no', $materialIssue->issue_number)->first();
        if ($issuing) {
            $this->line("InventoryIssuing {$issuing->issuing_number}: warehouse_id {$issuing->warehouse_id} -> {$targetWarehouse->id}, branch_id {$issuing->branch_id} -> {$targetBranch->id}");
        }

        if (! $apply) {
            continue;
        }

        \DB::transaction(function () use ($materialIssue, $issuing, $oldWarehouseId, $targetWarehouse, $targetBranch, $rebalanceStock) {
            $materialIssue->update([
                'warehouse_id' => $targetWarehouse->id,
                'updated_by' => \Auth::id() ?: $materialIssue->updated_by,
            ]);

            if ($issuing) {
                $issuing->update([
                    'warehouse_id' => $targetWarehouse->id,
                    'branch_id' => $targetBranch->id,
                    'updated_by' => \Auth::id() ?: $issuing->updated_by,
                ]);
            }

            $references = collect([$materialIssue->issue_number, $issuing?->issuing_number])->filter()->values();
            $movements = \App\Models\InventoryMovement::whereIn('reference_no', $references)
                ->where('warehouse_id', $oldWarehouseId)
                ->get();

            foreach ($movements as $movement) {
                if ($rebalanceStock && $movement->quantity != 0) {
                    $qty = abs((float) $movement->quantity);
                    $oldStock = \App\Models\WarehouseProduct::firstOrCreate(
                        ['warehouse_id' => $oldWarehouseId, 'master_product_id' => $movement->master_product_id],
                        ['quantity' => 0, 'minimum_stock' => 0, 'maximum_stock' => 0]
                    );
                    $newStock = \App\Models\WarehouseProduct::firstOrCreate(
                        ['warehouse_id' => $targetWarehouse->id, 'master_product_id' => $movement->master_product_id],
                        ['quantity' => 0, 'minimum_stock' => 0, 'maximum_stock' => 0]
                    );

                    if ($movement->quantity < 0) {
                        $oldStock->increment('quantity', $qty);
                        $newStock->decrement('quantity', $qty);
                    } else {
                        $oldStock->decrement('quantity', $qty);
                        $newStock->increment('quantity', $qty);
                    }
                }

                $movement->update(['warehouse_id' => $targetWarehouse->id]);
            }
        });
    }

    $this->info('Done.');

    return 0;
})->purpose('Repair active job material issue / inventory issuing warehouse scope from job building branch');

Artisan::command('operational:diagnose-warehouse-branch {--job=} {--apply} {--branch=}', function () {
    $jobNumber = trim((string) $this->option('job'));
    $apply = (bool) $this->option('apply');
    $branchOption = trim((string) $this->option('branch'));

    if ($jobNumber === '') {
        $this->error('Use --job=<job_number>, example: --job=JKT-IR/26-04/0011');

        return 1;
    }

    $jobs = \App\Models\JobSchedule::with([
        'building.city.province',
        'building.province',
        'jobAssignSchedules.team',
    ])
        ->where('job_number', $jobNumber)
        ->orderBy('id')
        ->get();

    if ($jobs->isEmpty()) {
        $this->error("Job {$jobNumber} not found.");

        return 1;
    }

    $this->line($apply ? 'APPLY mode: explicit/safe branch assignment may be persisted.' : 'Preview only. Use --apply to persist a safe/explicit building branch assignment.');
    $this->line("Job group {$jobNumber}: {$jobs->count()} schedule row(s)");
    $buildingHasBranchColumn = \Schema::hasColumn('buildings', 'branch_id');

    if (! $buildingHasBranchColumn) {
        $this->warn('Database column buildings.branch_id does not exist yet. Run php artisan migrate before applying building branch mapping.');
    }

    foreach ($jobs as $job) {
        $building = $job->building;
        $cityId = $building?->city_id;
        $provinceId = $building?->province_id ?: $building?->city?->province_id;
        $teamBranchIds = $job->jobAssignSchedules
            ->pluck('team.branch_office')
            ->filter()
            ->unique()
            ->values();

        $this->newLine();
        $this->line("Schedule #{$job->id} {$job->job_number} type={$job->type} status={$job->status}");
        $this->line('Building: '.($building?->id ?: '-').' / '.($building?->nama_gedung ?? $building?->name ?? '-'));
        $this->line('Building branch_id='.($building?->branch_id ?: '-').', city_id='.($cityId ?: '-').', province_id='.($provinceId ?: '-'));
        $this->line('Team branch ids: '.($teamBranchIds->isNotEmpty() ? $teamBranchIds->implode(',') : '-'));

        $candidateBranches = collect();

        if ($building?->branch_id) {
            $candidateBranches = $candidateBranches->merge(\App\Models\Branch::where('id', $building->branch_id)->get()->map(function ($branch) {
                $branch->diagnose_source = 'building.branch_id';

                return $branch;
            }));
        }

        if ($cityId) {
            $candidateBranches = $candidateBranches->merge(\App\Models\Branch::where('city_id', $cityId)->get()->map(function ($branch) {
                $branch->diagnose_source = 'branch.city_id';

                return $branch;
            }));
        }

        if ($provinceId) {
            $candidateBranches = $candidateBranches->merge(\App\Models\Branch::where('province_id', $provinceId)->get()->map(function ($branch) {
                $branch->diagnose_source = 'branch.province_id';

                return $branch;
            }));
        }

        if ($teamBranchIds->isNotEmpty()) {
            $candidateBranches = $candidateBranches->merge(\App\Models\Branch::whereIn('id', $teamBranchIds)->get()->map(function ($branch) {
                $branch->diagnose_source = 'team.branch_id';

                return $branch;
            }));
        }

        if ($branchOption !== '') {
            $explicitBranch = is_numeric($branchOption)
                ? \App\Models\Branch::whereKey((int) $branchOption)->first()
                : \App\Models\Branch::where('code', $branchOption)->orWhere('name', $branchOption)->first();

            if (! $explicitBranch) {
                $this->error("Explicit branch {$branchOption} not found.");

                return 1;
            }

            $explicitBranch->diagnose_source = '--branch';
            $candidateBranches = collect([$explicitBranch]);
        }

        $candidateBranches = $candidateBranches->unique('id')->values();

        if ($candidateBranches->isEmpty()) {
            $this->warn('No branch candidate found from building city/province/team.');

            continue;
        }

        $validBranches = collect();
        foreach ($candidateBranches as $branch) {
            $warehouses = \App\Models\Warehouse::where('branch_id', $branch->id)->orderBy('id')->get();
            $activeWarehouses = $warehouses->where('is_active', true)->values();
            $this->line(sprintf(
                'Candidate branch #%s %s (%s) source=%s active=%s warehouses=%s active_warehouses=%s',
                $branch->id,
                $branch->code ?: '-',
                $branch->name ?: '-',
                $branch->diagnose_source ?? '-',
                $branch->is_active ? 'yes' : 'no',
                $warehouses->pluck('name')->implode(', ') ?: '-',
                $activeWarehouses->pluck('name')->implode(', ') ?: '-'
            ));

            if ($branch->is_active && $activeWarehouses->isNotEmpty()) {
                $validBranches->push($branch);
            }
        }

        if ($validBranches->count() === 1) {
            $targetBranch = $validBranches->first();
            $this->info("Recommended: set building #{$building?->id} branch_id={$targetBranch->id} ({$targetBranch->code} - {$targetBranch->name})");

            if ($apply && ! $buildingHasBranchColumn) {
                $this->error('Cannot apply: buildings.branch_id column is missing. Deploy migration and run php artisan migrate first.');

                return 1;
            }

            if ($apply && $building && ((int) $building->branch_id !== (int) $targetBranch->id)) {
                $building->update([
                    'branch_id' => $targetBranch->id,
                    'updated_by' => \Auth::id() ?: $building->updated_by,
                ]);
                $this->info("Applied: building #{$building->id} branch_id set to {$targetBranch->id}.");
            }
        } elseif ($validBranches->count() > 1) {
            $this->warn('Multiple valid branches found. Re-run with explicit --branch=<id/code/name> before --apply.');
        } else {
            $this->error('No valid active branch with active warehouse found. Fix branch/warehouse master data first.');
        }
    }

    return 0;
})->purpose('Diagnose job building branch/warehouse resolution and optionally assign a safe explicit branch');

Artisan::command('operational:diagnose-repair-grouped-job-rooms {--job=} {--apply} {--reopen-status=in_progress}', function () {
    $jobNumber = trim((string) $this->option('job'));
    $apply = (bool) $this->option('apply');
    $reopenStatus = trim((string) $this->option('reopen-status')) ?: 'in_progress';
    $allowedReopenStatuses = ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_tiba_dilokasi'];

    if ($jobNumber === '') {
        $this->error('Use --job=<job_number>, example: --job=JKT-IF/26-04/0019');

        return 1;
    }

    if (! in_array($reopenStatus, $allowedReopenStatuses, true)) {
        $this->error('Invalid --reopen-status. Allowed: '.implode(', ', $allowedReopenStatuses));

        return 1;
    }

    $terminalStatuses = ['done_job', 'completed', 'selesai'];
    $jobs = \App\Models\JobSchedule::with([
        'jobAdvice.rooms',
        'jobScheduleRooms.completedBy',
        'jobScheduleRooms.roomAssignment.team',
        'jobAssignSchedules.team',
    ])
        ->where('job_number', $jobNumber)
        ->orderBy('id')
        ->get();

    if ($jobs->isEmpty()) {
        $this->error("Job {$jobNumber} not found.");

        return 1;
    }

    $this->line($apply ? 'APPLY mode: inconsistent parent statuses will be reopened.' : 'Preview only. Re-run with --apply to persist repairs.');
    $this->line("Job group {$jobNumber}: {$jobs->count()} schedule row(s)");

    $fixed = 0;

    foreach ($jobs as $job) {
        $rooms = $job->jobScheduleRooms;
        $totalRooms = $rooms->count();
        $completedRooms = $rooms->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)->count();
        $activeTeams = $job->jobAssignSchedules
            ->where('status', '!=', 'cancelled')
            ->pluck('team.team_name')
            ->filter()
            ->unique()
            ->implode(', ');

        $this->line(sprintf(
            'Schedule #%s type=%s room_id=%s status=%s rooms=%s/%s team=%s',
            $job->id,
            $job->type ?: '-',
            $job->room_id ?: '-',
            $job->status ?: '-',
            $completedRooms,
            $totalRooms,
            $activeTeams ?: '-'
        ));

        if ($totalRooms === 0 && $job->jobAdvice && $job->jobAdvice->rooms->isNotEmpty()) {
            $this->warn("  WARN: no job_schedule_rooms, but Job Advice has {$job->jobAdvice->rooms->count()} room(s). This schedule must not be auto-completed.");
        }

        foreach ($rooms as $room) {
            $hasBefore = \App\Models\JobPhoto::where('job_schedule_room_id', $room->id)->where('photo_type', 'Before Work')->exists();
            $hasAfter = \App\Models\JobPhoto::where('job_schedule_room_id', $room->id)->where('photo_type', 'After Work')->exists();
            $roomTeam = $room->roomAssignment?->team?->team_name;

            $this->line(sprintf(
                '  Room #%s "%s": status=%s room_id=%s team=%s photos=%s/%s completed_by=%s',
                $room->id,
                $room->room_name ?: '-',
                $room->status ?: '-',
                $room->room_id ?: '-',
                $roomTeam ?: '-',
                $hasBefore ? 'before' : 'no-before',
                $hasAfter ? 'after' : 'no-after',
                $room->completedBy?->name ?: '-'
            ));
        }

        $hasUnfinishedRooms = $totalRooms > 0
            && $rooms->where('status', '!=', \App\Models\JobScheduleRoom::STATUS_COMPLETED)->isNotEmpty();
        $hasMissingRoomTracking = $totalRooms === 0 && $job->jobAdvice && $job->jobAdvice->rooms->isNotEmpty();

        if (in_array($job->status, $terminalStatuses, true) && ($hasUnfinishedRooms || $hasMissingRoomTracking)) {
            $this->warn("  FIX NEEDED: parent status {$job->status} is terminal while room tracking is unfinished/incomplete. Reopen to {$reopenStatus}.");

            if ($apply) {
                $job->update([
                    'status' => $reopenStatus,
                    'completed_at' => null,
                    'updated_by' => \Auth::id() ?: $job->updated_by,
                ]);
                $fixed++;
            }
        }
    }

    if ($apply) {
        $this->info("Done. Reopened {$fixed} inconsistent job schedule(s).");
    }

    return 0;
})->purpose('Diagnose and repair grouped job rooms where parent job is done while child rooms are pending');

Artisan::command('operational:repair-service-period-room-scope {--job=} {--apply}', function () {
    $jobNumber = trim((string) $this->option('job'));
    $apply = (bool) $this->option('apply');
    $serviceTypes = ['service', 'service_first', 'service_routine', 'csr'];

    if ($jobNumber === '') {
        $this->error('Use --job=<completed CSR/service job number>, example: --job=JKT-CSR/26-04/0012');

        return 1;
    }

    $completedService = \App\Models\JobSchedule::with(['jobAdvice.rooms', 'jobScheduleRooms'])
        ->where('job_number', $jobNumber)
        ->whereIn('type', $serviceTypes)
        ->first();

    if (! $completedService) {
        $this->error("Service/CSR job {$jobNumber} not found.");

        return 1;
    }

    if (! in_array($completedService->status, ['done_job', 'completed', 'selesai', 'teknisi_selesai_pengerjaan'], true)) {
        $this->warn("Job {$jobNumber} status is {$completedService->status}; expected completed/done/technician-finished service.");
    }

    $eligibleRoomIds = $completedService->jobScheduleRooms
        ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
        ->pluck('room_id')
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    if ($eligibleRoomIds->isEmpty()) {
        $this->error("No completed rooms found on {$jobNumber}; cannot determine allowed service period room scope.");

        return 1;
    }

    $this->line($apply ? 'APPLY mode: ineligible rooms will be removed from future service schedules.' : 'Preview only. Re-run with --apply to persist repairs.');
    $this->line("Source {$completedService->job_number}: JA={$completedService->jobAdvice?->job_advice_number}, period=".($completedService->period ?: '-').', eligible_room_ids='.$eligibleRoomIds->implode(','));

    $futureQuery = \App\Models\JobSchedule::with('jobScheduleRooms')
        ->where('job_advice_id', $completedService->job_advice_id)
        ->whereIn('type', $serviceTypes)
        ->where('id', '!=', $completedService->id)
        ->whereNotIn('status', ['done_job', 'completed', 'selesai', 'cancelled', 'suspend', 'dpf']);

    if (is_numeric($completedService->period)) {
        $futureQuery->where(function ($query) use ($completedService) {
            $query->whereNull('period')
                ->orWhere('period', '>', (int) $completedService->period);
        });
    } elseif ($completedService->schedule_date) {
        $futureQuery->whereDate('schedule_date', '>=', $completedService->schedule_date->toDateString());
    }

    $futureSchedules = $futureQuery->orderBy('period')->orderBy('schedule_date')->orderBy('id')->get();

    if ($futureSchedules->isEmpty()) {
        $this->info('No future active service schedules found to repair.');

        return 0;
    }

    $removedRooms = 0;

    foreach ($futureSchedules as $schedule) {
        $rooms = $schedule->jobScheduleRooms;
        $badRooms = $rooms->filter(fn ($room) => $room->room_id && ! $eligibleRoomIds->contains((int) $room->room_id));
        $goodRooms = $rooms->filter(fn ($room) => ! $room->room_id || $eligibleRoomIds->contains((int) $room->room_id));

        $this->line(sprintf(
            'Future schedule #%s %s period=%s status=%s rooms=%s good=%s bad=%s',
            $schedule->id,
            $schedule->job_number ?: '(no job no)',
            $schedule->period ?: '-',
            $schedule->status ?: '-',
            $rooms->count(),
            $goodRooms->count(),
            $badRooms->count()
        ));

        foreach ($badRooms as $badRoom) {
            $this->warn("  REMOVE room #{$badRoom->id} {$badRoom->room_name} room_id={$badRoom->room_id}");
        }

        if (! $apply || $badRooms->isEmpty()) {
            continue;
        }

        \DB::transaction(function () use ($badRooms, &$removedRooms) {
            foreach ($badRooms as $badRoom) {
                $jobAdviceRoomIds = \App\Models\JobScheduleRoomRental::where('job_schedule_room_id', $badRoom->id)
                    ->pluck('job_advice_room_id')
                    ->filter()
                    ->push($badRoom->job_advice_room_id)
                    ->filter()
                    ->unique()
                    ->values();

                if ($jobAdviceRoomIds->isNotEmpty()) {
                    \App\Models\JobAdviceRoom::whereIn('id', $jobAdviceRoomIds)
                        ->where('service_job_schedule_id', $badRoom->job_schedule_id)
                        ->update(['service_job_schedule_id' => null]);
                }

                \App\Models\JobScheduleRoomRental::where('job_schedule_room_id', $badRoom->id)->delete();
                $badRoom->delete();
                $removedRooms++;
            }
        });
    }

    if ($apply) {
        $this->info("Done. Removed {$removedRooms} ineligible room(s) from future service schedules.");
    }

    return 0;
})->purpose('Repair future service periods so only rooms completed in the source CSR/service job are carried forward');

Artisan::command('warehouse:repair-empty-receiving-from-issuing {--receiving=} {--all} {--apply}', function () {
    $receivingNumber = trim((string) $this->option('receiving'));
    $repairAll = (bool) $this->option('all');
    $apply = (bool) $this->option('apply');

    if ($receivingNumber === '' && ! $repairAll) {
        $this->error('Use --receiving=<receiving_number> or --all. Add --apply to persist repairs.');
        $this->line('Example: php artisan warehouse:repair-empty-receiving-from-issuing --receiving=JKT-RR/26-04/0034');

        return 1;
    }

    $query = \App\Models\InventoryReceiving::with(['issuing.items.product', 'items'])
        ->whereNotNull('issuing_id')
        ->whereDoesntHave('items')
        ->orderBy('id');

    if ($receivingNumber !== '') {
        $query->where('receiving_number', $receivingNumber);
    }

    $receivings = $query->get();

    if ($receivings->isEmpty()) {
        $this->info('No empty receiving records linked to inventory issuing were found.');

        return 0;
    }

    $buildItemsFromIssuing = function ($issuing): array {
        if (! $issuing) {
            return [];
        }

        return $issuing->items
            ->map(function ($item) use ($issuing) {
                if (! $item->product_id) {
                    return null;
                }

                $issuedQty = (float) $item->quantity_issued;
                $requestedQty = (float) $item->quantity_requested;
                $quantity = $issuedQty > 0 ? $issuedQty : $requestedQty;

                if ($quantity <= 0) {
                    return null;
                }

                $notes = "Repaired from Inventory Issuing {$issuing->issuing_number}";
                if ($item->room_name) {
                    $notes .= "; Room: {$item->room_name}";
                }
                $notes .= "; WI Item: {$item->id}";
                if ($item->notes) {
                    $notes .= "; {$item->notes}";
                }

                return [
                    'master_product_id' => $item->product_id,
                    'quantity' => $quantity,
                    'quantity_received' => 0,
                    'notes' => $notes,
                ];
            })
            ->filter()
            ->values()
            ->all();
    };

    $this->line($apply ? 'APPLY mode: receiving items will be created.' : 'Preview only. Re-run with --apply to persist these repairs.');

    $repairedReceivings = 0;
    $createdItems = 0;
    $skipped = 0;

    foreach ($receivings as $receiving) {
        $movementExists = \App\Models\InventoryMovement::where('reference_no', $receiving->receiving_number)->exists();
        $candidateItems = $buildItemsFromIssuing($receiving->issuing);

        $this->line(sprintf(
            '%s | status=%s | ref=%s | issuing=%s | source_items=%d | movements=%s',
            $receiving->receiving_number,
            $receiving->status,
            $receiving->reference_no ?: '-',
            $receiving->issuing?->issuing_number ?: '-',
            count($candidateItems),
            $movementExists ? 'yes' : 'no'
        ));

        if ($receiving->status !== 'pending') {
            $this->warn('  skip: receiving is not pending.');
            $skipped++;

            continue;
        }

        if ($movementExists) {
            $this->warn('  skip: receiving already has inventory movements.');
            $skipped++;

            continue;
        }

        if (empty($candidateItems)) {
            $this->warn('  skip: linked issuing has no usable item quantity.');
            $skipped++;

            continue;
        }

        foreach ($candidateItems as $item) {
            $productName = \App\Models\MasterProduct::whereKey($item['master_product_id'])->value('name') ?: $item['master_product_id'];
            $this->line("  item: {$productName} qty={$item['quantity']}");
        }

        if (! $apply) {
            continue;
        }

        \DB::transaction(function () use ($receiving, $buildItemsFromIssuing, &$repairedReceivings, &$createdItems) {
            $lockedReceiving = \App\Models\InventoryReceiving::with(['issuing.items'])
                ->whereKey($receiving->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedReceiving || $lockedReceiving->status !== 'pending' || $lockedReceiving->items()->exists()) {
                return;
            }

            if (\App\Models\InventoryMovement::where('reference_no', $lockedReceiving->receiving_number)->exists()) {
                return;
            }

            $items = $buildItemsFromIssuing($lockedReceiving->issuing);
            if (empty($items)) {
                return;
            }

            foreach ($items as $item) {
                $lockedReceiving->items()->create($item);
                $createdItems++;
            }

            $repairedReceivings++;
        });
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist these repairs.');

        return 0;
    }

    $this->info("Done. Repaired {$repairedReceivings} receiving record(s), created {$createdItems} item row(s), skipped {$skipped}.");

    return 0;
})->purpose('Backfill empty pending inventory receivings from their linked inventory issuing items');

Artisan::command('warehouse:repair-receiving-sn-stock {--receiving=} {--apply}', function () {
    $receivingNumber = trim((string) $this->option('receiving'));
    $apply = (bool) $this->option('apply');

    if ($receivingNumber === '') {
        $this->error('Use --receiving=<receiving_number>, example: --receiving=JKT-IRC/26-04/0008');

        return 1;
    }

    $receiving = \App\Models\InventoryReceiving::with(['items.product.productCategory', 'items.product.productType', 'issuing.warehouse'])
        ->where('receiving_number', $receivingNumber)
        ->first();

    if (! $receiving) {
        $this->error("Receiving {$receivingNumber} not found.");

        return 1;
    }

    $warehouse = $receiving->issuing?->warehouse;
    if (! $warehouse && $receiving->branch_id) {
        $warehouse = \App\Models\Warehouse::where('branch_id', $receiving->branch_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    if (! $warehouse) {
        $this->error('Cannot resolve warehouse for receiving stock correction.');

        return 1;
    }

    $this->line("Receiving {$receiving->receiving_number}: status={$receiving->status}, reference={$receiving->reference_no}, warehouse={$warehouse->name}");
    $this->line($apply ? 'APPLY mode: changes will be persisted.' : 'Preview only. Re-run with --apply to persist these repairs.');

    $productRequiresSn = function ($product): bool {
        return (bool) ($product?->requiresSerialNumber()
            ?? $product?->productCategory?->has_serial_number
            ?? $product?->productType?->has_serial_number
            ?? false);
    };

    $fixedProducts = 0;

    foreach ($receiving->items->groupBy('master_product_id') as $productId => $items) {
        $firstItem = $items->first();
        $snCount = \App\Models\SerialNumber::where('inventory_receiving_id', $receiving->id)
            ->where('master_product_id', $productId)
            ->count();

        if (! $productRequiresSn($firstItem->product) && $snCount <= 0) {
            continue;
        }

        $requestedQty = (float) $items->sum('quantity');
        $movementQty = (float) \App\Models\InventoryMovement::where('reference_no', $receiving->receiving_number)
            ->where('reference_type', 'inventory_receiving')
            ->where('warehouse_id', $warehouse->id)
            ->where('master_product_id', $productId)
            ->sum('quantity');

        $correctionQty = (float) \App\Models\InventoryMovement::where('reference_no', $receiving->receiving_number)
            ->where('reference_type', 'inventory_receiving_correction')
            ->where('warehouse_id', $warehouse->id)
            ->where('master_product_id', $productId)
            ->sum('quantity');

        $receivedQty = (float) $items->sum('quantity_received');
        $netMovementQty = $movementQty + $correctionQty;
        $creditedQty = $netMovementQty > 0 ? $netMovementQty : ($receivedQty > 0 ? $receivedQty : ($receiving->status === 'received' ? $requestedQty : 0));
        $targetQty = (float) $snCount;
        $delta = max(0, $creditedQty - $targetQty);

        $this->line(sprintf(
            '%s: requested=%s, SN=%s, credited=%s, target_received=%s, stock_delta_to_reverse=%s',
            $firstItem->product?->name ?? $productId,
            $requestedQty,
            $snCount,
            $creditedQty,
            $targetQty,
            $delta
        ));

        if (! $apply || $delta <= 0) {
            continue;
        }

        \DB::transaction(function () use ($receiving, $warehouse, $productId, $items, $snCount, $delta) {
            $remaining = $snCount;
            foreach ($items->sortBy('id') as $item) {
                $allocated = min((float) $item->quantity, $remaining);
                $item->update(['quantity_received' => $allocated]);
                $remaining -= $allocated;
            }

            $warehouseProduct = \App\Models\WarehouseProduct::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'master_product_id' => $productId],
                ['quantity' => 0, 'minimum_stock' => 0, 'maximum_stock' => 0]
            );
            $warehouseProduct->decrement('quantity', $delta);

            \App\Models\InventoryMovement::create([
                'warehouse_id' => $warehouse->id,
                'master_product_id' => $productId,
                'movement_type' => 'out',
                'quantity' => -abs($delta),
                'movement_date' => now()->toDateString(),
                'reference_no' => $receiving->receiving_number,
                'reference_type' => 'inventory_receiving_correction',
                'movement_no' => 'IRCOR-'.$receiving->id.'-'.$productId.'-'.now()->format('His'),
                'notes' => 'Correction: receiving credited more stock than registered serial numbers.',
                'created_by' => \Auth::id() ?: (\App\Models\User::query()->orderBy('id')->value('id') ?: 1),
                'updated_by' => \Auth::id() ?: (\App\Models\User::query()->orderBy('id')->value('id') ?: 1),
            ]);

            if ($receiving->reference_no) {
                $request = \App\Models\InventoryRequest::where('request_number', $receiving->reference_no)->first();
                if ($request) {
                    $requestItems = $request->items()->where('master_product_id', $productId)->orderBy('id')->get();
                    $remainingReceived = $snCount;
                    foreach ($requestItems as $requestItem) {
                        $received = min((float) $requestItem->quantity, $remainingReceived);
                        $issued = (float) ($requestItem->issued_qty ?? $requestItem->quantity ?? 0);
                        $requestItem->update([
                            'received_qty' => $received,
                            'returned_qty' => max(0, $issued - $received),
                            'updated_by' => \Auth::id() ?: $requestItem->updated_by,
                        ]);
                        $remainingReceived -= $received;
                    }
                }
            }
        });

        $fixedProducts++;
    }

    if (! $apply) {
        $this->warn('Preview only. Re-run with --apply to persist these repairs.');

        return 0;
    }

    $this->info("Done. Corrected {$fixedProducts} SN product group(s).");

    return 0;
})->purpose('Repair received SN products where stock quantity exceeded registered serial numbers');

Artisan::command('warehouse:repair-stale-issued-sn-links {--job=} {--wi=} {--sn=} {--apply}', function () {
    $jobNumber = trim((string) $this->option('job'));
    $issuingNumber = trim((string) $this->option('wi'));
    $serialNumberFilter = trim((string) $this->option('sn'));
    $apply = (bool) $this->option('apply');

    $query = \App\Models\InventoryIssuingItem::with([
        'inventoryIssuing',
        'serialNumber.inventoryReceiving',
        'product',
    ])->whereNotNull('serial_number_id');

    if ($issuingNumber !== '') {
        $query->whereHas('inventoryIssuing', function ($subQuery) use ($issuingNumber) {
            $subQuery->where('issuing_number', $issuingNumber)
                ->orWhere('reference_no', $issuingNumber);
        });
    }

    if ($serialNumberFilter !== '') {
        $query->whereHas('serialNumber', function ($subQuery) use ($serialNumberFilter) {
            $subQuery->where('serial_number', $serialNumberFilter);
        });
    }

    if ($jobNumber !== '') {
        $jobScheduleIds = \App\Models\JobSchedule::where('job_number', $jobNumber)->pluck('id');
        if ($jobScheduleIds->isEmpty()) {
            $this->error("Job {$jobNumber} not found.");

            return 1;
        }

        $jobAssignScheduleIds = \App\Models\JobAssignSchedule::whereIn('job_schedule_id', $jobScheduleIds)->pluck('id');
        if ($jobAssignScheduleIds->isEmpty()) {
            $this->error("Job {$jobNumber} has no job assignment rows.");

            return 1;
        }

        $query->whereIn('job_assign_schedule_id', $jobAssignScheduleIds);
    }

    $items = $query->orderBy('inventory_issuing_id')->orderBy('id')->get();

    if ($items->isEmpty()) {
        $this->info('No issuing items with serial numbers found for the given filter.');

        return 0;
    }

    $this->line($apply ? 'APPLY mode: stale SN links will be released from old Inventory Issuing items.' : 'Preview only. Re-run with --apply to persist these repairs.');
    $this->line('Scope: '
        .($jobNumber !== '' ? "job={$jobNumber} " : '')
        .($issuingNumber !== '' ? "wi={$issuingNumber} " : '')
        .($serialNumberFilter !== '' ? "sn={$serialNumberFilter}" : '')
    );

    $released = 0;
    $skipped = 0;

    foreach ($items as $item) {
        $issuing = $item->inventoryIssuing;
        $serialNumber = $item->serialNumber;
        $receiving = $serialNumber?->inventoryReceiving;
        $jobAssignSchedule = $item->job_assign_schedule_id
            ? \App\Models\JobAssignSchedule::with('jobSchedule')->find($item->job_assign_schedule_id)
            : null;
        $job = $jobAssignSchedule?->jobSchedule;

        if (! $issuing || ! $serialNumber) {
            $this->warn("SKIP item #{$item->id}: missing issuing or serial number relation.");
            $skipped++;

            continue;
        }

        $isWarehouseReady = in_array($serialNumber->status, ['ready', 'available'], true)
            && ($serialNumber->effective_location_type === 'warehouse');
        $hasReceivingBacklink = ! empty($serialNumber->inventory_receiving_id);
        $isStillBlockingStatus = in_array($issuing->status, ['pending', 'processed', 'sent', 'received'], true);
        $hasActiveUnitOnWall = \App\Models\UnitOnWall::where('serial_number_id', $serialNumber->id)
            ->where('status', 'active')
            ->exists();

        $looksStale = $isWarehouseReady
            && $hasReceivingBacklink
            && $isStillBlockingStatus
            && ! $hasActiveUnitOnWall;

        $this->line(sprintf(
            'Item #%s | WI=%s (%s) | Job=%s | Product=%s | SN=%s | sn_status=%s | sn_location=%s | receiving=%s | stale=%s',
            $item->id,
            $issuing->issuing_number ?? '-',
            $issuing->status ?? '-',
            $job?->job_number ?? '-',
            $item->product?->name ?? $item->product_id ?? '-',
            $serialNumber->serial_number ?? '-',
            $serialNumber->status ?? '-',
            $serialNumber->effective_location_type ?? '-',
            $receiving?->receiving_number ?? ($serialNumber->inventory_receiving_id ?: '-'),
            $looksStale ? 'yes' : 'no'
        ));

        if (! $looksStale) {
            $skipReasons = [];
            if (! $isWarehouseReady) {
                $skipReasons[] = 'SN is not ready in warehouse';
            }
            if (! $hasReceivingBacklink) {
                $skipReasons[] = 'SN has no receiving backlink';
            }
            if (! $isStillBlockingStatus) {
                $skipReasons[] = 'WI status is not active/blocking';
            }
            if ($hasActiveUnitOnWall) {
                $skipReasons[] = 'SN still active on unit on wall';
            }

            $this->warn('  SKIP: '.implode('; ', $skipReasons));
            $skipped++;

            continue;
        }

        if (! $apply) {
            continue;
        }

        \DB::transaction(function () use ($item, $serialNumber, $job, &$released) {
            $item->update([
                'serial_number_id' => null,
                'updated_by' => \Auth::id() ?: $item->updated_by,
                'notes' => trim((string) $item->notes.' | Repair stale SN link on '.now()->format('Y-m-d H:i:s').' | Released SN: '.($serialNumber->serial_number ?? '-').($job?->job_number ? ' | Source Job: '.$job->job_number : '')),
            ]);

            $released++;
        });

        $this->info("  RELEASED: {$serialNumber->serial_number} detached from issuing item #{$item->id}");
    }

    if (! $apply) {
        $this->warn('Preview complete. Candidate stale links: '.($items->count() - $skipped).", skipped: {$skipped}.");
        $this->warn('Re-run with --apply to persist these repairs.');

        return 0;
    }

    $this->info("Done. Released {$released} stale SN link(s). Skipped {$skipped} item(s).");

    return 0;
})->purpose('Release stale serial-number links from old inventory issuing items after SN has already returned to warehouse');
