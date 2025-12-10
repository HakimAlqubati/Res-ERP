<x-filament-panels::page>
    {{-- Header Section --}}
    <x-filament::section>
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                @php
                $levelColors = [
                'critical' => 'danger',
                'warning' => 'warning',
                'info' => 'info',
                ];
                $levelColor = $levelColors[$notification['level']] ?? 'gray';

                $levelIcons = [
                'critical' => 'heroicon-o-x-circle',
                'warning' => 'heroicon-o-exclamation-triangle',
                'info' => 'heroicon-o-information-circle',
                ];
                $levelIcon = $levelIcons[$notification['level']] ?? 'heroicon-o-bell';
                @endphp

                <x-filament::icon
                    :icon="$levelIcon"
                    class="h-8 w-8 text-{{ $levelColor }}-500" />

                <div>
                    <h2 class="text-xl font-bold">{{ $notification['title'] }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($notification['created_at'])->diffForHumans() }}
                        @if($notification['read_at'])
                        · <span class="text-success-500">{{ __('Read') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <x-filament::badge :color="$levelColor" size="lg">
                {{ ucfirst($notification['level']) }}
            </x-filament::badge>
        </div>

        @if($notification['detail'])
        <div class="mt-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
            <p class="text-gray-700 dark:text-gray-300">{{ $notification['detail'] }}</p>
        </div>
        @endif
    </x-filament::section>

    {{-- Dynamic Content Based on Type --}}
    @if($notificationType === 'missed_checkin' && !empty($context['employees']))
    {{-- Missed Check-in Details --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-users" class="h-5 w-5" />
                {{ __('Employees Details') }}
                <x-filament::badge color="danger">{{ count($context['employees']) }}</x-filament::badge>
            </div>
        </x-slot>

        <div class="space-y-4">
            @foreach($context['employees'] as $empData)
            @php
            $emp = $empData['employee'] ?? [];
            $periods = $empData['periods'] ?? [];
            @endphp

            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <x-filament::icon icon="heroicon-o-user" class="h-5 w-5 text-primary-600" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $emp['name'] ?? 'N/A' }}</h4>
                            <p class="text-sm text-gray-500">
                                {{ $emp['employee_no'] ?? '' }}
                                @if(!empty($emp['branch']))
                                · {{ $emp['branch'] }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <x-filament::badge color="warning">
                        {{ count($periods) }} {{ __('Period(s)') }}
                    </x-filament::badge>
                </div>

                @if(!empty($periods))
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2 text-start font-medium text-gray-600 dark:text-gray-400">{{ __('Period') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-gray-600 dark:text-gray-400">{{ __('Shift Start') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-gray-600 dark:text-gray-400">{{ __('Grace Deadline') }}</th>
                                <th class="px-3 py-2 text-start font-medium text-gray-600 dark:text-gray-400">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($periods as $period)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-3 py-2 font-medium">{{ $period['period_label'] ?? 'N/A' }}</td>
                                <td class="px-3 py-2">{{ $period['shift_start'] ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $period['grace_deadline'] ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    @if(($period['on_leave'] ?? false))
                                    <x-filament::badge color="info">{{ __('On Leave') }}</x-filament::badge>
                                    @else
                                    <x-filament::badge color="danger">{{ __('No Check-in') }}</x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </x-filament::section>

    @elseif($notificationType === 'low_stock')
    {{-- Low Stock Details --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
                {{ __('Low Stock Details') }}
            </div>
        </x-slot>

        @if(!empty($context['store_id']))
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            <strong>{{ __('Store ID') }}:</strong> {{ $context['store_id'] }}
        </p>
        @endif

        @if(!empty($context['products']))
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-start">{{ __('Product') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Current Stock') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Minimum') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($context['products'] as $product)
                    <tr>
                        <td class="px-3 py-2">{{ $product['name'] ?? $product['id'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2">{{ $product['remaining'] ?? $product['current'] ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $product['min'] ?? $product['minimum'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </x-filament::section>

    @else
    {{-- Generic/Unknown Type - Show Raw Context --}}
    @if(!empty($context))
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-code-bracket" class="h-5 w-5" />
                {{ __('Additional Details') }}
            </div>
        </x-slot>

        <div class="p-4 rounded-lg bg-gray-900 text-gray-100 overflow-x-auto">
            <pre class="text-xs"><code>{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
        </div>
    </x-filament::section>
    @endif
    @endif

    {{-- Footer Actions --}}
    <div class="flex justify-end gap-3 mt-6">
        <x-filament::button
            color="gray"
            tag="a"
            href="{{ url()->previous() }}">
            {{ __('Back') }}
        </x-filament::button>
    </div>
</x-filament-panels::page>