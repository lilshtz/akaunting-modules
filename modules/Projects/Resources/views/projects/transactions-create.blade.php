<x-layouts.admin>
    <x-slot name="title">
        {{ trans('projects::general.link_document') }}
    </x-slot>

    <x-slot name="content">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('projects.projects.transactions.store', $project->id) }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.transaction') }}</label>
                        <select name="document_type" class="w-full rounded-lg border-gray-300 text-sm" onchange="window.location='{{ route('projects.projects.transactions.create', $project->id) }}?document_type='+this.value">
                            <option value="invoice" {{ request('document_type', 'invoice') === 'invoice' ? 'selected' : '' }}>{{ trans('projects::general.invoice') }}</option>
                            <option value="bill" {{ request('document_type') === 'bill' ? 'selected' : '' }}>{{ trans('projects::general.bill') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.link_existing_document') }}</label>
                        <select name="document_id" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach (($documentsByType[request('document_type', 'invoice')] ?? collect()) as $document)
                                <option value="{{ $document->id }}">
                                    {{ ($document->document_number ?: '#' . $document->id) . ' - ' . ($document->contact_name ?: trans('general.na')) . ' - ' . money($document->amount, $document->currency_code ?: setting('default.currency', 'USD')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-between gap-3">
                    <div class="flex gap-2">
                        @if (Route::has('sales.invoices.create'))
                            <x-link href="{{ route('sales.invoices.create') }}">
                                {{ trans('projects::general.create_invoice') }}
                            </x-link>
                        @endif
                        @if (Route::has('purchases.bills.create'))
                            <x-link href="{{ route('purchases.bills.create') }}">
                                {{ trans('projects::general.create_bill') }}
                            </x-link>
                        @endif
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">
                        {{ trans('general.save') }}
                    </button>
                </div>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
