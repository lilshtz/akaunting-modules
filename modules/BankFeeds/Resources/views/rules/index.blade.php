@extends('layouts.admin')

@section('title', trans('bank-feeds::general.categorization_rules'))

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.categorization_rules') }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('bank-feeds.rules.apply') }}">
                    @csrf
                    <button type="submit" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ trans('bank-feeds::general.apply_rules') }}
                    </button>
                </form>
                <a href="{{ route('bank-feeds.rules.create') }}" class="inline-flex rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    {{ trans('general.title.new', ['type' => trans('bank-feeds::general.rule')]) }}
                </a>
            </div>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('bank-feeds::general.priority') }}</x-table.th>
                        <x-table.th>{{ trans('general.name') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.field') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.operator') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.value') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.category') }}</x-table.th>
                        <x-table.th>{{ trans('general.enabled') }}</x-table.th>
                        <x-table.th>{{ trans('general.actions') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse ($rules as $rule)
                        <x-table.tr>
                            <x-table.td>{{ $rule->priority }}</x-table.td>
                            <x-table.td>{{ $rule->name }}</x-table.td>
                            <x-table.td>{{ trans('bank-feeds::general.rule_fields.' . $rule->field) }}</x-table.td>
                            <x-table.td>{{ trans('bank-feeds::general.operators.' . $rule->operator) }}</x-table.td>
                            <x-table.td>{{ $rule->value }}{{ $rule->value_end ? ' / ' . $rule->value_end : '' }}</x-table.td>
                            <x-table.td>{{ $rule->category ? trim($rule->category->code . ' - ' . $rule->category->name) : '—' }}</x-table.td>
                            <x-table.td>{{ $rule->enabled ? trans('bank-feeds::general.enabled_label') : trans('bank-feeds::general.disabled_label') }}</x-table.td>
                            <x-table.td>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('bank-feeds.rules.edit', $rule->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ trans('general.edit') }}</a>
                                    <form method="POST" action="{{ route('bank-feeds.rules.destroy', $rule->id) }}" onsubmit="return confirm('{{ trans('messages.warning.confirm.delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">{{ trans('general.delete') }}</button>
                                    </form>
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.tr>
                            <x-table.td colspan="8" class="py-6 text-center text-sm text-gray-500">{{ trans('bank-feeds::general.rules_empty') }}</x-table.td>
                        </x-table.tr>
                    @endforelse
                </x-table.tbody>
            </x-table>
        </div>

        {{ $rules->links() }}
    </div>
@endsection
