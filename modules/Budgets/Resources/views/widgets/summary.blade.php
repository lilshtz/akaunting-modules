@if (! $budget || ! $report)
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">{{ trans('budgets::general.widget_summary') }}</h3>
        </div>
        <div class="card-body text-muted">
            {{ trans('budgets::general.no_budget_widget') }}
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">{{ trans('budgets::general.widget_summary') }}</h3>
            <a href="{{ route('budgets.budgets.report', $budget->id) }}">{{ trans('budgets::general.view_report') }}</a>
        </div>
        <div class="card-body">
            <p><strong>{{ $budget->name }}</strong></p>
            <p>{{ $budget->period_start->toDateString() }} - {{ $budget->period_end->toDateString() }}</p>
            <p><strong>{{ trans('budgets::general.budgeted') }}:</strong> {{ money($report['summary']['budgeted'], setting('default.currency', 'USD')) }}</p>
            <p><strong>{{ trans('budgets::general.actual') }}:</strong> {{ money($report['summary']['actual'], setting('default.currency', 'USD')) }}</p>
            <p><strong>{{ trans('budgets::general.over_budget') }}:</strong> {{ $report['summary']['over_budget_count'] }}</p>

            <table class="table table-sm">
                <thead>
                <tr>
                    <th>{{ trans('budgets::general.account') }}</th>
                    <th>{{ trans('budgets::general.variance') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($report['top_variances'] as $row)
                    <tr>
                        <td>{{ $row['account']->display_name }}</td>
                        <td class="{{ $row['variance'] < 0 ? 'text-danger' : 'text-success' }}">{{ money($row['variance'], setting('default.currency', 'USD')) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
