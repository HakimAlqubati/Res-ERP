@php
    use Filament\Support\Facades\FilamentView;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getFilters();

@endphp


<x-filament-widgets::widget class="fi-wi-chart">

 
    <x-filament::section :description="$description" :heading="$heading">
        <div class="flex flex-wrap gap-4 mb-6">
            {{-- Date From --}}
            <x-filament::input.wrapper>
                <label for="fromDate" class="sr-only" value="From Date">{{ 'From Date' }} </label>
                <x-filament::input type="date" id="fromDate" wire:model="fromDate" wire:change="updateChartData" />
            </x-filament::input.wrapper>

            {{-- Date To --}}
            <x-filament::input.wrapper>
                <label for="toDate" value="To Date" class="sr-only">
                    {{ ' To Date' }}
                </label>
                <x-filament::input type="date" id="toDate" wire:model="toDate" wire:change="updateChartData" />
            </x-filament::input.wrapper>

            {{-- Branch --}}
            <x-filament::input.wrapper>
                <label for="branchIds" value="Branches"> </label>
                <x-filament::input.select multiple id="branchIds" wire:model="branchIds" wire:change="updateChartData">
                    @foreach ($this->getBranchOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            {{-- Products --}}
            <x-filament::input.wrapper>
                <label for="productIds" value="Products"> </label>
                <x-filament::input.select multiple id="productIds" wire:model="productIds"
                    wire:change="updateChartData">
                    @foreach ($this->getProductOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            {{-- Categories --}}
            <x-filament::input.wrapper>
                <label for="categoryIds" value="Categories"> </label>
                <x-filament::input.select multiple id="categoryIds" wire:model="categoryIds"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm"
                    wire:change="updateChartData">
                    @foreach ($this->getCategoryOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>


        @if ($filters)
            <x-slot name="headerEnd">
                <x-filament::input.wrapper inline-prefix wire:target="filter" class="w-max sm:-my-2">
                    <x-filament::input.select inline-prefix wire:model.live="filter">
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-slot>
        @endif

        <div @if ($pollingInterval = $this->getPollingInterval()) wire:poll.{{ $pollingInterval }}="updateChartData" @endif>
            <div @if (FilamentView::hasSpaMode()) ax-load="visible"
                @else
                    ax-load @endif
                ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore x-data="chart({
                    cachedData: @js($this->getCachedData()),
                    options: @js($this->getOptions()),
                    type: @js($this->getType()),
                })" x-ignore @class([
                    match ($color) {
                        'gray' => null,
                        default => 'fi-color-custom',
                    },
                    is_string($color) ? "fi-color-{$color}" : null,
                ])>
                <canvas x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight()) style="max-height: {{ $maxHeight }}" @endif></canvas>

                <span x-ref="backgroundColorElement" @class([
                    match ($color) {
                        'gray' => 'text-gray-100 dark:text-gray-800',
                        default => 'text-custom-50 dark:text-custom-400/10',
                    },
                ]) @style([
                    \Filament\Support\get_color_css_variables($color, shades: [50, 400], alias: 'widgets::chart-widget.background') => $color !== 'gray',
                ])></span>

                <span x-ref="borderColorElement" @class([
                    match ($color) {
                        'gray' => 'text-gray-400',
                        default => 'text-custom-500 dark:text-custom-400',
                    },
                ]) @style([
                    \Filament\Support\get_color_css_variables($color, shades: [400, 500], alias: 'widgets::chart-widget.border') => $color !== 'gray',
                ])></span>

                <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>

                <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
