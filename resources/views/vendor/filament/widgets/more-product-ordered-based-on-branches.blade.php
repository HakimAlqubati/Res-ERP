@php
    $heading = $this->getHeading();
    $filters = $this->getFilters();
@endphp

{{-- <x-filament::widget class="filament-widgets-chart-widget" style="direction: rtl"> --}}
<x-filament::widget class="filament-widgets-chart-widget">
    <x-filament::card>
        @if ($heading || $filters)
            <div class="flex items-center justify-between gap-8">
                @if ($heading)
                    <filament::card.heading>
                        {{ $heading }}
                    </filament::card.heading>
                @endif

                <div>

                    <div>
                        <p> {{ __('lang.choose_branch') }} </p>
                        <select wire:model="branchid" @class([
                            'block h-10 rounded-lg border-gray-300 text-gray-900 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500',
                            'dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:focus:border-primary-500' => config(
                                'filament.dark_mode'),
                        ]) wire:loading.class="animate-pulse">
                            @foreach ($branches as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <p> {{ __('lang.choose_month') }} </p>
                        <select wire:model="month" @class([
                            'text-gray-900 border-gray-300 block h-10 transition duration-75 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500',
                            'dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:focus:border-primary-500' => config(
                                'filament.dark_mode'),
                        ])>
                            <option value="0"> {{ __('lang.all') }} </option>
                            <option value="1">{{ __('lang.month_1') }}</option>
                            <option value="2">{{ __('lang.month_2') }}</option>
                            <option value="3">{{ __('lang.month_3') }}</option>
                            <option value="4">{{ __('lang.month_4') }}</option>
                            <option value="5">{{ __('lang.month_5') }}</option>
                            <option value="6">{{ __('lang.month_6') }}</option>
                            <option value="7">{{ __('lang.month_7') }}</option>
                            <option value="8">{{ __('lang.month_8') }}</option>
                            <option value="9">{{ __('lang.month_9') }}</option>
                            <option value="10">{{ __('lang.month_10') }}</option>
                            <option value="11">{{ __('lang.month_11') }}</option>
                            <option value="12">{{ __('lang.month_12') }}</option>
                        </select>
                    </div>

                </div>
                <div>
                    <div>
                        <p> {{ __('lang.choose_year') }} </p>
                        <select wire:model="yearid" @class([
                            'text-gray-900 border-gray-300 block h-10 transition duration-75 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500',
                            'dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:focus:border-primary-500' => config(
                                'filament.dark_mode'),
                        ])>

                            <option value="0000"> {{ __('lang.all') }} </option>
                            <option value="2022">2022</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>


                    <div>
                        <p> {{__('lang.products_chart')}} </p>
                        <select wire:model="productscount" @class([
                            'text-gray-900 border-gray-300 block h-10 transition duration-75 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500',
                            'dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:focus:border-primary-500' => config(
                                'filament.dark_mode'),
                        ])>
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                            <option value="25">25</option>
                        </select>
                    </div>
                </div>
            </div>

            <filament::hr />
        @endif

        <div {!! ($pollingInterval = $this->getPollingInterval()) ? "wire:poll.{$pollingInterval}=\"updateChartData\"" : '' !!}>
            <canvas x-data="{
                chart: null,
            
                init: function() {
                    let chart = this.initChart()
            
                    $wire.on('updateChartData', async ({ data }) => {
                        chart.data = this.applyColorToData(data)
                        chart.update('resize')
                    })
            
                    $wire.on('filterChartData', async ({ data }) => {
                        chart.destroy()
                        chart = this.initChart(data)
                    })
                },
            
                initChart: function(data = null) {
                    data = data ?? {{ json_encode($this->getCachedData()) }}
            
                    return (this.chart = new Chart($el, {
                        type: '{{ $this->getType() }}',
                        data: this.applyColorToData(data),
                        options: {{ json_encode($this->getOptions()) }} ?? {},
                    }))
                },
            
                applyColorToData: function(data) {
                    data.datasets.forEach((dataset, datasetIndex) => {
                        if (!dataset.backgroundColor) {
                            data.datasets[datasetIndex].backgroundColor = getComputedStyle(
                                $refs.backgroundColorElement,
                            ).color
                        }
            
                        if (!dataset.borderColor) {
                            data.datasets[datasetIndex].borderColor = getComputedStyle(
                                $refs.borderColorElement,
                            ).color
                        }
                    })
            
                    return data
                },
            }" wire:ignore @if ($maxHeight = $this->getMaxHeight())
                style="max-height: {{ $maxHeight }}"
                @endif
                >
                <span x-ref="backgroundColorElement" @class([
                    'text-gray-50',
                    'dark:text-gray-300' => config('filament.dark_mode'),
                ])></span>

                <span x-ref="borderColorElement" @class([
                    'text-gray-500',
                    'dark:text-gray-200' => config('filament.dark_mode'),
                ])></span>
            </canvas>
        </div>
    </x-filament::card>
</x-filament::widget>
<style>
    select {
        min-width: 180px !important;
    }
</style>
