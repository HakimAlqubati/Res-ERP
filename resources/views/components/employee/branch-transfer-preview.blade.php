@php
    use App\Services\HR\EmployeeBranchTransferService;

    $employee    = $getRecord();
    $newBranchId = $get('branch_id') ?? null;
    $startAt     = $get('start_at') ?? now()->toDateString();

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
    <div style="border: 2px border-style: dashed; border-color: #f59e0b; background-color: rgba(245, 158, 11, 0.05); padding: 20px; text-align: center; border-radius: 12px;">
        <p style="font-size: 0.875rem; color: #b45309;">
            {{ __('lang.select_branch_and_date_to_preview') }}
        </p>
    </div>
@else
    {{-- ─── Header ─── --}}
    <div style="margin-bottom: 20px; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; background: linear-gradient(to right, #f8fafc, #f1f5f9);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="display: flex; width: 40px; height: 40px; align-items: center; justify-content: center; background-color: #3b82f6; border-radius: 50%; color: white;">
                <x-heroicon-o-arrow-path-rounded-square style="width: 20px; height: 20px;" />
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: #1e293b;">
                    {{ __('lang.transfer_preview_title') }}
                </h3>
                <p style="margin: 0; font-size: 0.75rem; color: #64748b;">
                    {{ $employee->name }} &rarr; <strong style="color: #334155;">{{ $preview['newBranch']?->name }}</strong> &bull; {{ $startAt }}
                </p>
            </div>
        </div>
    </div>

    {{-- ─── Operations List ─── --}}
    <div style="display: flex; flex-direction: column; gap: 12px;">
        @foreach($preview['operations'] as $op)
            @php
                $colors = match($op['type']) {
                    'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'icon_bg' => '#fef3c7', 'icon' => '#d97706', 'text' => '#92400e'],
                    'danger'  => ['bg' => '#fef2f2', 'border' => '#fecaca', 'icon_bg' => '#fee2e2', 'icon' => '#dc2626', 'text' => '#991b1b'],
                    'success' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'icon_bg' => '#dcfce7', 'icon' => '#16a34a', 'text' => '#166534'],
                    default   => ['bg' => '#f0f9ff', 'border' => '#bae6fd', 'icon_bg' => '#e0f2fe', 'icon' => '#0284c7', 'text' => '#075985'],
                };
                $icon = match($op['type']) {
                    'warning' => 'heroicon-o-exclamation-triangle',
                    'danger'  => 'heroicon-o-x-circle',
                    'success' => 'heroicon-o-check-circle',
                    default   => 'heroicon-o-information-circle',
                };
            @endphp
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; border-radius: 12px; border: 1px solid {{ $colors['border'] }}; background-color: {{ $colors['bg'] }};">
                <div style="display: flex; flex-shrink: 0; width: 32px; height: 32px; align-items: center; justify-content: center; border-radius: 50%; background-color: {{ $colors['icon_bg'] }}; color: {{ $colors['icon'] }};">
                    @svg($icon, ['style' => 'width: 18px; height: 18px;'])
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: {{ $colors['text'] }};">{{ $op['title'] }}</h4>
                    @if(!empty($op['body']))
                        <p style="margin: 4px 0 0 0; font-size: 0.75rem; color: {{ $colors['text'] }}; opacity: 0.8;">{{ $op['body'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─── Table ─── --}}
    @if($preview['activePeriodHistories']->isNotEmpty())
        <div style="margin-top: 20px; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
            <div style="background-color: #f8fafc; padding: 10px 16px; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">
                {{ __('lang.shifts_to_be_closed') }}
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #f1f5f9; text-align: left;">
                        <th style="padding: 8px 16px; color: #94a3b8; font-weight: 500;">{{ __('lang.shift') }}</th>
                        <th style="padding: 8px 16px; color: #94a3b8; font-weight: 500;">{{ __('lang.start_date') }}</th>
                        <th style="padding: 8px 16px; color: #94a3b8; font-weight: 500;">{{ __('lang.new_end_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['activePeriodHistories'] as $history)
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 16px; font-weight: 600;">{{ $history->workPeriod?->name }}</td>
                            <td style="padding: 10px 16px; color: #64748b;">{{ $history->start_date }}</td>
                            <td style="padding: 10px 16px;">
                                <span style="background-color: #fffbeb; color: #92400e; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                                    {{ \Carbon\Carbon::parse($startAt)->subDay()->toDateString() }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
