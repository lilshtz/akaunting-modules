@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>{{ trans('auto-schedule-reports::general.scheduled_reports') }}</h1>
            <a href="{{ route('auto-schedule-reports.schedules.create') }}" class="btn btn-primary">{{ trans('auto-schedule-reports::general.new_schedule') }}</a>
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>{{ trans('auto-schedule-reports::general.report_type') }}</th>
                <th>{{ trans('auto-schedule-reports::general.frequency') }}</th>
                <th>{{ trans('auto-schedule-reports::general.format') }}</th>
                <th>{{ trans('auto-schedule-reports::general.date_range') }}</th>
                <th>{{ trans('auto-schedule-reports::general.next_run') }}</th>
                <th>{{ trans('auto-schedule-reports::general.status') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($schedules as $schedule)
                @php($latestRun = $schedule->runs->first())
                <tr>
                    <td><a href="{{ route('auto-schedule-reports.schedules.show', $schedule->id) }}">{{ $schedule->report_type_label }}</a></td>
                    <td>{{ $schedule->frequency_label }}</td>
                    <td>{{ $schedule->format_label }}</td>
                    <td>{{ $schedule->date_range_label }}</td>
                    <td>{{ optional($schedule->next_run)->toDayDateTimeString() }}</td>
                    <td>
                        <span class="badge bg-{{ $schedule->enabled ? 'success' : 'secondary' }}">
                            {{ $schedule->enabled ? trans('auto-schedule-reports::general.enabled') : trans('auto-schedule-reports::general.disabled') }}
                        </span>
                        @if($latestRun)
                            <div class="small text-muted mt-1">
                                {{ trans('auto-schedule-reports::general.last_run') }}:
                                {{ $latestRun->ran_at->toDayDateTimeString() }} ({{ trans('auto-schedule-reports::general.statuses.' . $latestRun->status) }})
                            </div>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('auto-schedule-reports.schedules.edit', $schedule->id) }}" class="btn btn-sm btn-outline-primary">{{ trans('general.edit') }}</a>
                        <form action="{{ route('auto-schedule-reports.schedules.toggle', $schedule->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $schedule->enabled ? trans('auto-schedule-reports::general.disable') : trans('auto-schedule-reports::general.enable') }}</button>
                        </form>
                        <form action="{{ route('auto-schedule-reports.schedules.run', $schedule->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">{{ trans('auto-schedule-reports::general.manual_run') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ trans('auto-schedule-reports::general.no_schedules') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        {{ $schedules->links() }}
    </div>
@endsection
