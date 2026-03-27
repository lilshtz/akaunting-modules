<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.chart_of_accounts') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.chart_of_accounts') }}"
        icon="balance"
        route="double-entry.accounts.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.accounts.import') }}" kind="secondary">
            {{ trans('double-entry::general.import_csv') }}
        </x-link>
        <x-form id="seed-accounts" method="POST" route="double-entry.accounts.seed">
            <x-button type="submit">
                {{ trans('double-entry::general.seed_default_coa') }}
            </x-button>
        </x-form>
        <x-link href="{{ route('double-entry.accounts.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('double-entry::general.account')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        @forelse ($accounts as $type => $rows)
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-3">{{ trans('double-entry::general.types.' . $type) }}</h2>

                <x-table>
                    <x-table.thead>
                        <x-table.tr class="flex items-center px-1">
                            <x-table.th class="w-2/12">{{ trans('double-entry::general.account_code') }}</x-table.th>
                            <x-table.th class="w-4/12">{{ trans('double-entry::general.account') }}</x-table.th>
                            <x-table.th class="w-2/12">{{ trans('double-entry::general.detail_type') }}</x-table.th>
                            <x-table.th class="w-2/12">{{ trans('double-entry::general.opening_balance') }}</x-table.th>
                            <x-table.th class="w-1/12">{{ trans('general.enabled') }}</x-table.th>
                            <x-table.th class="w-1/12">{{ trans('general.actions') }}</x-table.th>
                        </x-table.tr>
                    </x-table.thead>
                    <x-table.tbody>
                        @foreach ($rows as $account)
                            <x-table.tr>
                                <x-table.td class="w-2/12">{{ $account->code }}</x-table.td>
                                <x-table.td class="w-4/12">
                                    <a href="{{ route('double-entry.accounts.edit', $account->id) }}" class="border-b">
                                        {{ str_repeat('— ', (int) ($account->depth ?? 0)) }}{{ $account->name }}
                                    </a>
                                </x-table.td>
                                <x-table.td class="w-2/12">{{ $account->detail_type ?: trans('general.na') }}</x-table.td>
                                <x-table.td class="w-2/12">@money($account->opening_balance, setting('default.currency', 'USD'), true)</x-table.td>
                                <x-table.td class="w-1/12">{{ $account->enabled ? trans('general.yes') : trans('general.no') }}</x-table.td>
                                <x-table.td class="w-1/12">
                                    <x-dropdown id="account-{{ $account->id }}">
                                        <x-dropdown.link href="{{ route('double-entry.accounts.edit', $account->id) }}">
                                            {{ trans('general.edit') }}
                                        </x-dropdown.link>
                                        <x-delete-link :model="$account" route="double-entry.accounts.destroy" />
                                    </x-dropdown>
                                </x-table.td>
                            </x-table.tr>
                        @endforeach
                    </x-table.tbody>
                </x-table>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
                {{ trans('general.no_records') }}
            </div>
        @endforelse
    </x-slot>
</x-layouts.admin>
