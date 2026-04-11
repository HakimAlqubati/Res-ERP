@php
    use App\Services\HR\EmployeeBranchTransferService;

    $employee    = $getRecord();
    $newBranchId = $getState()['branch_id'] ?? null;
    $startAt     = $getState()['start_at'] ?? now()->toDateString();

    if (! $newBranchId) {
        $preview = null;
    } else {
        $preview = app(EmployeeBranchTransferService::class)->preview(
            employee:     $employee,
            newBranchId:  (int) $newBranchId,
            transferDate: $startAt,
        );
    }
@endphp

@if(! $preview)
    <div class="rounded-xl border border-dashed border-warning-400 bg-warning-50 dark:bg-warning-500/10 p-5 text-center">
        <p class="text-sm font-medium text-warning-700 dark:text-warning-400">
            {{ __('lang.select_branch_and_date_to_preview') }}
        </p>
    </div>
@else
    {{-- ─── Header ─── --}}
    <div class="mb-5 rounded-xl border border-primary-200 bg-gradient-to-r from-primary-50 to-primary-100/50 p-5 dark:border-primary-800 dark:from-primary-950/50 dark:to-primary-900/30">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-500 text-white shadow">
                @svg('heroicon-o-arrow-path-rounded-square', 'w-5 h-5')
            </div>
            <div>
                <h3 class="text-base font-bold text-primary-900 dark:text-primary-100">
                    {{ __('lang.transfer_preview_title') }}
                </h3>
                <p class="text-xs text-primary-600 dark:text-primary-400">
                    {{ $employee->name }}
                    &rarr;
                    <span class="font-semibold">{{ $preview['newBranch']?->name }}</span>
                    &bull;
                    {{ \Carbon\Carbon::parse($startAt)->format('Y-m-d') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ─── Operations List ─── --}}
    <div class="space-y-3">
        @foreach($preview['operations'] as $op)
            @php
                $colorMap = [
                    'warning' => ['border' => 'border-warning-300 dark:border-warning-700', 'bg' => 'bg-warning-50 dark:bg-warning-500/10', 'icon_bg' => 'bg-warning-100 dark:bg-warning-500/20', 'title' => 'text-warning-800 dark:text-warning-300', 'body' => 'text-warning-700 dark:text-warning-400', 'icon_color' => 'text-warning-600 dark:text-warning-400'],
                    'info'    => ['border' => 'border-info-300 dark:border-info-700', 'bg' => 'bg-info-50 dark:bg-info-500/10', 'icon_bg' => 'bg-info-100 dark:bg-info-500/20', 'title' => 'text-info-800 dark:text-info-300', 'body' => 'text-info-700 dark:text-info-400', 'icon_color' => 'text-info-600 dark:text-info-400'],
                    'success' => ['border' => 'border-success-300 dark:border-success-700', 'bg' => 'bg-success-50 dark:bg-success-500/10', 'icon_bg' => 'bg-success-100 dark:bg-success-500/20', 'title' => 'text-success-800 dark:text-success-300', 'body' => 'text-success-700 dark:text-success-400', 'icon_color' => 'text-success-600 dark:text-success-400'],
                    'danger'  => ['border' => 'border-danger-300 dark:border-danger-700', 'bg' => 'bg-danger-50 dark:bg-danger-500/10', 'icon_bg' => 'bg-danger-100 dark:bg-danger-500/20', 'title' => 'text-danger-800 dark:text-danger-300', 'body' => 'text-danger-700 dark:text-danger-400', 'icon_color' => 'text-danger-600 dark:text-danger-400'],
                ];
                $iconMap = [
                    'warning' => 'heroicon-o-exclamation-triangle',
                    'info'    => 'heroicon-o-information-circle',
                    'success' => 'heroicon-o-check-circle',
                    'danger'  => 'heroicon-o-x-circle',
                ];
                $c = $colorMap[$op['type']] ?? $colorMap['info'];
                $icon = $iconMap[$op['type']] ?? 'heroicon-o-information-circle';
            @endphp
            <div class="flex items-start gap-3 rounded-xl border {{ $c['border'] }} {{ $c['bg'] }} p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $c['icon_bg'] }} {{ $c['icon_color'] }}">
                    @svg($icon, 'w-4.5 h-4.5')
                </div>
                <div>
                    <p class="text-sm font-semibold {{ $c['title'] }}">{{ $op['title'] }}</p>
                    @if(! empty($op['body']))
                        <p class="mt-0.5 text-xs leading-relaxed {{ $c['body'] }}">{{ $op['body'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─── Active Period Histories detail ─── --}}
    @if($preview['activePeriodHistories']->isNotEmpty())
        <div class="mt-4 rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('lang.shifts_to_be_closed') }}
            </div>
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 dark:border-white/5 text-xs text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2 text-start font-medium">{{ __('lang.shift') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('lang.start_date') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('lang.end_date') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('lang.new_end_date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($preview['activePeriodHistories'] as $history)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-white">
                                {{ $history->workPeriod?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
                                {{ $history->start_date ? \Carbon\Carbon::parse($history->start_date)->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-4 py-2.5 text-gray-400 dark:text-gray-500 italic text-xs">
                                {{ $history->end_date ? \Carbon\Carbon::parse($history->end_date)->format('Y-m-d') : __('lang.open') }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-400">
                                    {{ \Carbon\Carbon::parse($startAt)->subDay()->format('Y-m-d') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
