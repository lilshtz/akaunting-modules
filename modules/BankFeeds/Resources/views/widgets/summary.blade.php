<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ trans('bank-feeds::general.bank_feed_summary') }}</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col">
                <h3 class="mb-0">{{ $stats['pending'] }}</h3>
                <small class="text-muted">{{ trans('bank-feeds::general.statuses.pending') }}</small>
            </div>
            <div class="col">
                <h3 class="mb-0 text-success">{{ $stats['categorized'] }}</h3>
                <small class="text-muted">{{ trans('bank-feeds::general.statuses.categorized') }}</small>
            </div>
            <div class="col">
                <h3 class="mb-0 text-info">{{ $stats['total_imports'] }}</h3>
                <small class="text-muted">{{ trans('bank-feeds::general.imports') }}</small>
            </div>
        </div>
        <hr>
        <div class="row text-center">
            <div class="col-6">
                <a href="{{ route('bank-feeds.matching.index') }}" class="text-decoration-none">
                    <h3 class="mb-0 {{ $stats['unmatched'] > 0 ? 'text-warning' : 'text-success' }}">
                        {{ $stats['unmatched'] }}
                    </h3>
                    <small class="text-muted">{{ trans('bank-feeds::general.reconciliation_fields.unmatched_count') }}</small>
                </a>
            </div>
            <div class="col-6">
                <a href="{{ route('bank-feeds.reconciliation.index') }}" class="text-decoration-none">
                    <h3 class="mb-0 {{ $stats['unreconciled_accounts'] > 0 ? 'text-warning' : 'text-success' }}">
                        {{ $stats['unreconciled_accounts'] }}
                    </h3>
                    <small class="text-muted">Unreconciled Accounts</small>
                </a>
            </div>
        </div>
    </div>
</div>
