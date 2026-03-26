@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-3">
            <h1>{{ trans('expense-claims::general.claims') }}</h1>
            <div>
                <a href="{{ route('expense-claims.claims.import') }}" class="btn btn-outline-primary">{{ trans('general.import') }}</a>
                <a href="{{ route('expense-claims.claims.export') }}" class="btn btn-outline-primary">{{ trans('general.export') }}</a>
                <a href="{{ route('expense-claims.claims.create') }}" class="btn btn-primary">{{ trans('expense-claims::general.new_claim') }}</a>
            </div>
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>{{ trans('expense-claims::general.employee') }}</th>
                <th>{{ trans('expense-claims::general.status') }}</th>
                <th>{{ trans('expense-claims::general.total') }}</th>
                <th>{{ trans('expense-claims::general.reimbursable_total') }}</th>
                <th>{{ trans('expense-claims::general.due_date') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($claims as $claim)
                <tr>
                    <td><a href="{{ route('expense-claims.claims.show', $claim->id) }}">{{ $claim->claim_number ?: $claim->id }}</a></td>
                    <td>{{ $claim->employee_name }}</td>
                    <td>{{ $claim->status_label }}</td>
                    <td>{{ money($claim->total, setting('default.currency', 'USD')) }}</td>
                    <td>{{ money($claim->reimbursable_total, setting('default.currency', 'USD')) }}</td>
                    <td>{{ optional($claim->due_date)->toDateString() }}</td>
                    <td><a href="{{ route('expense-claims.claims.edit', $claim->id) }}">{{ trans('general.edit') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{ $claims->links() }}
    </div>
@endsection
