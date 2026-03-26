<x-layouts.admin>
    <x-slot name="title">{{ trans('general.title.new', ['type' => trans('crm::general.deal')]) }}</x-slot>

    <x-slot name="content">
        <form method="POST" action="{{ route('crm.deals.store') }}" class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
            @csrf
            @include('crm::deals._form')
            <div class="flex justify-end gap-2">
                <x-link href="{{ route('crm.deals.index') }}" kind="secondary">{{ trans('general.cancel') }}</x-link>
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
            </div>
        </form>
    </x-slot>
</x-layouts.admin>
