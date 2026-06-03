@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Achievement Periods</h3>
                    <div class="card-tools">
                        <a href="{{ route('finance.achievement-periods.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Period
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Period Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($achievementPeriods as $period)
                                <tr>
                                    <td>{{ $period->period_name }}</td>
                                    <td>{{ $period->start_date->format('d/M/Y') }}</td>
                                    <td>{{ $period->end_date->format('d/M/Y') }}</td>
                                    <td>
                                        <span class="badge {{ $period->status_badge }}">
                                            {{ $period->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $period->duration }}</td>
                                    <td>
                                        <a href="{{ route('finance.achievement-periods.show', $period) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('finance.achievement-periods.edit', $period) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No achievement periods found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
