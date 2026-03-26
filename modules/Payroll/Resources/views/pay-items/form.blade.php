<x-layouts.admin>
    <x-slot name="title">
        {{ $item ? trans('general.title.edit', ['type' => trans('payroll::general.pay_item')]) : trans('general.title.new', ['type' => trans('payroll::general.pay_item')]) }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-3xl bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ $route }}" class="space-y-4">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $item?->name) }}" class="w-full rounded-lg border-gray-300" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.type') }}</label>
                    <select name="type" class="w-full rounded-lg border-gray-300" required>
                        @foreach (['benefit', 'deduction'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $item?->type) === $type)>{{ trans('payroll::general.types.' . $type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.default_amount') }}</label>
                    <input type="number" step="0.0001" min="0" name="default_amount" value="{{ old('default_amount', $item?->default_amount) }}" class="w-full rounded-lg border-gray-300" />
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_percentage" value="1" @checked(old('is_percentage', $item?->is_percentage)) />
                    <span>{{ trans('payroll::general.percentage') }}</span>
                </label>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $item?->enabled ?? true)) />
                    <span>{{ trans('general.enabled') }}</span>
                </label>

                <div class="flex gap-3">
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
                    <a href="{{ route('payroll.pay-items.index') }}" class="px-4 py-2 rounded-lg border border-gray-300">{{ trans('general.cancel') }}</a>
                </div>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>
