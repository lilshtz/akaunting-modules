@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-3">
            <h1>{{ trans('expense-claims::general.reports') }}</h1>
            <a href="{{ route('expense-claims.reports.export', request()->query()) }}" class="btn btn-outline-primary">{{ trans('general.export') }}</a>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card"><div class="card-body"><strong>Pending</strong><div>{{ money($pendingTotal, setting('default.currency', 'USD')) }}</div></div></div>
            </div>
            <div class="col-md-6">
                <div class="card"><div class="card-body"><strong>Approved</strong><div>{{ money($approvedTotal, setting('default.currency', 'USD')) }}</div></div></div>
            </div>
        </div>

        <h3>{{ trans('expense-claims::general.employee') }}</h3>
        <table class="table table-striped">
            <thead><tr><th>{{ trans('expense-claims::general.employee') }}</th><th>Claims</th><th>{{ trans('expense-claims::general.total') }}</th></tr></thead>
            <tbody>
            @foreach($byEmployee as $row)
                <tr>
                    <td>{{ $row->employee?->name ?? trans('general.na') }}</td>
                    <td>{{ $row->claims_count }}</td>
                    <td>{{ money($row->total_amount, setting('default.currency', 'USD')) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <h3>{{ trans('expense-claims::general.category') }}</h3>
        <table class="table table-striped">
            <thead><tr><th>{{ trans('expense-claims::general.category') }}</th><th>Items</th><th>{{ trans('expense-claims::general.total') }}</th></tr></thead>
            <tbody>
            @foreach($byCategory as $row)
                <tr>
                    <td>{{ $row->name ?: trans('general.na') }}</td>
                    <td>{{ $row->item_count }}</td>
                    <td>{{ money($row->total_amount, setting('default.currency', 'USD')) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
