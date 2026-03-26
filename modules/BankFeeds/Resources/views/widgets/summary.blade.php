<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ trans('bank-feeds::general.bank_feed_summary') }}</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-4">
                <h3 class="mb-0">{{ $stats['pending'] }}</h3>
                <small class="text-muted">{{ trans('bank-feeds::general.statuses.pending') }}</small>
            </div>
            <div class="col-4">
                <h3 class="mb-0 text-success">{{ $stats['categorized'] }}</h3>
                <small class="text-muted">{{ trans('bank-feeds::general.statuses.categorized') }}</small>
            </div>
            <div class="col-4">
                <h3 class="mb-0 text-info">{{ $stats['total_imports'] }}</h3>
                <small class="text-muted">{{ trans('bank-feeds::general.imports') }}</small>
            </div>
        </div>
    </div>
</div>
