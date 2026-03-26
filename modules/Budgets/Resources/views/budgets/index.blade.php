@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>{{ trans('budgets::general.budgets') }}</h1>
            <a href="{{ route('budgets.budgets.create') }}" class="btn btn-primary">{{ trans('budgets::general.new_budget') }}</a>
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>{{ trans('general.name') }}</th>
                <th>{{ trans('budgets::general.period') }}</th>
                <th>{{ trans('budgets::general.scenario') }}</th>
                <th>{{ trans('budgets::general.status') }}</th>
                <th>{{ trans('budgets::general.accounts') }}</th>
                <th>{{ trans('budgets::general.total_budgeted') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($budgets as $budget)
                <tr>
                    <td><a href="{{ route('budgets.budgets.show', $budget->id) }}">{{ $budget->name }}</a></td>
                    <td>{{ $budget->period_start->toDateString() }} - {{ $budget->period_end->toDateString() }}</td>
                    <td>{{ $budget->scenario_label }}</td>
                    <td>{{ $budget->status_label }}</td>
                    <td>{{ $budget->lines_count }}</td>
                    <td>{{ money($budget->total_budgeted, setting('default.currency', 'USD')) }}</td>
                    <td class="text-end">
                        <a href="{{ route('budgets.budgets.edit', $budget->id) }}" class="btn btn-sm btn-outline-primary">{{ trans('general.edit') }}</a>
                        <a href="{{ route('budgets.budgets.report', $budget->id) }}" class="btn btn-sm btn-outline-secondary">{{ trans('budgets::general.view_report') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ trans('budgets::general.empty_state') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        {{ $budgets->links() }}
    </div>
@endsection
