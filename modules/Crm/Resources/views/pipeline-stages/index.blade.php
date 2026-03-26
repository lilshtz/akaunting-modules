<x-layouts.admin>
    <x-slot name="title">{{ trans('crm::general.pipeline_stages') }}</x-slot>

    <x-slot name="buttons">
        <div class="flex gap-2">
            <x-link href="{{ route('crm.deals.index') }}" kind="secondary">{{ trans('crm::general.board') }}</x-link>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
            {{ trans('crm::general.reorder_help') }}
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('general.title.new', ['type' => trans('crm::general.pipeline_stage')]) }}</h2>
                <form method="POST" action="{{ route('crm.pipeline-stages.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.name') }}</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ trans('general.color') }}</label>
                        <input type="color" name="color" value="#0ea5e9" class="h-11 w-full rounded-lg border-gray-300" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_won" value="1" class="rounded border-gray-300" />
                        {{ trans('crm::general.mark_won') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_lost" value="1" class="rounded border-gray-300" />
                        {{ trans('crm::general.mark_lost') }}
                    </label>
                    <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
                </form>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('crm::general.pipeline') }}</h2>
                <form method="POST" action="{{ route('crm.pipeline-stages.reorder') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-3">
                        @foreach ($stages as $index => $stage)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-4">
                                <input type="hidden" name="stages[]" value="{{ $stage->id }}" />
                                <div class="flex items-center gap-3">
                                    <span class="inline-block h-3 w-3 rounded-full" style="background-color: {{ $stage->color }}"></span>
                                    <div>
                                        <div class="font-medium">{{ $stage->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $stage->deals_count }} {{ trans('crm::general.deals') }}</div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onclick="moveStage(this, -1)">↑</button>
                                    <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onclick="moveStage(this, 1)">↓</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="mt-4 rounded-lg bg-slate-800 px-4 py-2 text-sm text-white">{{ trans('crm::general.customize_pipeline') }}</button>
                </form>
            </div>
        </div>

        <div class="mt-4 rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">{{ trans('general.edit') }}</h2>
            <div class="space-y-4">
                @foreach ($stages as $stage)
                    <div class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 p-4 md:grid-cols-[1fr_auto]">
                        <form method="POST" action="{{ route('crm.pipeline-stages.update', $stage->id) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="name" value="{{ $stage->name }}" required class="rounded-lg border-gray-300 text-sm" />
                            <input type="color" name="color" value="{{ $stage->color }}" class="h-11 rounded-lg border-gray-300" />
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_won" value="1" {{ $stage->is_won ? 'checked' : '' }} class="rounded border-gray-300" />
                                {{ trans('crm::general.mark_won') }}
                            </label>
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_lost" value="1" {{ $stage->is_lost ? 'checked' : '' }} class="rounded border-gray-300" />
                                {{ trans('crm::general.mark_lost') }}
                            </label>
                            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white md:col-span-4 md:justify-self-start">{{ trans('general.save') }}</button>
                        </form>
                        <form method="POST" action="{{ route('crm.pipeline-stages.destroy', $stage->id) }}" class="flex items-start justify-end">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm text-white">{{ trans('general.delete') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <script>
            function moveStage(button, direction) {
                const card = button.closest('.rounded-lg.border');
                const container = card.parentElement;
                const sibling = direction < 0 ? card.previousElementSibling : card.nextElementSibling;

                if (! sibling) {
                    return;
                }

                if (direction < 0) {
                    container.insertBefore(card, sibling);
                } else {
                    container.insertBefore(sibling, card);
                }
            }
        </script>
    </x-slot>
</x-layouts.admin>
