@extends('layouts.admin')

@section('title', trans('bank-feeds::general.matching.find_match'))

@section('new_button')
    <a href="{{ route('bank-feeds.matching.index') }}" class="btn btn-sm btn-default">
        <span class="fa fa-arrow-left"></span> &nbsp;{{ trans('general.back') }}
    </a>
@endsection

@section('content')
    <div class="row">
        {{-- Left: Imported Transaction --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ trans('bank-feeds::general.matching.imported_transaction') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th class="text-muted" width="35%">{{ trans('bank-feeds::general.fields.date') }}</th>
                            <td>{{ $transaction->date->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ trans('bank-feeds::general.fields.description') }}</th>
                            <td>{{ $transaction->description }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ trans('bank-feeds::general.fields.amount') }}</th>
                            <td class="{{ $transaction->type === 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                {{ $transaction->formatted_amount }}
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ trans('bank-feeds::general.fields.type') }}</th>
                            <td>
                                <span class="badge badge-{{ $transaction->type === 'deposit' ? 'success' : 'danger' }}">
                                    {{ trans('bank-feeds::general.types.' . $transaction->type) }}
                                </span>
                            </td>
                        </tr>
                        @if($transaction->category)
                            <tr>
                                <th class="text-muted">{{ trans('bank-feeds::general.fields.category') }}</th>
                                <td>{{ $transaction->category->name }}</td>
                            </tr>
                        @endif
                        @if($transaction->is_duplicate)
                            <tr>
                                <td colspan="2">
                                    <div class="alert alert-warning mb-0">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        {{ trans('bank-feeds::general.matching.duplicate_warning') }}
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>

                    <div class="mt-3">
                        <form action="{{ route('bank-feeds.matching.create-transaction', $transaction->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus mr-1"></i>{{ trans('bank-feeds::general.matching.create_new') }}
                            </button>
                        </form>
                        <form action="{{ route('bank-feeds.transactions.ignore', $transaction->id) }}" method="POST" class="d-inline ml-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="fa fa-eye-slash mr-1"></i>{{ trans('bank-feeds::general.ignore') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Suggested Matches --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ trans('bank-feeds::general.matching.suggested_matches') }}</h5>
                </div>
                <div class="card-body">
                    @forelse($suggestions as $index => $match)
                        @php
                            $conf = $match['confidence'];
                            $confClass = $conf >= 85 ? 'success' : ($conf >= 50 ? 'warning' : 'secondary');
                            $confLabel = $conf >= 85
                                ? trans('bank-feeds::general.matching.high_confidence')
                                : ($conf >= 50 ? trans('bank-feeds::general.matching.medium_confidence') : trans('bank-feeds::general.matching.low_confidence'));
                            $txn = $match['transaction'];
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $index === 0 && $conf >= 85 ? 'border-success' : '' }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge badge-{{ $confClass }} badge-lg">
                                        {{ $conf }}% — {{ $confLabel }}
                                    </span>
                                </div>
                                <form action="{{ route('bank-feeds.matching.accept', $transaction->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="transaction_id" value="{{ $txn->id }}">
                                    <input type="hidden" name="confidence" value="{{ $conf }}">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fa fa-check mr-1"></i>{{ trans('bank-feeds::general.matching.accept_match') }}
                                    </button>
                                </form>
                            </div>

                            <table class="table table-borderless table-sm mb-2">
                                <tr>
                                    <th class="text-muted" width="30%">{{ trans('bank-feeds::general.fields.date') }}</th>
                                    <td>{{ $txn->paid_at->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">{{ trans('bank-feeds::general.fields.amount') }}</th>
                                    <td>{{ money($txn->amount, $txn->currency_code) }}</td>
                                </tr>
                                @if($txn->contact)
                                    <tr>
                                        <th class="text-muted">{{ trans('bank-feeds::general.fields.vendor') }}</th>
                                        <td>{{ $txn->contact->name }}</td>
                                    </tr>
                                @endif
                                @if($txn->description)
                                    <tr>
                                        <th class="text-muted">{{ trans('bank-feeds::general.fields.description') }}</th>
                                        <td>{{ $txn->description }}</td>
                                    </tr>
                                @endif
                                @if($txn->document)
                                    <tr>
                                        <th class="text-muted">Document</th>
                                        <td>
                                            {{ ucfirst($txn->document->type) }} #{{ $txn->document->document_number }}
                                            ({{ money($txn->document->amount, $txn->document->currency_code) }})
                                        </td>
                                    </tr>
                                @endif
                                @if($txn->category)
                                    <tr>
                                        <th class="text-muted">{{ trans('bank-feeds::general.fields.category') }}</th>
                                        <td>{{ $txn->category->name }}</td>
                                    </tr>
                                @endif
                            </table>

                            @if(!empty($match['reasons']))
                                <div>
                                    <small class="text-muted">{{ trans('bank-feeds::general.matching.match_reasons') }}:</small>
                                    @foreach($match['reasons'] as $reason)
                                        <span class="badge badge-light">{{ $reason }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-muted">{{ trans('bank-feeds::general.matching.no_suggestions') }}</p>
                            <form action="{{ route('bank-feeds.matching.create-transaction', $transaction->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-plus mr-1"></i>{{ trans('bank-feeds::general.matching.create_new') }}
                                </button>
                            </form>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
