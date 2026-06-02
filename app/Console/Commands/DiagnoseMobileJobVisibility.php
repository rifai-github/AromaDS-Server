<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DiagnoseMobileJobVisibility extends Command
{
    protected $signature = 'mobile:diagnose-job-visibility
                            {--user= : Technician name, username, or email}
                            {--job-no= : Job schedule number}
                            {--clear-cache : Clear the cached mobile team list for this user before checking}';

    protected $description = 'Diagnose why a job schedule is or is not visible in the technician mobile job list';

    public function handle(): int
    {
        $userKeyword = trim((string) $this->option('user'));
        $jobNo = trim((string) $this->option('job-no'));

        if ($userKeyword === '' || $jobNo === '') {
            $this->error('Please provide --user and --job-no.');
            $this->line('Example: php artisan mobile:diagnose-job-visibility --user="Asep Suryana" --job-no="JKT-RV/26-06/0001"');

            return self::FAILURE;
        }

        $user = $this->findUser($userKeyword);
        if (!$user) {
            $this->error("User not found: {$userKeyword}");

            return self::FAILURE;
        }

        if ((bool) $this->option('clear-cache')) {
            Cache::forget($this->teamCacheKey($user->id));
            $this->info("Cleared mobile team cache for {$user->name}.");
        }

        $userTeamIds = $this->getUserTeamIds($user->id);
        $userTeams = DB::table('teams')
            ->whereIn('id', $userTeamIds)
            ->orderBy('team_name')
            ->get(['id', 'team_name', 'team_head_id']);

        $jobs = JobSchedule::with([
            'jobAdvice.customer',
            'room',
            'jobAssignSchedules.team',
            'jobScheduleRooms.roomAssignment.team',
        ])
            ->where('job_number', $jobNo)
            ->orderBy('id')
            ->get();

        $this->line("User : {$user->name} (ID {$user->id}, username: " . ($user->username ?: '-') . ', email: ' . ($user->email ?: '-') . ')');
        $this->line("Job  : {$jobNo}");
        $this->newLine();

        $this->line('Mobile teams for this user:');
        if ($userTeams->isEmpty()) {
            $this->warn('- No team found for this user.');
        } else {
            $this->table(
                ['Team ID', 'Team Name', 'User Role'],
                $userTeams->map(function ($team) use ($user) {
                    $role = ((int) $team->team_head_id === (int) $user->id) ? 'Team Head' : 'Member';

                    return [$team->id, $team->team_name, $role];
                })->all()
            );
        }

        if ($jobs->isEmpty()) {
            $this->error("Job not found: {$jobNo}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Job visibility checks:');

        $rows = [];
        $visibleJobs = 0;
        foreach ($jobs as $job) {
            $activeJobAssignments = $job->jobAssignSchedules
                ->filter(fn ($assignment) => $assignment->status !== 'cancelled' && !$assignment->deleted_at);

            $activeRoomAssignments = $job->jobScheduleRooms
                ->flatMap(fn ($room) => $room->roomAssignment ? [$room->roomAssignment] : [])
                ->filter(fn ($assignment) => $assignment->status !== 'cancelled' && !$assignment->deleted_at);

            $assignmentTeamIds = $activeJobAssignments
                ->pluck('team_id')
                ->merge($activeRoomAssignments->pluck('team_id'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $matchedTeamIds = array_values(array_intersect($userTeamIds, $assignmentTeamIds));
            $hasMatchingTeam = !empty($matchedTeamIds);
            $hasOfficialNumber = !empty($job->job_number);
            $hasJobAdvice = (bool) $job->jobAdvice;
            $activeStatus = !in_array($job->status, ['completed', 'done_job', 'selesai', 'suspend', 'dpf', 'undone'], true);
            $visible = $hasOfficialNumber && $hasJobAdvice && $activeStatus && $hasMatchingTeam;

            if ($visible) {
                $visibleJobs++;
            }

            $rows[] = [
                $visible ? 'VISIBLE' : 'HIDDEN',
                $job->id,
                $job->type ?: '-',
                $job->status ?: '-',
                $job->getRawOriginal('room_name') ?: ($job->room?->room_name ?? '-'),
                $this->teamNamesForIds($assignmentTeamIds),
                $hasMatchingTeam ? $this->teamNamesForIds($matchedTeamIds) : '-',
                $this->reason($hasOfficialNumber, $hasJobAdvice, $activeStatus, $hasMatchingTeam),
            ];
        }

        $this->table(
            ['Result', 'Job ID', 'Type', 'Status', 'Room', 'Assigned Team(s)', 'Matched Team(s)', 'Reason'],
            $rows
        );

        $this->newLine();
        if ($visibleJobs > 0) {
            $this->info('Conclusion: this job should appear in the APK job list for this user.');
            $this->line('If it still does not appear, refresh the job list and make sure the APK base URL points to this staging server.');
        } else {
            $this->warn('Conclusion: this job will NOT appear in the APK job list for this user.');
            $this->line('Most common fix: add the technician to one of the assigned teams above, then run this command again with --clear-cache.');
        }

        return self::SUCCESS;
    }

    private function findUser(string $keyword): ?User
    {
        $exact = User::query()
            ->where('name', $keyword)
            ->orWhere('username', $keyword)
            ->orWhere('email', $keyword)
            ->first();

        if ($exact) {
            return $exact;
        }

        return User::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orWhere('username', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orderBy('id')
            ->first();
    }

    private function getUserTeamIds(int $userId): array
    {
        return Cache::remember($this->teamCacheKey($userId), now()->addMinutes(5), function () use ($userId) {
            return DB::table('teams')
                ->where('team_head_id', $userId)
                ->pluck('id')
                ->merge(
                    DB::table('team_members')
                        ->where('user_id', $userId)
                        ->pluck('team_id')
                )
                ->unique()
                ->values()
                ->toArray();
        });
    }

    private function teamCacheKey(int $userId): string
    {
        return "mobile:user-team-ids:{$userId}:v1";
    }

    private function teamNamesForIds(array $teamIds): string
    {
        if (empty($teamIds)) {
            return '-';
        }

        return DB::table('teams')
            ->whereIn('id', $teamIds)
            ->orderBy('team_name')
            ->pluck('team_name')
            ->implode(', ');
    }

    private function reason(bool $hasOfficialNumber, bool $hasJobAdvice, bool $activeStatus, bool $hasMatchingTeam): string
    {
        $reasons = [];

        if (!$hasOfficialNumber) {
            $reasons[] = 'job number kosong';
        }

        if (!$hasJobAdvice) {
            $reasons[] = 'job advice kosong';
        }

        if (!$activeStatus) {
            $reasons[] = 'status tidak aktif untuk APK';
        }

        if (!$hasMatchingTeam) {
            $reasons[] = 'team user tidak sama dengan team JS';
        }

        return empty($reasons) ? 'lolos filter APK' : implode('; ', $reasons);
    }
}
