@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="mb-1">{{ $budget->name }}</h1>
                <div class="text-muted">{{ $budget->period_start->toDateString() }} - {{ $budget->period_end->toDateString() }}</div>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('budgets.budgets.copy', $budget->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">{{ trans('budgets::general.copy_budget') }}</button>
                </form>
                <a href="{{ route('budgets.budgets.edit', $budget->id) }}" class="btn btn-outline-primary">{{ trans('general.edit') }}</a>
                <a href="{{ route('budgets.budgets.report', $budget->id) }}" class="btn btn-primary">{{ trans('budgets::general.view_report') }}</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.period_type') }}</strong><div>{{ $budget->period_label }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.scenario') }}</strong><div>{{ $budget->scenario_label }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.status') }}</strong><div>{{ $budget->status_label }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.total_budgeted') }}</strong><div>{{ money($budget->total_budgeted, setting('default.currency', 'USD')) }}</div></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">{{ trans('budgets::general.line_items') }}</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ trans('budgets::general.account') }}</th>
                        <th>{{ trans('budgets::general.amount') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($budget->lines->sortBy(fn ($line) => $line->account?->code) as $line)
                        <tr>
                            <td>{{ $line->account?->display_name ?: trans('general.na') }}</td>
                            <td>{{ money($line->amount, setting('default.currency', 'USD')) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
