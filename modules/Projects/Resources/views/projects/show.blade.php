<x-layouts.admin>
    <x-slot name="title">
        {{ $project->name }}
    </x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('projects.projects.edit', $project->id) }}" kind="primary">
            {{ trans('general.edit') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <div class="mb-6 rounded-3xl bg-slate-900 p-6 text-white shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-3xl font-semibold">{{ $project->name }}</h2>
                        <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-medium">
                            {{ $project->status_label }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-200">{{ $project->contact?->name ?? trans('general.na') }}</p>
                    <p class="mt-4 max-w-3xl text-sm text-slate-200 whitespace-pre-wrap">{{ $project->description ?: trans('projects::general.empty_state') }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-2xl bg-white/10 p-4">
                        <div class="text-slate-300">{{ trans('projects::general.progress') }}</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $project->progress_percentage }}%</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4">
                        <div class="text-slate-300">{{ trans('projects::general.budget') }}</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $project->budget_display }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 flex flex-wrap gap-2">
            @foreach (trans('projects::general.tabs') as $key => $label)
                <a href="{{ route('projects.projects.show', ['project' => $project->id, 'tab' => $key]) }}"
                    class="rounded-full px-4 py-2 text-sm {{ $tab === $key ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 shadow-sm' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($tab === 'overview')
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.dashboard') }}</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="rounded-2xl bg-emerald-50 p-4">
                                <div class="text-sm text-emerald-700">{{ trans('projects::general.milestones') }}</div>
                                <div class="mt-1 text-2xl font-semibold text-emerald-900">{{ $project->milestones->count() }}</div>
                            </div>
                            <div class="rounded-2xl bg-blue-50 p-4">
                                <div class="text-sm text-blue-700">{{ trans('projects::general.tasks') }}</div>
                                <div class="mt-1 text-2xl font-semibold text-blue-900">{{ $project->tasks->count() }}</div>
                            </div>
                            <div class="rounded-2xl bg-amber-50 p-4">
                                <div class="text-sm text-amber-700">{{ trans('projects::general.transactions') }}</div>
                                <div class="mt-1 text-2xl font-semibold text-amber-900">{{ $project->transactions->count() }}</div>
                            </div>
                            <div class="rounded-2xl bg-rose-50 p-4">
                                <div class="text-sm text-rose-700">{{ trans('projects::general.timesheets') }}</div>
                                <div class="mt-1 text-2xl font-semibold text-rose-900">{{ $timesheets->count() }}</div>
                            </div>
                            <div class="rounded-2xl bg-violet-50 p-4">
                                <div class="text-sm text-violet-700">{{ trans('projects::general.tracked_hours') }}</div>
                                <div class="mt-1 text-2xl font-semibold text-violet-900">{{ number_format($report['summary']['tracked_hours'], 2) }}h</div>
                            </div>
                            <div class="rounded-2xl bg-cyan-50 p-4">
                                <div class="text-sm text-cyan-700">{{ trans('projects::general.profit') }}</div>
                                <div class="mt-1 text-2xl font-semibold text-cyan-900">{{ money($report['summary']['profit'], setting('default.currency', 'USD')) }}</div>
                            </div>
                        </div>
                    </div>

                    @if ($activeTimers->isNotEmpty())
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.running_timers') }}</h3>
                            <div class="mt-4 space-y-3">
                                @foreach ($activeTimers as $timer)
                                    <div class="flex flex-col gap-2 rounded-xl border border-emerald-100 bg-emerald-50 p-4 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="font-medium text-emerald-900">{{ $timer->task?->name ?? trans('projects::general.task') }}</div>
                                            <div class="text-sm text-emerald-700">{{ $timer->user?->name ?? trans('general.na') }} · {{ $timer->started_at?->format('M d, Y H:i') }}</div>
                                        </div>
                                        <div class="text-sm font-semibold text-emerald-900">{{ number_format($timer->tracked_hours, 2) }}h</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.timeline') }}</h3>
                        <div class="mt-4 space-y-4">
                            @forelse ($project->milestones as $milestone)
                                <div class="flex gap-4">
                                    <div class="mt-1 h-3 w-3 rounded-full {{ $milestone->completed_at ? 'bg-emerald-500' : 'bg-slate-300' }}"></div>
                                    <div class="flex-1 border-l border-dashed border-slate-200 pl-4 pb-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="font-medium text-slate-900">{{ $milestone->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $milestone->target_date?->format('M d, Y') ?? '-' }}</div>
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600">{{ $milestone->description ?: trans('projects::general.empty_state') }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.recent_tasks') }}</h3>
                            <a href="{{ route('projects.projects.show', ['project' => $project->id, 'tab' => 'tasks']) }}" class="text-sm text-blue-700 hover:underline">{{ trans('general.show') }}</a>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($project->tasks->sortByDesc('updated_at')->take(5) as $task)
                                <div class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3">
                                    <div>
                                        <div class="font-medium text-slate-900">{{ $task->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $task->assignee?->name ?? trans('general.na') }}</div>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $task->status_label }}</span>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.transaction_summary') }}</h3>
                        <div class="mt-4 space-y-4 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">{{ trans('projects::general.invoice') }}</span>
                                <span class="font-medium">{{ $transactionSummary['invoice_count'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">{{ trans('projects::general.bill') }}</span>
                                <span class="font-medium">{{ $transactionSummary['bill_count'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">{{ trans('projects::general.invoice_total') }}</span>
                                <span class="font-medium">{{ money($transactionSummary['invoice_total'], setting('default.currency', 'USD')) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">{{ trans('projects::general.bill_total') }}</span>
                                <span class="font-medium">{{ money($transactionSummary['bill_total'], setting('default.currency', 'USD')) }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                                <span class="text-gray-500">{{ trans('projects::general.actual_costs') }}</span>
                                <span class="font-medium">{{ money($report['summary']['costs'], setting('default.currency', 'USD')) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.team_members') }}</h3>
                        </div>
                        <form method="POST" action="{{ route('projects.projects.members.update', $project->id) }}" class="mt-4 space-y-3">
                            @csrf
                            @foreach ($users as $id => $name)
                                <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2">
                                    <span class="flex items-center gap-3 text-sm">
                                        <input type="checkbox" name="member_ids[]" value="{{ $id }}" {{ array_key_exists($id, $project->members->pluck('role', 'user_id')->toArray()) ? 'checked' : '' }} />
                                        {{ $name }}
                                    </span>
                                    <select name="member_roles[{{ $id }}]" class="rounded-lg border-gray-300 text-sm">
                                        @foreach ($memberRoles as $role => $label)
                                            <option value="{{ $role }}" {{ ($project->members->pluck('role', 'user_id')->toArray()[$id] ?? 'member') === $role ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endforeach
                            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
                        </form>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.activity_feed') }}</h3>
                        <div class="mt-4 space-y-4">
                            @forelse ($project->activities->take(8) as $activity)
                                <div class="border-l-2 border-slate-200 pl-4">
                                    <div class="text-sm text-slate-900">{{ $activity->description }}</div>
                                    <div class="text-xs text-gray-500">{{ $activity->created_at?->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($tab === 'tasks')
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                @foreach ($taskColumns as $columnKey => $tasks)
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-semibold text-slate-900">{{ $taskStatuses[$columnKey] }}</h3>
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs">{{ $tasks->count() }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($tasks as $task)
                                <div class="rounded-xl border border-gray-100 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $task->name }}</div>
                                            <div class="mt-1 text-xs text-gray-500">{{ $task->milestone?->name ?? trans('projects::general.no_milestone') }}</div>
                                        </div>
                                        <span class="rounded-full bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700">{{ $task->priority_label }}</span>
                                    </div>
                                    <div class="mt-3 text-sm text-gray-600">{{ $task->description ?: trans('projects::general.empty_state') }}</div>
                                    <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                                        <span>{{ $task->assignee?->name ?? trans('general.na') }}</span>
                                        <span>{{ $task->estimated_hours ? number_format($task->estimated_hours, 2) . 'h' : '-' }}</span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        {{ trans('projects::general.tracked_hours') }}: {{ number_format((float) ($task->timesheets_sum_hours ?? 0), 2) }}h
                                    </div>
                                    @if ($activeTimerMap->has($task->id))
                                        <div class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">
                                            {{ trans('projects::general.running_timer') }}:
                                            {{ $activeTimerMap[$task->id]->user?->name ?? trans('general.na') }}
                                            · {{ number_format($activeTimerMap[$task->id]->tracked_hours, 2) }}h
                                        </div>
                                    @endif
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="{{ route('projects.tasks.edit', $task->id) }}" class="text-xs font-medium text-blue-700 hover:underline">{{ trans('general.edit') }}</a>
                                        @if (($activeTimerMap[$task->id]->user_id ?? null) === auth()->id())
                                            <form method="POST" action="{{ route('projects.projects.tasks.timer.stop', [$project->id, $task->id]) }}">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium text-rose-700 hover:underline">{{ trans('projects::general.stop_timer') }}</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('projects.projects.tasks.timer.start', [$project->id, $task->id]) }}">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium text-emerald-700 hover:underline">{{ trans('projects::general.start_timer') }}</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('projects.projects.tasks.transition', [$project->id, $task->id]) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $task->status === 'done' ? 'todo' : 'done' }}" />
                                            <button type="submit" class="text-xs font-medium text-emerald-700 hover:underline">
                                                {{ $task->status === 'done' ? trans('projects::general.task_statuses.todo') : trans('projects::general.task_statuses.done') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('projects.tasks.destroy', $task->id) }}" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-red-600 hover:underline">{{ trans('general.delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.new_task') }}</h3>
                <form method="POST" action="{{ route('projects.projects.tasks.store', $project->id) }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.milestone') }}</label>
                        <select name="milestone_id" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($milestonesForSelect as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.assignee') }}</label>
                        <select name="assignee_id" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">{{ trans('general.none') }}</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.priority') }}</label>
                        <select name="priority" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($priorities as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($taskStatuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.estimated_hours') }}</label>
                        <input type="number" name="estimated_hours" step="0.0001" min="0" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.description') }}</label>
                        <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
                    </div>
                </form>
            </div>
        @elseif ($tab === 'timesheets')
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.timesheets') }}</h3>
                        <div class="text-sm text-gray-500">{{ number_format($report['summary']['tracked_hours'], 2) }}h</div>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left text-gray-500">
                                    <th class="px-3 py-2">{{ trans('projects::general.logged_by') }}</th>
                                    <th class="px-3 py-2">{{ trans('projects::general.task') }}</th>
                                    <th class="px-3 py-2">{{ trans('projects::general.hours') }}</th>
                                    <th class="px-3 py-2">{{ trans('projects::general.date') }}</th>
                                    <th class="px-3 py-2">{{ trans('projects::general.billable') }}</th>
                                    <th class="px-3 py-2">{{ trans('general.description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($timesheets as $timesheet)
                                    <tr class="border-b border-gray-100">
                                        <td class="px-3 py-3">{{ $timesheet->user?->name ?? trans('general.na') }}</td>
                                        <td class="px-3 py-3 font-medium text-slate-900">{{ $timesheet->task?->name ?? trans('general.na') }}</td>
                                        <td class="px-3 py-3">{{ number_format($timesheet->tracked_hours, 2) }}</td>
                                        <td class="px-3 py-3">{{ $timesheet->started_at?->format('M d, Y') ?? '-' }}</td>
                                        <td class="px-3 py-3">{{ $timesheet->billable ? trans('projects::general.billable') : trans('projects::general.non_billable') }}</td>
                                        <td class="px-3 py-3 text-gray-500">{{ $timesheet->description ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ trans('projects::general.empty_state') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.manual_timesheet_entry') }}</h3>
                        <form method="POST" action="{{ route('projects.projects.timesheets.store', $project->id) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.task') }}</label>
                                <select name="task_id" class="w-full rounded-lg border-gray-300 text-sm" required>
                                    <option value="">{{ trans('general.select') }}</option>
                                    @foreach ($project->tasks as $task)
                                        <option value="{{ $task->id }}">{{ $task->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.work_date') }}</label>
                                <input type="date" name="work_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border-gray-300 text-sm" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.hours') }}</label>
                                <input type="number" name="hours" step="0.01" min="0.01" class="w-full rounded-lg border-gray-300 text-sm" required />
                            </div>
                            <label class="flex items-center gap-3 rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="billable" value="1" checked />
                                {{ trans('projects::general.billable') }}
                            </label>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.description') }}</label>
                                <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
                        </form>
                    </div>

                    @if ($currentUserActiveTimer)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-emerald-900">{{ trans('projects::general.running_timer') }}</h3>
                            <div class="mt-2 text-sm text-emerald-700">{{ $currentUserActiveTimer->task?->name ?? trans('general.na') }}</div>
                            <div class="mt-1 text-sm text-emerald-700">{{ number_format($currentUserActiveTimer->tracked_hours, 2) }}h</div>
                            <form method="POST" action="{{ route('projects.projects.tasks.timer.stop', [$project->id, $currentUserActiveTimer->task_id]) }}" class="mt-4">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-900 px-4 py-2 text-sm text-white">{{ trans('projects::general.stop_timer') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @elseif ($tab === 'budget')
            @include('projects::projects.partials.financial-dashboard', ['showReportLink' => true])
        @elseif ($tab === 'milestones')
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="space-y-6">
                    @forelse ($project->milestones as $milestone)
                        <div class="rounded-2xl border border-gray-100 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-lg font-semibold text-slate-900">{{ $milestone->name }}</h3>
                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $milestone->completed_at ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $milestone->completed_at ? trans('projects::general.statuses.completed') : trans('projects::general.statuses.active') }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">{{ $milestone->description ?: trans('projects::general.empty_state') }}</p>
                                    <div class="mt-2 text-xs text-gray-500">{{ trans('projects::general.target_date') }}: {{ $milestone->target_date?->format('M d, Y') ?? '-' }}</div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('projects.milestones.edit', $milestone->id) }}" class="text-sm font-medium text-blue-700 hover:underline">{{ trans('general.edit') }}</a>
                                    <form method="POST" action="{{ route('projects.projects.milestones.complete', [$project->id, $milestone->id]) }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-emerald-700 hover:underline">{{ $milestone->completed_at ? trans('projects::general.statuses.active') : trans('projects::general.statuses.completed') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.milestones.destroy', $milestone->id) }}" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">{{ trans('general.delete') }}</button>
                                    </form>
                                </div>
                            </div>
                            @if ($milestone->tasks->isNotEmpty())
                                <div class="mt-4 rounded-xl bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ trans('projects::general.tasks') }}</div>
                                    <div class="mt-3 space-y-2">
                                        @foreach ($milestone->tasks as $task)
                                            <div class="flex items-center justify-between text-sm">
                                                <span>{{ $task->name }}</span>
                                                <span class="text-gray-500">{{ $task->status_label }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.new_milestone') }}</h3>
                <form method="POST" action="{{ route('projects.projects.milestones.store', $project->id) }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.name') }}</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('projects::general.target_date') }}</label>
                        <input type="date" name="target_date" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.description') }}</label>
                        <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
                    </div>
                </form>
            </div>
        @elseif ($tab === 'transactions')
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.transactions') }}</h3>
                    <x-link href="{{ route('projects.projects.transactions.create', $project->id) }}" kind="primary">
                        {{ trans('projects::general.link_document') }}
                    </x-link>
                </div>
                <div class="space-y-3">
                    @forelse ($project->transactions as $transaction)
                        <div class="flex flex-col gap-3 rounded-xl border border-gray-100 p-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-medium text-slate-900">
                                    {{ $transaction->document_type_label }} {{ $transaction->document?->document_number ?: '#' . $transaction->document_id }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $transaction->document?->contact_name ?: trans('general.na') }} ·
                                    {{ $transaction->document ? money($transaction->document->amount, $transaction->document->currency_code ?: setting('default.currency', 'USD')) : '-' }}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('projects.projects.transactions.destroy', [$project->id, $transaction->id]) }}" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:underline">{{ trans('general.delete') }}</button>
                            </form>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                    @endforelse
                </div>
            </div>
        @elseif ($tab === 'discussions')
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.discussions') }}</h3>

                <form method="POST" action="{{ route('projects.projects.discussions.store', $project->id) }}" class="mt-4">
                    @csrf
                    <textarea name="body" rows="4" class="w-full rounded-lg border-gray-300 text-sm" placeholder="{{ trans('projects::general.new_discussion') }}"></textarea>
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">{{ trans('general.save') }}</button>
                    </div>
                </form>

                <div class="mt-6 space-y-6">
                    @forelse ($project->discussions as $discussion)
                        <div class="rounded-2xl border border-gray-100 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $discussion->user?->name ?? trans('general.na') }}</div>
                                    <div class="text-xs text-gray-500">{{ $discussion->created_at?->diffForHumans() }}</div>
                                </div>
                                <form method="POST" action="{{ route('projects.discussions.destroy', $discussion->id) }}" onsubmit="return confirm('{{ trans('general.delete_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:underline">{{ trans('general.delete') }}</button>
                                </form>
                            </div>
                            <p class="mt-3 whitespace-pre-wrap text-sm text-gray-700">{{ $discussion->body }}</p>

                            <form method="POST" action="{{ route('projects.projects.discussions.store', $project->id) }}" class="mt-4 rounded-xl bg-slate-50 p-4">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $discussion->id }}" />
                                <textarea name="body" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="{{ trans('projects::general.reply') }}"></textarea>
                                <div class="mt-3 flex justify-end">
                                    <button type="submit" class="rounded-lg bg-white px-4 py-2 text-sm text-slate-900 shadow-sm">{{ trans('projects::general.reply') }}</button>
                                </div>
                            </form>

                            @if ($discussion->replies->isNotEmpty())
                                <div class="mt-4 space-y-3 border-l border-gray-200 pl-4">
                                    @foreach ($discussion->replies as $reply)
                                        <div class="rounded-xl bg-slate-50 p-4">
                                            <div class="font-medium text-slate-900">{{ $reply->user?->name ?? trans('general.na') }}</div>
                                            <div class="text-xs text-gray-500">{{ $reply->created_at?->diffForHumans() }}</div>
                                            <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $reply->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="space-y-4">
                    @forelse ($project->activities as $activity)
                        <div class="border-l-2 border-slate-200 pl-4">
                            <div class="text-sm text-slate-900">{{ $activity->description }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->created_at?->format('M d, Y H:i') }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">{{ trans('projects::general.empty_state') }}</div>
                    @endforelse
                </div>
            </div>
        @endif
    </x-slot>
</x-layouts.admin>
