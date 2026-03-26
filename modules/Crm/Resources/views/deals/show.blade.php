<x-layouts.admin>
    <x-slot name="title">{{ $deal->name }}</x-slot>

    <x-slot name="buttons">
        <div class="flex gap-2">
            <x-link href="{{ route('crm.deals.edit', $deal->id) }}" kind="secondary">{{ trans('general.edit') }}</x-link>
            <x-link href="{{ route('crm.deals.index') }}" kind="primary">{{ trans('crm::general.deals') }}</x-link>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">{{ trans('general.information') }}</h2>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('crm.deals.status', $deal->id) }}">
                                @csrf
                                <input type="hidden" name="status" value="won" />
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm text-white">{{ trans('crm::general.mark_won') }}</button>
                            </form>
                            <form method="POST" action="{{ route('crm.deals.status', $deal->id) }}">
                                @csrf
                                <input type="hidden" name="status" value="lost" />
                                <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-sm text-white">{{ trans('crm::general.mark_lost') }}</button>
                            </form>
                            @if ($deal->status !== 'open')
                                <form method="POST" action="{{ route('crm.deals.status', $deal->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="open" />
                                    <button type="submit" class="rounded-lg bg-slate-700 px-3 py-2 text-sm text-white">{{ trans('crm::general.reopen_deal') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.contact') }}</dt><dd><a href="{{ route('crm.contacts.show', $deal->contact->id) }}" class="text-sky-700">{{ $deal->contact->name }}</a></dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.crm_company') }}</dt><dd>{{ $deal->contact->crmCompany?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.stage') }}</dt><dd>{{ $deal->stage?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('general.status') }}</dt><dd>{{ $dealStatuses[$deal->status] ?? $deal->status }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.deal_value') }}</dt><dd>{{ money($deal->value, setting('default.currency', 'USD')) }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.expected_close') }}</dt><dd>{{ optional($deal->expected_close)->format('M d, Y') ?: '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.owner') }}</dt><dd>{{ $deal->contact->owner?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.completed_at') }}</dt><dd>{{ optional($deal->closed_at)->format('M d, Y H:i') ?: '-' }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.linked_invoice') }}</dt><dd>{{ $deal->invoice ? (($deal->invoice->document_number ?: '#' . $deal->invoice->id) . ' - ' . money($deal->invoice->amount, $deal->invoice->currency_code)) : '-' }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs uppercase text-gray-500">{{ trans('general.notes') }}</dt><dd class="whitespace-pre-line">{{ $deal->notes ?: '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">{{ trans('crm::general.stage_history') }}</h2>
                        <span class="text-sm text-gray-500">{{ $deal->activities->count() }} {{ trans('crm::general.activities') }}</span>
                    </div>
                    <div class="space-y-4">
                        @forelse ($deal->activities as $activity)
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
            </div>

            <div class="space-y-4">
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">{{ trans('crm::general.contact') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">{{ $deal->contact->name }}</span></div>
                        <div>{{ $deal->contact->email ?: '-' }}</div>
                        <div>{{ $deal->contact->phone ?: '-' }}</div>
                        <div>{{ $deal->contact->akauntingContact?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">{{ trans('general.title.new', ['type' => trans('crm::general.activity')]) }}</h2>
                    <form method="POST" action="{{ route('crm.deals.activities.store', $deal->id) }}" class="space-y-4">
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
        </div>
    </x-slot>
</x-layouts.admin>
