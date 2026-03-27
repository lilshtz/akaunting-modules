<x-layouts.admin>
    <x-slot name="title">
        {{ trans('pos::general.settings') }}
    </x-slot>

    <section class="max-w-2xl rounded-xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('pos.settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">{{ trans('pos::general.receipt_width') }}</label>
                <input type="number" min="40" max="120" name="receipt_width" value="{{ old('receipt_width', $setting->receipt_width) }}" class="w-full rounded-lg border border-gray-200 px-4 py-3">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">{{ trans('pos::general.default_payment_method') }}</label>
                <select name="default_payment_method" class="w-full rounded-lg border border-gray-200 px-4 py-3">
                    @foreach (['cash', 'card', 'split'] as $method)
                        <option value="{{ $method }}" @selected(old('default_payment_method', $setting->default_payment_method) === $method)>{{ trans('pos::general.payment_methods.' . $method) }}</option>
                    @endforeach
                </select>
            </div>

            <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3">
                <input type="checkbox" name="auto_create_invoice" value="1" @checked(old('auto_create_invoice', $setting->auto_create_invoice))>
                <span>{{ trans('pos::general.auto_create_invoice') }}</span>
            </label>

            <button type="submit" class="rounded-lg bg-black px-4 py-3 text-sm font-semibold text-white">{{ trans('general.save') }}</button>
        </form>
    </section>
</x-layouts.admin>
