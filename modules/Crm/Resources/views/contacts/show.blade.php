<x-layouts.admin>
    <x-slot name="title">{{ $contact->name }}</x-slot>

    <x-slot name="buttons">
        <div class="flex gap-2">
            <x-link href="{{ route('crm.contacts.edit', $contact->id) }}" kind="secondary">{{ trans('general.edit') }}</x-link>
            <x-link href="{{ route('crm.contacts.index') }}" kind="primary">{{ trans('crm::general.contacts') }}</x-link>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">{{ trans('general.information') }}</h2>
                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.crm_company') }}</dt><dd>{{ $contact->crmCompany?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.owner') }}</dt><dd>{{ $contact->owner?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('general.email') }}</dt><dd>{{ $contact->email ?? '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('general.phone') }}</dt><dd>{{ $contact->phone ?? '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.source') }}</dt><dd>{{ $sources[$contact->source] ?? $contact->source }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.stage') }}</dt><dd>{{ $stages[$contact->stage] ?? $contact->stage }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.akaunting_customer') }}</dt><dd>{{ $contact->akauntingContact?->name ? $contact->akauntingContact->name . ' (#' . $contact->akauntingContact->id . ')' : '-' }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs uppercase text-gray-500">{{ trans('general.notes') }}</dt><dd class="whitespace-pre-line">{{ $contact->notes ?: '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">{{ trans('crm::general.timeline') }}</h2>
                        <span class="text-sm text-gray-500">{{ $contact->activities->count() }} {{ trans('crm::general.activities') }}</span>
                    </div>
                    <div class="space-y-4">
                        @forelse ($contact->activities as $activity)
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <div class="font-medium">{{ $activityTypes[$activity->type] ?? $activity->type }}</div>
                                        <div class="text-xs text-gray-500">{{ $activity->user?->name ?? '-' }} • {{ optional($activity->created_at)->format('M d, Y H:i') }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('crm.activities.destroy', $activity->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600">{{ trans('general.delete') }}</button>
                                    </form>
                                </div>
                                <div class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $activity->description }}</div>
                                @if ($activity->scheduled_at || $activity->completed_at)
                                    <div class="mt-2 text-xs text-gray-500">
                                        @if ($activity->scheduled_at)
                                            <div>{{ trans('crm::general.scheduled_for') }}: {{ $activity->scheduled_at->format('M d, Y H:i') }}</div>
                                        @endif
                                        @if ($activity->completed_at)
                                            <div>{{ trans('crm::general.completed_at') }}: {{ $activity->completed_at->format('M d, Y H:i') }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ trans('crm::general.activities') }}: 0</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">{{ trans('crm::general.linked_invoices') }}</h2>
                    <div class="space-y-3">
                        @forelse ($invoices as $invoice)
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="font-medium">{{ $invoice->document_number ?: ('#' . $invoice->id) }}</div>
                                <div class="text-sm text-gray-500">{{ optional($invoice->issued_at)->format('M d, Y') }} • {{ money($invoice->amount, $invoice->currency_code) }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">{{ trans('crm::general.linked_invoices') }}: 0</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h2 class="mb-2 text-lg font-semibold">{{ trans('crm::general.linked_deals') }}</h2>
                    <p class="text-sm text-gray-500">{{ trans('crm::general.no_linked_deals') }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('general.title.new', ['type' => trans('crm::general.activity')]) }}</h2>
                <form method="POST" action="{{ route('crm.contacts.activities.store', $contact->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.type') }}</label>
                        <select name="type" class="w-full rounded-lg border-gray-300">
                            @foreach ($activityTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.description') }}</label>
                        <textarea name="description" rows="5" class="w-full rounded-lg border-gray-300" required>{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.scheduled_for') }}</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('crm::general.completed_at') }}</label>
                        <input type="datetime-local" name="completed_at" value="{{ old('completed_at') }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
                </form>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
