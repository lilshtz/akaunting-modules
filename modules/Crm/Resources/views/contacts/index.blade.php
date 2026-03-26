<x-layouts.admin>
    <x-slot name="title">{{ trans('crm::general.contacts') }}</x-slot>

    <x-slot name="favorite" title="{{ trans('crm::general.contacts') }}" icon="person_search" route="crm.contacts.index"></x-slot>

    <x-slot name="buttons">
        <div class="flex gap-2">
            <x-link href="{{ route('crm.contacts.import') }}" kind="secondary">{{ trans('crm::general.import_contacts') }}</x-link>
            <x-link href="{{ route('crm.contacts.create') }}" kind="primary">{{ trans('general.title.new', ['type' => trans('crm::general.contact')]) }}</x-link>
        </div>
    </x-slot>

    <x-slot name="content">
        <form method="GET" action="{{ route('crm.contacts.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans('crm::general.search_contacts') }}" class="rounded-lg border-gray-300 px-3 py-2 text-sm" />
            <select name="stage" class="rounded-lg border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">{{ trans('crm::general.stage') }}</option>
                @foreach ($stages as $key => $label)
                    <option value="{{ $key }}" {{ request('stage') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="source" class="rounded-lg border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">{{ trans('crm::general.source') }}</option>
                @foreach ($sources as $key => $label)
                    <option value="{{ $key }}" {{ request('source') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="crm_company_id" class="rounded-lg border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                @foreach ($crmCompanies as $id => $name)
                    <option value="{{ $id }}" {{ (string) request('crm_company_id', '') === (string) $id ? 'selected' : '' }}>{{ $id === '' ? trans('crm::general.crm_company') : $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('general.search') }}</button>
        </form>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.crm_company') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.stage') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.source') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.owner') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.activities') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-sky-700">
                                    <a href="{{ route('crm.contacts.show', $contact->id) }}">{{ $contact->name }}</a>
                                </div>
                                <div class="text-xs text-gray-500">{{ $contact->email ?: $contact->phone ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $contact->crmCompany?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $stages[$contact->stage] ?? $contact->stage }}</td>
                            <td class="px-4 py-3 text-sm">{{ $sources[$contact->source] ?? $contact->source }}</td>
                            <td class="px-4 py-3 text-sm">{{ $contact->owner?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $contact->activities_count }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <x-dropdown id="dropdown-contact-{{ $contact->id }}">
                                    <x-dropdown.link href="{{ route('crm.contacts.show', $contact->id) }}">{{ trans('general.show') }}</x-dropdown.link>
                                    <x-dropdown.link href="{{ route('crm.contacts.edit', $contact->id) }}">{{ trans('general.edit') }}</x-dropdown.link>
                                    <x-delete-link :model="$contact" route="crm.contacts.destroy" />
                                </x-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">{{ trans('crm::general.contacts') }}: 0</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($contacts->hasPages())
            <div class="mt-4">{{ $contacts->withQueryString()->links() }}</div>
        @endif
    </x-slot>
</x-layouts.admin>
