@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="mb-1">{{ trans('budgets::general.variance_report') }}</h1>
                <div class="text-muted">{{ $budget->name }} | {{ $budget->period_start->toDateString() }} - {{ $budget->period_end->toDateString() }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('budgets.budgets.report.export', ['budget' => $budget->id, 'format' => 'csv']) }}" class="btn btn-outline-primary">{{ trans('budgets::general.export_csv') }}</a>
                <a href="{{ route('budgets.budgets.report.export', ['budget' => $budget->id, 'format' => 'pdf']) }}" class="btn btn-outline-primary">{{ trans('budgets::general.export_pdf') }}</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.budgeted') }}</strong><div>{{ money($report['summary']['budgeted'], setting('default.currency', 'USD')) }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.actual') }}</strong><div>{{ money($report['summary']['actual'], setting('default.currency', 'USD')) }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.variance') }}</strong><div class="{{ $report['summary']['variance'] < 0 ? 'text-danger' : 'text-success' }}">{{ money($report['summary']['variance'], setting('default.currency', 'USD')) }}</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body"><strong>{{ trans('budgets::general.over_budget') }}</strong><div>{{ $report['summary']['over_budget_count'] }}</div></div></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="mb-0">{{ trans('budgets::general.chart_title') }}</h3>
            </div>
            <div class="card-body">
                <canvas id="budget-vs-actual-chart" height="120"></canvas>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="mb-0">{{ trans('budgets::general.top_variances') }}</h3>
                    </div>
                    <div class="card-body">
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
            </div>
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="mb-0">{{ trans('budgets::general.budget_vs_actual') }}</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{ trans('budgets::general.account') }}</th>
                                <th>{{ trans('budgets::general.budgeted') }}</th>
                                <th>{{ trans('budgets::general.actual') }}</th>
                                <th>{{ trans('budgets::general.variance') }}</th>
                                <th>{{ trans('budgets::general.variance_percentage') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($report['lines'] as $row)
                                <tr class="{{ $row['is_over_budget'] ? 'table-danger' : '' }}">
                                    <td>{{ $row['account']->display_name }}</td>
                                    <td>{{ money($row['budget_amount'], setting('default.currency', 'USD')) }}</td>
                                    <td>{{ money($row['actual_amount'], setting('default.currency', 'USD')) }}</td>
                                    <td class="{{ $row['variance'] < 0 ? 'text-danger' : 'text-success' }}">{{ money($row['variance'], setting('default.currency', 'USD')) }}</td>
                                    <td>{{ $row['variance_percentage'] !== null ? number_format($row['variance_percentage'], 2) . '%' : '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('budget-vs-actual-chart');

            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: @json($report['chart']['labels']),
                    datasets: [
                        {
                            label: '{{ trans('budgets::general.budgeted') }}',
                            data: @json($report['chart']['budget']),
                            backgroundColor: '#cbd5e1'
                        },
                        {
                            label: '{{ trans('budgets::general.actual') }}',
                            data: @json($report['chart']['actual']),
                            backgroundColor: '#2563eb'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
@endsection
