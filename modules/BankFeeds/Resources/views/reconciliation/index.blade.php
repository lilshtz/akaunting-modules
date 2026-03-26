@extends('layouts.admin')

@section('title', trans('bank-feeds::general.reconciliation'))

@section('content')
    {{-- Start New Reconciliation --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Start New Reconciliation</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('bank-feeds.reconciliation.create') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ trans('bank-feeds::general.reconciliation_fields.bank_account') }}</label>
                            <select name="bank_account_id" class="form-control" required>
                                <option value="">— Select Account —</option>
                                @foreach($accounts as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ trans('bank-feeds::general.reconciliation_fields.start_date') }}</label>
                            <input type="date" name="statement_start_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ trans('bank-feeds::general.reconciliation_fields.end_date') }}</label>
                            <input type="date" name="statement_end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ trans('bank-feeds::general.reconciliation_fields.opening_balance') }}</label>
                            <input type="number" name="opening_balance" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ trans('bank-feeds::general.reconciliation_fields.closing_balance') }}</label>
                            <input type="number" name="closing_balance" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-play"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Reconciliation History --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Reconciliation History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-flush table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>{{ trans('bank-feeds::general.reconciliation_fields.bank_account') }}</th>
                        <th>{{ trans('bank-feeds::general.reconciliation_fields.statement_period') }}</th>
                        <th class="text-right">{{ trans('bank-feeds::general.reconciliation_fields.opening_balance') }}</th>
                        <th class="text-right">{{ trans('bank-feeds::general.reconciliation_fields.closing_balance') }}</th>
                        <th class="text-right">{{ trans('bank-feeds::general.reconciliation_fields.difference') }}</th>
                        <th class="text-center">{{ trans('bank-feeds::general.fields.status') }}</th>
                        <th class="text-center">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $recon)
                        <tr>
                            <td>{{ $recon->bank_account_id }}</td>
                            <td>
                                {{ $recon->statement_start_date->format('M d, Y') }}
                                — {{ $recon->statement_end_date->format('M d, Y') }}
                            </td>
                            <td class="text-right">
                                {{ money($recon->opening_balance, setting('default.currency', 'USD')) }}
                            </td>
                            <td class="text-right">
                                {{ money($recon->closing_balance, setting('default.currency', 'USD')) }}
                            </td>
                            <td class="text-right {{ abs($recon->difference) < 0.01 ? 'text-success' : 'text-danger' }}">
                                {{ money($recon->difference, setting('default.currency', 'USD')) }}
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = $recon->status === 'completed' ? 'success' : 'warning';
                                @endphp
                                <span class="badge badge-{{ $statusClass }}">
                                    {{ trans('bank-feeds::general.statuses.' . $recon->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-neutral" data-toggle="dropdown">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if($recon->status !== 'completed')
                                            <a class="dropdown-item" href="{{ route('bank-feeds.reconciliation.show', $recon->id) }}">
                                                <i class="fa fa-edit mr-2"></i>Continue
                                            </a>
                                        @else
                                            <a class="dropdown-item" href="{{ route('bank-feeds.reconciliation.show', $recon->id) }}">
                                                <i class="fa fa-eye mr-2"></i>View
                                            </a>
                                        @endif
                                        <form action="{{ route('bank-feeds.reconciliation.destroy', $recon->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this reconciliation?')">
                                                <i class="fa fa-trash mr-2"></i>{{ trans('general.delete') }}
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
            {{ $reconciliations->links() }}
        </div>
    </div>
@endsection
