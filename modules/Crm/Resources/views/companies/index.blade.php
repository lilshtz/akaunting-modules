<x-layouts.admin>
    <x-slot name="title">{{ trans('crm::general.companies') }}</x-slot>

    <x-slot name="favorite" title="{{ trans('crm::general.companies') }}" icon="domain" route="crm.companies.index"></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('crm.companies.create') }}" kind="primary">{{ trans('general.title.new', ['type' => trans('crm::general.company')]) }}</x-link>
    </x-slot>

    <x-slot name="content">
        <form method="GET" action="{{ route('crm.companies.index') }}" class="mb-4 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans('general.search') }}" class="rounded-lg border-gray-300 px-3 py-2 text-sm" />
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('general.search') }}</button>
        </form>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.name') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.currency') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.default_stage') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.contacts') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm"><a href="{{ route('crm.companies.show', $company->id) }}" class="font-medium text-sky-700">{{ $company->name }}</a></td>
                            <td class="px-4 py-3 text-sm">{{ $company->currency ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $stages[$company->default_stage] ?? $company->default_stage }}</td>
                            <td class="px-4 py-3 text-sm">{{ $company->contacts_count }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <x-dropdown id="dropdown-company-{{ $company->id }}">
                                    <x-dropdown.link href="{{ route('crm.companies.show', $company->id) }}">{{ trans('general.show') }}</x-dropdown.link>
                                    <x-dropdown.link href="{{ route('crm.companies.edit', $company->id) }}">{{ trans('general.edit') }}</x-dropdown.link>
                                    <x-delete-link :model="$company" route="crm.companies.destroy" />
                                </x-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ trans('crm::general.companies') }}: 0</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($companies->hasPages())
            <div class="mt-4">{{ $companies->withQueryString()->links() }}</div>
        @endif
    </x-slot>
</x-layouts.admin>
