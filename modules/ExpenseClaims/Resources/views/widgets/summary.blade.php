<div class="card">
    <div class="card-header">
        <h3 class="mb-0">{{ trans('expense-claims::general.widget_summary') }}</h3>
    </div>
    <div class="card-body">
        <p><strong>Pending:</strong> {{ $pendingCount }}</p>
        <p><strong>Approved:</strong> {{ money($approvedTotal, setting('default.currency', 'USD')) }}</p>
        <p><strong>Reimbursable:</strong> {{ money($reimbursableTotal, setting('default.currency', 'USD')) }}</p>

        <table class="table table-sm">
            <thead>
            <tr>
                <th>#</th>
                <th>{{ trans('expense-claims::general.employee') }}</th>
                <th>{{ trans('expense-claims::general.status') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($recentClaims as $claim)
                <tr>
                    <td>{{ $claim->claim_number ?: $claim->id }}</td>
                    <td>{{ $claim->employee_name }}</td>
                    <td>{{ $claim->status_label }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
