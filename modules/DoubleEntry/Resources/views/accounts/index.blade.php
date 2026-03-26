<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.chart_of_accounts') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.chart_of_accounts') }}"
        icon="account_tree"
        route="double-entry.accounts.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.accounts.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans_choice('double-entry::general.accounts', 1)]) }}
        </x-link>

        <x-link href="{{ route('double-entry.accounts.import') }}">
            {{ trans('import.import') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        @foreach ($types as $type)
            @php
                $typeAccounts = $accountsByType[$type] ?? collect();
            @endphp

            @if ($typeAccounts->isNotEmpty())
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-3 capitalize">
                        {{ trans('double-entry::general.types.' . $type) }}
                    </h2>

                    <div class="bg-white rounded-xl shadow-sm">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account_code') }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account_name') }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.opening_balance') }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.enabled') }}</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($typeAccounts as $account)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm">
                                            @if ($account->parent_id)
                                                <span class="ml-6">↳ </span>
                                            @endif
                                            {{ $account->code }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($account->parent_id)
                                                <span class="ml-6"></span>
                                            @endif
                                            <a href="{{ route('double-entry.accounts.edit', $account->id) }}" class="text-purple-700 hover:underline">
                                                {{ $account->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm">{{ number_format($account->opening_balance, 2) }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($account->enabled)
                                                <span class="text-green-600">{{ trans('general.yes') }}</span>
                                            @else
                                                <span class="text-red-600">{{ trans('general.no') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <x-dropdown id="dropdown-{{ $account->id }}">
                                                <x-dropdown.link href="{{ route('double-entry.accounts.edit', $account->id) }}">
                                                    {{ trans('general.edit') }}
                                                </x-dropdown.link>
                                                <x-delete-link :model="$account" route="double-entry.accounts.destroy" />
                                            </x-dropdown>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach
    </x-slot>
</x-layouts.admin>
