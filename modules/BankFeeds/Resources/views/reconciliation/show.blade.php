@extends('layouts.admin')

@section('title', trans('bank-feeds::general.reconciliation') . ' — ' . ($account ? $account->name : ''))

@section('new_button')
    <a href="{{ route('bank-feeds.reconciliation.index') }}" class="btn btn-sm btn-default">
        <span class="fa fa-arrow-left"></span> &nbsp;{{ trans('general.back') }}
    </a>
    @if($reconciliation->status !== 'completed')
        <form action="{{ route('bank-feeds.reconciliation.complete', $reconciliation->id) }}" method="POST" class="d-inline ml-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this period as reconciled?')">
                <i class="fa fa-check-double"></i> &nbsp;Mark as Reconciled
            </button>
        </form>
    @endif
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center py-3">
                    <small class="text-muted d-block">{{ trans('bank-feeds::general.reconciliation_fields.opening_balance') }}</small>
                    <h4 class="mb-0">{{ money($reconciliation->opening_balance, setting('default.currency', 'USD')) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center py-3">
                    <small class="text-muted d-block">+ {{ trans('bank-feeds::general.reconciliation_fields.matched_deposits') }}</small>
                    <h4 class="mb-0 text-success">{{ money($totals['matched_deposits'], setting('default.currency', 'USD')) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center py-3">
                    <small class="text-muted d-block">- {{ trans('bank-feeds::general.reconciliation_fields.matched_withdrawals') }}</small>
                    <h4 class="mb-0 text-danger">{{ money($totals['matched_withdrawals'], setting('default.currency', 'USD')) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center py-3">
                    <small class="text-muted d-block">{{ trans('bank-feeds::general.reconciliation_fields.reconciled_balance') }}</small>
                    <h4 class="mb-0">{{ money($totals['reconciled_balance'], setting('default.currency', 'USD')) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center py-3">
                    <small class="text-muted d-block">{{ trans('bank-feeds::general.reconciliation_fields.closing_balance') }}</small>
                    <h4 class="mb-0">{{ money($reconciliation->closing_balance, setting('default.currency', 'USD')) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card {{ abs($totals['difference']) < 0.01 ? 'border-success' : 'border-danger' }}">
                <div class="card-body text-center py-3">
                    <small class="text-muted d-block">{{ trans('bank-feeds::general.reconciliation_fields.difference') }}</small>
                    <h4 class="mb-0 {{ abs($totals['difference']) < 0.01 ? 'text-success' : 'text-danger' }}">
                        {{ money($totals['difference'], setting('default.currency', 'USD')) }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
                <span>
                    {{ $reconciliation->statement_start_date->format('M d, Y') }}
                    — {{ $reconciliation->statement_end_date->format('M d, Y') }}
                </span>
                <span>
                    <span class="badge badge-success">{{ $totals['matched_count'] }} matched</span>
                    <span class="badge badge-warning">{{ $totals['unmatched_count'] }} unmatched</span>
                    <span class="badge badge-secondary">{{ $totals['ignored_count'] }} ignored</span>
                    <span class="badge badge-info">{{ $totals['total_count'] }} total</span>
                </span>
            </div>
            @php
                $progress = $totals['total_count'] > 0
                    ? round(($totals['matched_count'] + $totals['ignored_count']) / $totals['total_count'] * 100)
                    : 0;
            @endphp
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </div>

    {{-- Transaction List --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ trans('bank-feeds::general.transactions') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-flush table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>{{ trans('bank-feeds::general.fields.date') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.description') }}</th>
                        <th class="text-right">{{ trans('bank-feeds::general.fields.amount') }}</th>
                        <th class="text-center">{{ trans('bank-feeds::general.fields.status') }}</th>
                        <th>{{ trans('bank-feeds::general.matching.existing_transaction') }}</th>
                        <th class="text-center">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankTransactions as $txn)
                        <tr class="{{ $txn->status === 'matched' ? 'table-success' : ($txn->status === 'ignored' ? 'table-secondary' : '') }}">
                            <td>{{ $txn->date->format('M d, Y') }}</td>
                            <td>{{ $txn->description }}</td>
                            <td class="text-right {{ $txn->type === 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                {{ $txn->formatted_amount }}
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($txn->status) {
                                        'matched' => 'success',
                                        'categorized' => 'info',
                                        'ignored' => 'secondary',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge badge-{{ $statusClass }}">
                                    {{ trans('bank-feeds::general.statuses.' . $txn->status) }}
                                </span>
                            </td>
                            <td>
                                @if($txn->matchedTransaction)
                                    <small>
                                        {{ money($txn->matchedTransaction->amount, $txn->matchedTransaction->currency_code) }}
                                        — {{ $txn->matchedTransaction->paid_at->format('M d') }}
                                        @if($txn->matchedTransaction->contact)
                                            — {{ $txn->matchedTransaction->contact->name }}
                                        @endif
                                        @if($txn->match_confidence)
                                            <span class="badge badge-light">{{ $txn->match_confidence }}%</span>
                                        @endif
                                    </small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($reconciliation->status !== 'completed')
                                    @if($txn->status === 'matched')
                                        <form action="{{ route('bank-feeds.reconciliation.unmatch', $reconciliation->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="bank_feed_transaction_id" value="{{ $txn->id }}">
                                            <button type="submit" class="btn btn-sm btn-neutral" title="{{ trans('bank-feeds::general.matching.reject_match') }}">
                                                <i class="fa fa-unlink"></i>
                                            </button>
                                        </form>
                                    @elseif($txn->status !== 'ignored')
                                        <a href="{{ route('bank-feeds.matching.show', $txn->id) }}" class="btn btn-sm btn-neutral" title="{{ trans('bank-feeds::general.matching.find_match') }}">
                                            <i class="fa fa-search"></i>
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                <p class="my-4">No imported transactions found for this period.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($reconciliation->status === 'completed' && $reconciliation->completed_at)
        <div class="text-center text-muted mt-3">
            <small>Reconciled on {{ $reconciliation->completed_at->format('M d, Y \a\t g:i A') }}</small>
        </div>
    @endif
@endsection
