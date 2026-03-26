<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold mb-4">{{ trans('employees::general.employee_summary') }}</h3>

    {{-- Total Headcount --}}
    <div class="mb-6">
        <div class="text-3xl font-bold text-purple-700">{{ $totalHeadcount }}</div>
        <div class="text-sm text-gray-500">{{ trans('employees::general.total_headcount') }}</div>
    </div>

    {{-- By Department --}}
    @if ($byDepartment->isNotEmpty())
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ trans('employees::general.by_department') }}</h4>
            <div class="space-y-2">
                @foreach ($byDepartment as $dept)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ $dept['name'] }}</span>
                        <span class="text-sm font-medium bg-gray-100 px-2 py-1 rounded">{{ $dept['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- By Type --}}
    @if ($byType->isNotEmpty())
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ trans('employees::general.by_type') }}</h4>
            <div class="space-y-2">
                @foreach ($byType as $type => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ trans('employees::general.types.' . $type) }}</span>
                        <span class="text-sm font-medium bg-gray-100 px-2 py-1 rounded">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent Hires --}}
    @if ($recentHires->isNotEmpty())
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ trans('employees::general.recent_hires') }}</h4>
            <div class="space-y-2">
                @foreach ($recentHires as $hire)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ $hire->name }}</span>
                        <span class="text-xs text-gray-400">{{ $hire->hire_date?->format('M d') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
