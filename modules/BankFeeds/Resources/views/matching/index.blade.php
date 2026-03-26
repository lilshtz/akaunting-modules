@extends('layouts.admin')

@section('title', trans('bank-feeds::general.matching.name'))

@section('new_button')
    <form action="{{ route('bank-feeds.matching.auto-match') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-info btn-sm">
            <span class="fa fa-magic"></span> &nbsp;{{ trans('bank-feeds::general.matching.auto_match_all') }}
        </button>
    </form>
    <a href="{{ route('bank-feeds.imports.create') }}" class="btn btn-success btn-sm ml-2">
        <span class="fa fa-plus"></span> &nbsp;{{ trans('bank-feeds::general.import_file') }}
    </a>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-header">
            <form method="GET" action="{{ route('bank-feeds.matching.index') }}" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">{{ trans('bank-feeds::general.fields.bank_account') }}</label>
                    <select name="bank_account_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach($accounts as $id => $name)
                            <option value="{{ $id }}" {{ request('bank_account_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk ignore form --}}
    <form id="bulk-ignore-form" action="{{ route('bank-feeds.matching.bulk-ignore') }}" method="POST">
        @csrf

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ trans('bank-feeds::general.matching.name') }} ({{ $transactions->total() }})</span>
                <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Ignore selected transactions?')">
                    <span class="fa fa-eye-slash"></span> &nbsp;{{ trans('bank-feeds::general.matching.bulk_ignore') }}
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-flush table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="30"><input type="checkbox" id="select-all"></th>
                            <th>{{ trans('bank-feeds::general.fields.date') }}</th>
                            <th>{{ trans('bank-feeds::general.fields.description') }}</th>
                            <th class="text-right">{{ trans('bank-feeds::general.fields.amount') }}</th>
                            <th class="text-center">{{ trans('bank-feeds::general.fields.type') }}</th>
                            <th>{{ trans('bank-feeds::general.matching.suggested_matches') }}</th>
                            <th class="text-center">{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr class="{{ $txn->is_duplicate ? 'table-warning' : '' }}">
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $txn->id }}">
                                </td>
                                <td>
                                    {{ $txn->date->format('M d, Y') }}
                                    @if($txn->is_duplicate)
                                        <br><small class="text-warning">
                                            <i class="fa fa-exclamation-triangle"></i>
                                            {{ trans('bank-feeds::general.matching.duplicate_warning') }}
                                        </small>
                                    @endif
                                </td>
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
                                    @if(!empty($suggestions[$txn->id]))
                                        @foreach($suggestions[$txn->id] as $match)
                                            @php
                                                $conf = $match['confidence'];
                                                $confClass = $conf >= 85 ? 'success' : ($conf >= 50 ? 'warning' : 'secondary');
                                            @endphp
                                            <div class="mb-1">
                                                <span class="badge badge-{{ $confClass }}">{{ $conf }}%</span>
                                                <small>
                                                    {{ money($match['transaction']->amount, $match['transaction']->currency_code) }}
                                                    — {{ $match['transaction']->paid_at->format('M d') }}
                                                    @if($match['transaction']->contact)
                                                        — {{ $match['transaction']->contact->name }}
                                                    @endif
                                                </small>
                                                <form action="{{ route('bank-feeds.matching.accept', $txn->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="transaction_id" value="{{ $match['transaction']->id }}">
                                                    <input type="hidden" name="confidence" value="{{ $conf }}">
                                                    <button type="submit" class="btn btn-sm btn-link text-success p-0 ml-1" title="{{ trans('bank-feeds::general.matching.accept_match') }}">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-muted"><small>{{ trans('bank-feeds::general.matching.no_suggestions') }}</small></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-neutral" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="{{ route('bank-feeds.matching.show', $txn->id) }}">
                                                <i class="fa fa-search mr-2"></i>{{ trans('bank-feeds::general.matching.find_match') }}
                                            </a>
                                            <form action="{{ route('bank-feeds.matching.create-transaction', $txn->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fa fa-plus mr-2"></i>{{ trans('bank-feeds::general.matching.create_new') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('bank-feeds.transactions.ignore', $txn->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fa fa-eye-slash mr-2"></i>{{ trans('bank-feeds::general.ignore') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
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
    </form>
@endsection

@push('scripts_start')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('select-all').addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('input[name="ids[]"]');
                checkboxes.forEach(function(cb) { cb.checked = this.checked; }.bind(this));
            });
        });
    </script>
@endpush
