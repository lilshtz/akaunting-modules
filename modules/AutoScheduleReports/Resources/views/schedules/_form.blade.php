@csrf

<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.report_type') }}</label>
                <select name="report_type" class="form-control" required>
                    @foreach($reportTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('report_type', $schedule->report_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.frequency') }}</label>
                <select name="frequency" class="form-control" required>
                    @foreach($frequencies as $value => $label)
                        <option value="{{ $value }}" @selected(old('frequency', $schedule->frequency) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.format') }}</label>
                <select name="format" class="form-control" required>
                    @foreach($formats as $value => $label)
                        <option value="{{ $value }}" @selected(old('format', $schedule->format) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.date_range') }}</label>
                <select name="date_range_type" class="form-control" required>
                    @foreach($dateRanges as $value => $label)
                        <option value="{{ $value }}" @selected(old('date_range_type', $schedule->date_range_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.next_run') }}</label>
                <input type="datetime-local" name="next_run" class="form-control" value="{{ old('next_run', optional($schedule->next_run)->format('Y-m-d\\TH:i')) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.recipients') }}</label>
                <textarea name="recipients" rows="2" class="form-control" placeholder="finance@example.com, owner@example.com">{{ old('recipients', $schedule->recipients_text) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.custom_date_from') }}</label>
                <input type="date" name="custom_date_from" class="form-control" value="{{ old('custom_date_from', optional($schedule->custom_date_from)->toDateString()) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ trans('auto-schedule-reports::general.custom_date_to') }}</label>
                <input type="date" name="custom_date_to" class="form-control" value="{{ old('custom_date_to', optional($schedule->custom_date_to)->toDateString()) }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">{{ trans('auto-schedule-reports::general.webhook_url') }}</label>
                <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url', $schedule->webhook_url) }}" placeholder="https://example.com/webhooks/reports">
            </div>
            <div class="col-md-12">
                <div class="form-check">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" id="enabled" class="form-check-input" value="1" @checked(old('enabled', $schedule->enabled))>
                    <label for="enabled" class="form-check-label">{{ trans('auto-schedule-reports::general.enabled') }}</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('auto-schedule-reports.schedules.index') }}" class="btn btn-outline-secondary">{{ trans('general.cancel') }}</a>
        <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
    </div>
</div>
