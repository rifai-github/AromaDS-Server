<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\JobSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MobileJobVisibilityController extends Controller
{
    private const APP_EXCLUDED_STATUSES = [
        'completed',
        'done_job',
        'selesai',
        'suspend',
        'dpf',
        'undone',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->hasRole('Admin') || $user->hasRole('super_admin') || $user->hasRoleStartingWith('Management')),
            403
        );

        $query = trim((string) $request->query('q', ''));
        $technicianId = $request->query('technician_id');
        $technician = $technicianId ? User::find($technicianId) : null;
        $technicians = $this->getTechnicianUsers();
        $pollLogs = $this->readMobilePollLogs();

        $jobs = collect();
        if ($query !== '') {
            $jobs = JobSchedule::with([
                    'jobAdvice.customer',
                    'jobAssignSchedules.team.teamHead:id,name,email,username',
                    'jobAssignSchedules.team.users:id,name,email,username',
                ])
                ->where(function ($jobQuery) use ($query) {
                    $jobQuery->where('job_number', 'like', "%{$query}%")
                        ->orWhere('contract_number', 'like', "%{$query}%")
                        ->orWhere('building_name', 'like', "%{$query}%")
                        ->orWhereHas('jobAdvice.customer', function ($customerQuery) use ($query) {
                            $customerQuery->where('name', 'like', "%{$query}%");
                        });
                })
                ->orderByDesc('updated_at')
                ->limit(30)
                ->get();
        }

        $diagnostics = $jobs->map(fn (JobSchedule $job) => $this->diagnoseJob($job, $technician, $pollLogs));

        return view('internal.mobile-job-visibility-check', [
            'query' => $query,
            'technicianId' => $technicianId,
            'technician' => $technician,
            'technicians' => $technicians,
            'diagnostics' => $diagnostics,
            'pollLogs' => $pollLogs->take(20),
        ]);
    }

    private function diagnoseJob(JobSchedule $job, ?User $technician, Collection $pollLogs): array
    {
        $activeAssignments = $job->jobAssignSchedules
            ->filter(fn ($assignment) => $assignment->status !== 'cancelled' && empty($assignment->deleted_at))
            ->values();
        $activeTeamIds = $activeAssignments->pluck('team_id')->filter()->unique()->values();
        $technicianTeamIds = $technician ? collect($this->getUserTeamIds($technician->id)) : collect();
        $visibleForTechnician = $technician
            ? $activeTeamIds->intersect($technicianTeamIds)->isNotEmpty()
            : null;

        $reasons = [];

        if (empty($job->job_number)) {
            $reasons[] = 'Job number kosong, APK tidak akan menampilkan job invalid.';
        }

        if (!$job->jobAdvice) {
            $reasons[] = 'Job Advice tidak ditemukan.';
        }

        if (in_array($job->status, self::APP_EXCLUDED_STATUSES, true)) {
            $reasons[] = 'Status job termasuk status yang disembunyikan dari daftar pekerjaan APK.';
        }

        if ($activeTeamIds->isEmpty()) {
            $reasons[] = 'Belum ada team aktif yang di-assign ke job.';
        }

        if ($technician && !$visibleForTechnician) {
            $reasons[] = 'Teknisi yang dipilih tidak berada di team aktif job ini.';
        }

        if ($job->status === 'meninggalkan_lokasi') {
            $reasons[] = 'Status meninggalkan lokasi bisa disembunyikan APK jika follow-up partial completion belum resolved.';
        }

        $wouldAppear = empty($reasons);
        $lastPollForTechnician = $technician
            ? $pollLogs->first(fn ($log) => (int) ($log['user_id'] ?? 0) === (int) $technician->id)
            : null;
        $lastPollContainingJob = $job->job_number
            ? $pollLogs->first(function ($log) use ($job) {
                return in_array($job->job_number, $log['job_numbers'] ?? [], true);
            })
            : null;

        $latestRelevantUpdate = collect([
                $job->updated_at,
                $activeAssignments->pluck('updated_at')->filter()->max(),
            ])
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sortDesc()
            ->first();

        return [
            'job' => $job,
            'active_assignments' => $activeAssignments,
            'active_team_ids' => $activeTeamIds,
            'technician_team_ids' => $technicianTeamIds,
            'visible_for_technician' => $visibleForTechnician,
            'would_appear' => $wouldAppear,
            'reasons' => $reasons,
            'latest_relevant_update' => $latestRelevantUpdate,
            'last_poll_for_technician' => $lastPollForTechnician,
            'last_poll_containing_job' => $lastPollContainingJob,
            'delay_seconds' => $this->calculateDelaySeconds($latestRelevantUpdate, $lastPollContainingJob),
        ];
    }

    private function calculateDelaySeconds(?Carbon $latestRelevantUpdate, ?array $pollLog): ?int
    {
        if (!$latestRelevantUpdate || empty($pollLog['polled_at'])) {
            return null;
        }

        $polledAt = Carbon::parse($pollLog['polled_at']);
        if ($polledAt->lessThan($latestRelevantUpdate)) {
            return null;
        }

        return $latestRelevantUpdate->diffInSeconds($polledAt);
    }

    private function getTechnicianUsers(): Collection
    {
        $teamHeadIds = DB::table('teams')->whereNotNull('team_head_id')->pluck('team_head_id');
        $memberIds = DB::table('team_members')->pluck('user_id');

        return User::whereIn('id', $teamHeadIds->merge($memberIds)->unique()->values())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username']);
    }

    private function getUserTeamIds(int $userId): array
    {
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
    }

    private function readMobilePollLogs(): Collection
    {
        $path = storage_path('logs/mobile-sync.log');
        if (!File::exists($path)) {
            return collect();
        }

        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $lines = [];

        for ($lineNumber = max(0, $lastLine - 250); $lineNumber <= $lastLine; $lineNumber++) {
            $file->seek($lineNumber);
            $line = trim((string) $file->current());

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        $lines = collect($lines);

        return $lines
            ->reverse()
            ->map(function (string $line) {
                if (!preg_match('/mobile_jobs_today_polled\s+(\{.*\})$/', $line, $matches)) {
                    return null;
                }

                $payload = json_decode($matches[1], true);
                if (!is_array($payload)) {
                    return null;
                }

                return $payload;
            })
            ->filter()
            ->values();
    }
}
