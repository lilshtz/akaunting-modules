@extends('layouts.admin')

@section('title', trans('bank-feeds::general.transactions'))

@section('new_button')
    <form action="{{ route('bank-feeds.transactions.bulk-categorize') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-info btn-sm">
            <span class="fa fa-magic"></span> &nbsp;{{ trans('bank-feeds::general.bulk_categorize') }}
        </button>
    </form>
    <a href="{{ route('bank-feeds.imports.create') }}" class="btn btn-success btn-sm ml-2">
        <span class="fa fa-plus"></span> &nbsp;{{ trans('bank-feeds::general.import_file') }}
    </a>
@endsection

@section('content')
    <div class="card">
        {{-- Filters --}}
        <div class="card-header">
            <form method="GET" action="{{ route('bank-feeds.transactions.index') }}" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">{{ trans('bank-feeds::general.fields.status') }}</label>
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(request('import_id'))
                    <input type="hidden" name="import_id" value="{{ request('import_id') }}">
                    <span class="badge badge-primary mr-2">
                        Import #{{ request('import_id') }}
                        <a href="{{ route('bank-feeds.transactions.index') }}" class="text-white ml-1">&times;</a>
                    </span>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-flush table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>{{ trans('bank-feeds::general.fields.date') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.description') }}</th>
                        <th class="text-right">{{ trans('bank-feeds::general.fields.amount') }}</th>
                        <th class="text-center">{{ trans('bank-feeds::general.fields.type') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.category') }}</th>
                        <th class="text-center">{{ trans('bank-feeds::general.fields.status') }}</th>
                        <th class="text-center">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            <td>{{ $txn->date->format('M d, Y') }}</td>
                            <td>{{ $txn->description }}</td>
                            <td class="text-right {{ $txn->type === 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                {{ $txn->formatted_amount }}
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $txn->type === 'deposit' ? 'success' : 'danger' }}">
                                    {{ trans('bank-feeds::general.types.' . $txn->type) }}
                                </span>
                            </td>
                            <td>
                                @if($txn->category)
                                    {{ $txn->category->name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($txn->status) {
                                        'categorized' => 'success',
                                        'matched' => 'info',
                                        'ignored' => 'secondary',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge badge-{{ $statusClass }}">
                                    {{ trans('bank-feeds::general.statuses.' . $txn->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($txn->status === 'pending')
                                    <form action="{{ route('bank-feeds.transactions.ignore', $txn->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-neutral" title="{{ trans('bank-feeds::general.ignore') }}">
                                            <i class="fa fa-eye-slash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                <p class="my-4">{{ trans('general.no_records') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $transactions->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
