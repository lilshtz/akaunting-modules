<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.chart_of_accounts') }}</x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.accounts.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('double-entry::general.account')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-6">
            @foreach($types as $type)
                @if(isset($accounts[$type]) && $accounts[$type]->count())
                    <div class="rounded-xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">
                            {{ trans('double-entry::general.types.' . $type) }}
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b text-gray-500">
                                        <th class="pb-3 pr-6">{{ trans('double-entry::general.code') }}</th>
                                        <th class="pb-3 pr-6">{{ trans('general.name') }}</th>
                                        <th class="pb-3 pr-6 text-right">{{ trans('double-entry::general.balance') }}</th>
                                        <th class="pb-3 pr-6 text-center">{{ trans('general.enabled') }}</th>
                                        <th class="pb-3 text-right">{{ trans('general.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accounts[$type] as $account)
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 pr-6 font-mono text-sm">{{ $account->code }}</td>
                                            <td class="py-3 pr-6">
                                                <a href="{{ route('double-entry.accounts.edit', $account->id) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                                    {{ $account->name }}
                                                </a>
                                            </td>
                                            <td class="py-3 pr-6 text-right font-mono">
                                                {{ number_format($account->balance, 2) }}
                                            </td>
                                            <td class="py-3 pr-6 text-center">
                                                @if($account->enabled)
                                                    <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ trans('general.yes') }}</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">{{ trans('general.no') }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('double-entry.accounts.edit', $account->id) }}" class="text-gray-400 hover:text-gray-600">
                                                        <span class="material-icons text-lg">edit</span>
                                                    </a>
                                                    <form action="{{ route('double-entry.accounts.destroy', $account->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ trans('general.delete_confirm', ['name' => $account->name]) }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-gray-400 hover:text-red-600">
                                                            <span class="material-icons text-lg">delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Sub-accounts --}}
                                        @foreach($account->children as $child)
                                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                                <td class="py-3 pl-6 pr-6 font-mono text-sm text-gray-500">{{ $child->code }}</td>
                                                <td class="py-3 pr-6 pl-6 text-gray-600">
                                                    <a href="{{ route('double-entry.accounts.edit', $child->id) }}" class="hover:text-blue-600">
                                                        &#8627; {{ $child->name }}
                                                    </a>
                                                </td>
                                                <td class="py-3 pr-6 text-right font-mono text-gray-500">
                                                    {{ number_format($child->balance, 2) }}
                                                </td>
                                                <td class="py-3 pr-6 text-center">
                                                    @if($child->enabled)
                                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ trans('general.yes') }}</span>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">{{ trans('general.no') }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <a href="{{ route('double-entry.accounts.edit', $child->id) }}" class="text-gray-400 hover:text-gray-600">
                                                            <span class="material-icons text-lg">edit</span>
                                                        </a>
                                                        <form action="{{ route('double-entry.accounts.destroy', $child->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ trans('general.delete_confirm', ['name' => $child->name]) }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-gray-400 hover:text-red-600">
                                                                <span class="material-icons text-lg">delete</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach

            @if($accounts->isEmpty())
                <div class="rounded-xl bg-white p-12 text-center shadow-sm">
                    <span class="material-icons mb-2 text-4xl text-gray-300">account_balance</span>
                    <p class="text-gray-500">{{ trans('general.no_records') }}</p>
                </div>
            @endif
        </div>
    </x-slot>
</x-layouts.admin>
