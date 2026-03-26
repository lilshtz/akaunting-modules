<x-layouts.admin>
    <x-slot name="title">{{ $company->name }}</x-slot>

    <x-slot name="buttons">
        <div class="flex gap-2">
            <x-link href="{{ route('crm.companies.edit', $company->id) }}" kind="secondary">{{ trans('general.edit') }}</x-link>
            <x-link href="{{ route('crm.companies.index') }}" kind="primary">{{ trans('crm::general.companies') }}</x-link>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-xl bg-white p-6 shadow-sm lg:col-span-1">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('general.information') }}</h2>
                <dl class="space-y-3">
                    <div><dt class="text-xs uppercase text-gray-500">{{ trans('general.currency') }}</dt><dd>{{ $company->currency ?: '-' }}</dd></div>
                    <div><dt class="text-xs uppercase text-gray-500">{{ trans('crm::general.default_stage') }}</dt><dd>{{ trans('crm::general.stages.' . $company->default_stage) }}</dd></div>
                    <div><dt class="text-xs uppercase text-gray-500">{{ trans('general.address') }}</dt><dd class="whitespace-pre-line">{{ $company->address ?: '-' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('crm::general.contacts') }}</h2>
                <div class="space-y-3">
                    @forelse ($company->contacts as $contact)
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <a href="{{ route('crm.contacts.show', $contact->id) }}" class="font-medium text-sky-700">{{ $contact->name }}</a>
                                    <div class="text-sm text-gray-500">{{ $contact->email ?: $contact->phone ?: '-' }}</div>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <div>{{ trans('crm::general.stages.' . $contact->stage) }}</div>
                                    <div>{{ $contact->owner?->name ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ trans('crm::general.contacts') }}: 0</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>
