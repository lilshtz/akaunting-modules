@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="mb-0">{{ $schedule->report_type_label }}</h1>
                <div class="text-muted">{{ $schedule->frequency_label }} · {{ $schedule->format_label }} · {{ $schedule->date_range_label }}</div>
            </div>
            <div>
                <a href="{{ route('auto-schedule-reports.schedules.edit', $schedule->id) }}" class="btn btn-outline-primary">{{ trans('general.edit') }}</a>
                <form action="{{ route('auto-schedule-reports.schedules.run', $schedule->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">{{ trans('auto-schedule-reports::general.manual_run') }}</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>{{ trans('auto-schedule-reports::general.next_run') }}:</strong> {{ optional($schedule->next_run)->toDayDateTimeString() }}</div>
                    <div class="col-md-4"><strong>{{ trans('auto-schedule-reports::general.recipients') }}:</strong> {{ $schedule->recipients_text ?: '-' }}</div>
                    <div class="col-md-4"><strong>{{ trans('auto-schedule-reports::general.webhook_url') }}:</strong> {{ $schedule->webhook_url ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">{{ trans('auto-schedule-reports::general.execution_history') }}</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>{{ trans('auto-schedule-reports::general.generated_at') }}</th>
                        <th>{{ trans('auto-schedule-reports::general.status') }}</th>
                        <th>{{ trans('auto-schedule-reports::general.download') }}</th>
                        <th>Error</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->ran_at->toDayDateTimeString() }}</td>
                            <td>{{ trans('auto-schedule-reports::general.statuses.' . $run->status) }}</td>
                            <td>
                                @if($run->file_path)
                                    <a href="{{ route('auto-schedule-reports.runs.download', $run->id) }}" class="btn btn-sm btn-outline-secondary">{{ trans('auto-schedule-reports::general.download') }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-muted small">{{ $run->error_message ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ trans('auto-schedule-reports::general.no_runs') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $runs->links() }}
        </div>
    </div>
@endsection
