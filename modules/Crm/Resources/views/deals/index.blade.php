<x-layouts.admin>
    <x-slot name="title">{{ trans('crm::general.deals') }}</x-slot>

    <x-slot name="favorite" title="{{ trans('crm::general.deals') }}" icon="sell" route="crm.deals.index"></x-slot>

    <x-slot name="buttons">
        <div class="flex gap-2">
            <x-link href="{{ route('crm.pipeline-stages.index') }}" kind="secondary">{{ trans('crm::general.customize_pipeline') }}</x-link>
            <x-link href="{{ route('crm.reports.deals') }}" kind="secondary">{{ trans('crm::general.reports') }}</x-link>
            <x-link href="{{ route('crm.deals.create') }}" kind="primary">{{ trans('general.title.new', ['type' => trans('crm::general.deal')]) }}</x-link>
        </div>
    </x-slot>

    <x-slot name="content">
        <form method="GET" action="{{ route('crm.deals.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ trans('crm::general.search_deals') }}" class="rounded-lg border-gray-300 px-3 py-2 text-sm" />
            <select name="crm_contact_id" class="rounded-lg border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">{{ trans('crm::general.contact') }}</option>
                @foreach ($contacts as $id => $name)
                    <option value="{{ $id }}" {{ (string) request('crm_contact_id', '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">{{ trans('crm::general.stage') }} / {{ trans('general.status') }}</option>
                @foreach ($dealStatuses as $status => $label)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('general.search') }}</button>
        </form>

        <div class="mb-4 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-sky-800">
            {{ trans('crm::general.drag_to_move') }}
        </div>

        <div class="flex gap-4 overflow-x-auto pb-2">
            @foreach ($stages as $stage)
                <div class="min-w-[18rem] flex-1 rounded-xl bg-white p-4 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-full" style="background-color: {{ $stage->color }}"></span>
                            <h2 class="font-semibold text-gray-900">{{ $stage->name }}</h2>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600">{{ ($deals->get($stage->id) ?? collect())->count() }}</span>
                    </div>

                    <div class="space-y-3 min-h-[10rem]" data-stage-dropzone="{{ $stage->id }}">
                        @forelse ($deals->get($stage->id, collect()) as $deal)
                            <div class="rounded-lg border border-gray-200 p-4" draggable="true" data-deal-card="{{ $deal->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <a href="{{ route('crm.deals.show', $deal->id) }}" class="font-medium text-sky-700">{{ $deal->name }}</a>
                                        <div class="text-sm text-gray-500">{{ $deal->contact?->name ?? '-' }}</div>
                                    </div>
                                    <div class="text-xs uppercase tracking-wide text-gray-400">{{ $dealStatuses[$deal->status] ?? $deal->status }}</div>
                                </div>
                                <div class="mt-3 space-y-1 text-sm text-gray-600">
                                    <div>{{ money($deal->value, setting('default.currency', 'USD')) }}</div>
                                    <div>{{ trans('crm::general.expected_close') }}: {{ optional($deal->expected_close)->format('M d, Y') ?: '-' }}</div>
                                </div>
                                <form method="POST" action="{{ route('crm.deals.move', $deal->id) }}" class="mt-3">
                                    @csrf
                                    <select name="stage_id" class="w-full rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                                        @foreach ($stages as $stageOption)
                                            <option value="{{ $stageOption->id }}" {{ $stageOption->id === $deal->stage_id ? 'selected' : '' }}>{{ $stageOption->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-200 px-3 py-6 text-center text-sm text-gray-400">
                                {{ trans('crm::general.deals') }}: 0
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        @foreach ($stages as $stage)
            @foreach ($deals->get($stage->id, collect()) as $deal)
                <form method="POST" action="{{ route('crm.deals.move', $deal->id) }}" id="move-deal-{{ $deal->id }}" class="hidden">
                    @csrf
                    <input type="hidden" name="stage_id" value="{{ $deal->stage_id }}" data-stage-input="{{ $deal->id }}" />
                </form>
            @endforeach
        @endforeach

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                let draggedDeal = null;

                document.querySelectorAll('[data-deal-card]').forEach((card) => {
                    card.addEventListener('dragstart', () => {
                        draggedDeal = card.getAttribute('data-deal-card');
                    });
                });

                document.querySelectorAll('[data-stage-dropzone]').forEach((zone) => {
                    zone.addEventListener('dragover', (event) => event.preventDefault());
                    zone.addEventListener('drop', (event) => {
                        event.preventDefault();

                        if (! draggedDeal) {
                            return;
                        }

                        const stageId = zone.getAttribute('data-stage-dropzone');
                        const input = document.querySelector(`[data-stage-input="${draggedDeal}"]`);
                        const form = document.getElementById(`move-deal-${draggedDeal}`);

                        if (! input || ! form) {
                            return;
                        }

                        input.value = stageId;
                        form.submit();
                    });
                });
            });
        </script>
    </x-slot>
</x-layouts.admin>
