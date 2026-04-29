<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportCatalystUsersFromExport extends Command
{
    protected $signature = 'catalyst:import-users-export
                            {--file=storage/app/catalyst/users_export.csv : CSV export file from PinkAds}
                            {--apply : Persist changes to target tables}
                            {--password=password123 : Default password for inserted users only}';

    protected $description = 'Import Catalyst users from CSV export without relying on PHP sqlsrv connectivity';

    public function handle(): int
    {
        $file = $this->resolveFilePath((string) $this->option('file'));
        if (!is_file($file)) {
            $this->error('File export user tidak ditemukan: ' . $file);
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $mode = $apply ? 'apply' : 'dry-run';
        $this->info('Running Catalyst user import from export in ' . $mode . ' mode.');
        $userColumns = array_flip(Schema::getColumnListing('users'));
        $branchUserColumns = Schema::hasTable('branch_user')
            ? array_flip(Schema::getColumnListing('branch_user'))
            : [];

        $branchMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsBranch')
            ->where('target_table', 'branches')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $departmentMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsDepartment')
            ->where('target_table', 'departments')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            $this->error('File export user tidak bisa dibuka.');
            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->error('File export user kosong.');
            return self::FAILURE;
        }

        $header = array_map(fn ($value) => $this->normalizeHeader($value), $header);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = $data[$index] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);

        $usedUsernames = DB::table('users')
            ->whereNull('deleted_at')
            ->pluck('username')
            ->filter()
            ->mapWithKeys(fn ($value) => [Str::lower(trim((string) $value)) => true])
            ->all();

        $usedEmails = DB::table('users')
            ->whereNull('deleted_at')
            ->pluck('email')
            ->filter()
            ->mapWithKeys(fn ($value) => [Str::lower(trim((string) $value)) => true])
            ->all();

        $stats = [
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'branch_pivot_synced' => 0,
        ];

        foreach ($rows as $row) {
            $stats['processed']++;

            $empNumb = $this->cleanString($row['empnumb'] ?? null);
            $name = $this->cleanString($row['empname'] ?? null);

            if (!$empNumb || !$name) {
                $stats['skipped']++;
                continue;
            }

            $sourceKey = $empNumb;
            $sourceMap = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MsEmployee')
                ->where('source_key', $sourceKey)
                ->where('target_table', 'users')
                ->first();

            $existingUser = null;
            if ($sourceMap?->target_id) {
                $existingUser = DB::table('users')->where('id', $sourceMap->target_id)->first();
            }
            if (!$existingUser) {
                $existingUser = DB::table('users')->where('nik', $empNumb)->first();
            }

            $assignedBranchCodes = $this->splitCodes($row['assignedbranchcodes'] ?? null);
            $assignedDepartmentCodes = $this->splitCodes($row['assigneddepartmentcodes'] ?? null);

            $branchCode = $this->cleanString($row['employeebranch'] ?? null)
                ?: $this->cleanString($row['loginbranchcode'] ?? null)
                ?: ($assignedBranchCodes[0] ?? null);
            $departmentCode = $this->cleanString($row['employeedepartment'] ?? null)
                ?: ($assignedDepartmentCodes[0] ?? null);

            $branchId = $branchCode ? ($branchMap[$branchCode] ?? null) : null;
            $departmentId = $departmentCode ? ($departmentMap[$departmentCode] ?? null) : null;

            $baseUsername = $existingUser?->username
                ?: $this->cleanString($row['username'] ?? null)
                ?: $this->cleanString($row['loginuserid'] ?? null)
                ?: strtolower(preg_replace('/[^a-z0-9._]/i', '', str_replace(' ', '.', $name)));
            $baseUsername = strtolower($baseUsername ?: ('emp' . $empNumb));
            $username = $this->resolveUniqueValue($baseUsername, $usedUsernames, $existingUser?->username, $existingUser?->id);

            $email = $this->cleanString($row['email'] ?? null);
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = 'emp' . strtolower($empNumb) . '@pinkads.internal';
            }
            $email = $this->resolveUniqueEmail($email, $usedEmails, $existingUser?->email, $empNumb, $existingUser?->id);

            $isActive = $this->yesNoToBool($row['employeeactive'] ?? null, true)
                && $this->yesNoToBool($row['loginactive'] ?? null, true);

            $hasBpjs = $this->yesNoToBool($row['fgjamsostek'] ?? null, false);
            $bpjsDate = $hasBpjs ? $this->toDate($row['jamsostekdate'] ?? null) : null;
            if ($bpjsDate && $bpjsDate->lt(Carbon::create(1950, 1, 1))) {
                $bpjsDate = null;
            }

            $payload = [
                'nik' => $empNumb,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'join_date' => $this->toDate($row['hiredate'] ?? null),
                'phone' => $this->cleanString($row['resphone'] ?? null),
                'handphone_1' => $this->cleanString($row['handphone'] ?? null),
                'address_1' => $this->cleanString($row['resaddr'] ?? null),
                'address_2' => $this->cleanString($row['oriaddr'] ?? null),
                'identity_number' => $this->cleanString($row['idcard'] ?? null),
                'npwp_number' => $this->cleanString($row['npwpno'] ?? null),
                'bpjs_number' => $hasBpjs ? $this->cleanString($row['jamsostekno'] ?? null) : null,
                'bpjs_date' => $bpjsDate,
                'position_name' => $this->cleanString($row['jobtitlename'] ?? null) ?: $this->cleanString($row['jobtitle'] ?? null),
                'department_id' => $departmentId,
                'department_name' => $departmentId ? null : $departmentCode,
                'branch_id' => $branchId,
                'branch_name' => $branchId ? null : $branchCode,
                'is_active' => $isActive,
                'data_restriction' => 'none',
                'description' => $this->buildDescription([
                    'EmpNumb' => $empNumb,
                    'LoginUserId' => $this->cleanString($row['loginuserid'] ?? null),
                    'EmployeeDepartment' => $this->cleanString($row['employeedepartment'] ?? null),
                    'EmployeeBranch' => $this->cleanString($row['employeebranch'] ?? null),
                    'AssignedDepartments' => $assignedDepartmentCodes !== [] ? implode('|', $assignedDepartmentCodes) : null,
                    'AssignedBranches' => $assignedBranchCodes !== [] ? implode('|', $assignedBranchCodes) : null,
                ]),
                'updated_at' => now(),
            ];
            $payload = $this->onlyColumns($payload, $userColumns);

            if (!$apply) {
                if ($existingUser) {
                    $stats['updated']++;
                } else {
                    $stats['inserted']++;
                }
                continue;
            }

            if ($existingUser) {
                DB::table('users')->where('id', $existingUser->id)->update($this->onlyColumns(array_merge($payload, [
                    'updated_by' => auth()->id(),
                ]), $userColumns));
                $targetId = (int) $existingUser->id;
                $stats['updated']++;
            } else {
                $targetId = (int) DB::table('users')->insertGetId($this->onlyColumns(array_merge($payload, [
                    'password' => Hash::make((string) $this->option('password')),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                ]), $userColumns));
                $stats['inserted']++;
            }

            DB::table('source_import_maps')->updateOrInsert(
                [
                    'source_system' => 'catalyst',
                    'source_table' => 'MsEmployee',
                    'source_key' => $sourceKey,
                    'target_table' => 'users',
                ],
                [
                    'target_id' => $targetId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            if (Schema::hasTable('branch_user')) {
                $branchIds = collect($assignedBranchCodes)
                    ->prepend($branchCode)
                    ->filter()
                    ->unique()
                    ->map(fn ($code) => $branchMap[$code] ?? null)
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($branchIds->isNotEmpty()) {
                    DB::table('branch_user')
                        ->where('user_id', $targetId)
                        ->whereNotIn('branch_id', $branchIds->all())
                        ->delete();

                    foreach ($branchIds as $id) {
                        DB::table('branch_user')->updateOrInsert(
                            [
                                'user_id' => $targetId,
                                'branch_id' => $id,
                            ],
                            $this->onlyColumns([
                                'is_primary' => $branchId === $id,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ], $branchUserColumns)
                        );
                    }

                    $stats['branch_pivot_synced']++;
                }
            }
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $mode],
                ['Processed', $stats['processed']],
                ['Inserted', $stats['inserted']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Failed', $stats['failed']],
                ['Branch Pivot Synced', $stats['branch_pivot_synced']],
            ]
        );

        $this->warn('Password default hanya dipasang untuk user baru. User lama tidak di-reset.');

        return self::SUCCESS;
    }

    private function normalizeHeader($value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        return Str::lower(trim((string) $value, "\" \t\n\r\0\x0B"));
    }

    private function cleanString($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function splitCodes($value): array
    {
        $value = $this->cleanString($value);
        if (!$value) {
            return [];
        }

        return collect(explode('|', $value))
            ->map(fn ($item) => $this->cleanString($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function yesNoToBool($value, bool $default = false): bool
    {
        $value = Str::upper(trim((string) $value));
        return match ($value) {
            'Y', 'YES', '1', 'TRUE' => true,
            'N', 'NO', '0', 'FALSE' => false,
            default => $default,
        };
    }

    private function toDate($value): ?Carbon
    {
        $value = $this->cleanString($value);
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveUniqueValue(string $baseValue, array &$used, ?string $currentValue = null, ?int $currentUserId = null): string
    {
        $baseValue = trim(Str::lower($baseValue));
        $candidate = $baseValue;
        if ($currentValue && Str::lower($currentValue) === $candidate) {
            $used[$candidate] = true;
            return $currentValue;
        }

        $suffix = 1;
        while (isset($used[$candidate]) || $this->usernameExists($candidate, $currentUserId)) {
            $candidate = $baseValue . $suffix;
            $suffix++;
        }

        $used[$candidate] = true;
        return $candidate;
    }

    private function resolveUniqueEmail(string $email, array &$used, ?string $currentValue, string $empNumb, ?int $currentUserId = null): string
    {
        $candidate = Str::lower(trim($email));
        if ($currentValue && Str::lower($currentValue) === $candidate) {
            $used[$candidate] = true;
            return $currentValue;
        }

        if (!isset($used[$candidate]) && !$this->emailExists($candidate, $currentUserId)) {
            $used[$candidate] = true;
            return $email;
        }

        $base = 'emp' . Str::lower($empNumb);
        $suffix = 0;
        do {
            $suffix++;
            $candidate = $base . ($suffix > 1 ? $suffix : '') . '@pinkads.internal';
        } while (isset($used[Str::lower($candidate)]) || $this->emailExists($candidate, $currentUserId));

        $used[Str::lower($candidate)] = true;
        return $candidate;
    }

    private function resolveFilePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return base_path('storage/app/catalyst/users_export.csv');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        }

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, base_path($path));
    }

    private function buildDescription(array $pairs): ?string
    {
        $parts = [];
        foreach ($pairs as $label => $value) {
            $value = $this->cleanString($value);
            if ($value) {
                $parts[] = $label . ': ' . $value;
            }
        }

        return $parts === [] ? null : implode(' | ', $parts);
    }

    private function onlyColumns(array $payload, array $columns): array
    {
        return array_intersect_key($payload, $columns);
    }


    private function usernameExists(string $username, ?int $ignoreUserId = null): bool
    {
        return DB::table('users')
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->whereRaw('LOWER(TRIM(username)) = ?', [Str::lower(trim($username))])
            ->exists();
    }

    private function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        return DB::table('users')
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->whereRaw('LOWER(TRIM(email)) = ?', [Str::lower(trim($email))])
            ->exists();
    }
}
