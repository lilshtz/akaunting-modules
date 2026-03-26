@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-3">
            <h1>{{ $claim->claim_number ?: $claim->id }}</h1>
            <div>
                <a href="{{ route('expense-claims.claims.pdf', $claim->id) }}" class="btn btn-outline-primary">PDF</a>
                <a href="{{ route('expense-claims.claims.edit', $claim->id) }}" class="btn btn-outline-primary">{{ trans('general.edit') }}</a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <p><strong>{{ trans('expense-claims::general.employee') }}:</strong> {{ $claim->employee_name }}</p>
                <p><strong>{{ trans('expense-claims::general.approver') }}:</strong> {{ $claim->approver?->name }}</p>
                <p><strong>{{ trans('expense-claims::general.status') }}:</strong> {{ $claim->status_label }}</p>
                <p><strong>{{ trans('expense-claims::general.total') }}:</strong> {{ money($claim->total, setting('default.currency', 'USD')) }}</p>
                <p><strong>{{ trans('expense-claims::general.reimbursable_total') }}:</strong> {{ money($claim->reimbursable_total, setting('default.currency', 'USD')) }}</p>
                <p><strong>{{ trans('expense-claims::general.due_date') }}:</strong> {{ optional($claim->due_date)->toDateString() }}</p>
                <p><strong>{{ trans('expense-claims::general.description') }}:</strong> {{ $claim->description }}</p>
                @if($claim->refusal_reason)
                    <p><strong>{{ trans('expense-claims::general.refusal_reason') }}:</strong> {{ $claim->refusal_reason }}</p>
                @endif
            </div>
        </div>

        <div class="mb-3">
            @if(in_array($claim->status, ['draft', 'refused'], true))
                <form action="{{ route('expense-claims.claims.submit', $claim->id) }}" method="post" class="d-inline">@csrf<button class="btn btn-primary">{{ trans('general.send') }}</button></form>
            @endif
            @if(in_array($claim->status, ['submitted', 'pending'], true))
                <form action="{{ route('expense-claims.claims.approve', $claim->id) }}" method="post" class="d-inline">@csrf<button class="btn btn-success">{{ trans('general.approve') }}</button></form>
                <form action="{{ route('expense-claims.claims.refuse', $claim->id) }}" method="post" class="d-inline-flex align-items-center">
                    @csrf
                    <input type="text" name="reason" class="form-control mr-2" placeholder="{{ trans('expense-claims::general.refusal_reason') }}" required>
                    <button class="btn btn-danger">{{ trans('general.refuse') }}</button>
                </form>
            @endif
            @if($claim->status === 'approved')
                <form action="{{ route('expense-claims.claims.pay', $claim->id) }}" method="post" class="d-inline">@csrf<button class="btn btn-success">{{ trans('expense-claims::general.statuses.paid') }}</button></form>
            @endif
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>{{ trans('expense-claims::general.item_date') }}</th>
                <th>{{ trans('expense-claims::general.category') }}</th>
                <th>{{ trans('expense-claims::general.description') }}</th>
                <th>{{ trans('expense-claims::general.item_amount') }}</th>
                <th>{{ trans('expense-claims::general.paid_by_employee') }}</th>
                <th>{{ trans('expense-claims::general.receipt') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($claim->items as $item)
                <tr>
                    <td>{{ optional($item->date)->toDateString() }}</td>
                    <td>{{ $item->category?->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ money($item->amount, setting('default.currency', 'USD')) }}</td>
                    <td>{{ $item->paid_by_employee ? trans('general.yes') : trans('general.no') }}</td>
                    <td>
                        @if($item->receipt_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->receipt_path) }}" target="_blank">{{ trans('general.view') }}</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
