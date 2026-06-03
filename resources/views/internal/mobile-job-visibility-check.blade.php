@extends('layouts.app')

@section('title', 'Mobile Job Visibility Check')

@section('content')
<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Mobile Job Visibility Check</h5>
            <small>Internal read-only tool untuk cek apakah job seharusnya muncul di APK teknisi.</small>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('internal.mobile-job-visibility-check') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Job No / Contract No / Customer / Building</label>
                    <input type="text" name="q" class="form-control" value="{{ $query }}" placeholder="Contoh: BDG-CA/26-05/0002 atau BDG-IR/26-05/0003">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Teknisi / User APK (opsional)</label>
                    <select name="technician_id" class="form-control">
                        <option value="">Semua / belum pilih teknisi</option>
                        @foreach($technicians as $option)
                            <option value="{{ $option->id }}" @selected((string) $technicianId === (string) $option->id)>
                                {{ $option->name }} - {{ $option->email ?? $option->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cek
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($query !== '')
        <div class="card mb-4">
            <div class="card-header">
                <strong>Hasil Cek</strong>
                <span class="text-muted">({{ $diagnostics->count() }} job ditemukan)</span>
            </div>
            <div class="card-body p-0">
                @forelse($diagnostics as $item)
                    @php
                        $job = $item['job'];
                        $statusClass = $item['would_appear'] ? 'success' : 'danger';
                    @endphp
                    <div class="border-bottom p-3">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <div>
                                <h6 class="mb-1">{{ $job->job_number ?? '-' }}</h6>
                                <div class="text-muted small">
                                    {{ $job->contract_number ?? '-' }} |
                                    {{ $job->jobAdvice?->customer?->name ?? '-' }} |
                                    {{ $job->building_name ?? '-' }} |
                                    {{ $job->room_name ?? '-' }}
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-{{ $statusClass }}">
                                    {{ $item['would_appear'] ? 'Seharusnya Muncul' : 'Tidak Muncul / Perlu Cek' }}
                                </span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-muted small">Status Job</div>
                                <strong>{{ $job->status ?? '-' }}</strong>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Schedule Date</div>
                                <strong>{{ $job->schedule_date ? \Carbon\Carbon::parse($job->schedule_date)->format('d/M/Y') : '-' }}</strong>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Terakhir Update Job/Assign</div>
                                <strong>{{ $item['latest_relevant_update'] ? $item['latest_relevant_update']->format('d/M/Y H:i:s') : '-' }}</strong>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Delay ke Poll APK</div>
                                <strong>
                                    @if($item['delay_seconds'] !== null)
                                        {{ $item['delay_seconds'] }} detik
                                    @else
                                        -
                                    @endif
                                </strong>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="text-muted small mb-1">Team Aktif</div>
                            @forelse($item['active_assignments'] as $assignment)
                                <span class="badge bg-info text-dark me-1">
                                    {{ $assignment->team?->team_name ?? 'Team #' . $assignment->team_id }}
                                </span>
                            @empty
                                <span class="text-muted">Belum ada team aktif</span>
                            @endforelse
                        </div>

                        @if($technician)
                            <div class="mt-3">
                                <div class="text-muted small mb-1">Cek untuk Teknisi</div>
                                <strong>{{ $technician->name }}</strong>
                                <span class="badge bg-{{ $item['visible_for_technician'] ? 'success' : 'danger' }} ms-2">
                                    {{ $item['visible_for_technician'] ? 'Team cocok' : 'Team tidak cocok' }}
                                </span>
                            </div>
                        @endif

                        @if(!empty($item['reasons']))
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>Alasan / Catatan:</strong>
                                <ul class="mb-0">
                                    @foreach($item['reasons'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="small text-muted">Poll APK terakhir user terpilih</div>
                                @if($item['last_poll_for_technician'])
                                    {{ $item['last_poll_for_technician']['polled_at'] ?? '-' }}
                                    |
                                    {{ $item['last_poll_for_technician']['jobs_count'] ?? 0 }} job dikirim
                                    |
                                    {{ $item['last_poll_for_technician']['duration_ms'] ?? '-' }} ms
                                @else
                                    <span class="text-muted">Belum ada log poll untuk user ini.</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Poll APK terakhir yang mengandung job ini</div>
                                @if($item['last_poll_containing_job'])
                                    {{ $item['last_poll_containing_job']['polled_at'] ?? '-' }}
                                    |
                                    User ID {{ $item['last_poll_containing_job']['user_id'] ?? '-' }}
                                @else
                                    <span class="text-muted">Belum ditemukan di log terbaru.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-muted">Tidak ada job yang cocok dengan pencarian.</div>
                @endforelse
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <strong>Log Poll APK Terakhir</strong>
            <span class="text-muted">dari storage/logs/mobile-sync.log</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Waktu Poll</th>
                            <th>User</th>
                            <th>Team IDs</th>
                            <th>Job Count</th>
                            <th>Duration</th>
                            <th>Job Numbers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pollLogs as $log)
                            <tr>
                                <td>{{ $log['polled_at'] ?? '-' }}</td>
                                <td>
                                    {{ $log['user_name'] ?? '-' }}
                                    <span class="text-muted">#{{ $log['user_id'] ?? '-' }}</span>
                                </td>
                                <td>{{ implode(', ', $log['team_ids'] ?? []) }}</td>
                                <td>{{ $log['jobs_count'] ?? '-' }}</td>
                                <td>{{ $log['duration_ms'] ?? '-' }} ms</td>
                                <td style="max-width: 520px; white-space: normal;">
                                    {{ implode(', ', array_slice($log['job_numbers'] ?? [], 0, 15)) }}
                                    @if(count($log['job_numbers'] ?? []) > 15)
                                        ...
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted p-4">Belum ada log poll APK.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
