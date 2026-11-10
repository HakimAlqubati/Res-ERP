<x-filament-panels::page>
    @php
        $data = $this->getDashboardData();
        $items = $data['items'];
    @endphp

    <div wire:ignore>
        <style>
            .quick-link {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 168px;
                height: 168px;
                gap: .5rem;
                text-align: center;
                border-radius: 1rem;
                background-color: #0d7c66;
                color: #fff !important;
                transition: all .2s ease;
                border: 1px solid transparent;
                text-decoration: none !important;
                box-sizing: border-box;
            }
            .quick-link:hover {
                transform: translateY(-4px);
                border-color: #0d7c66;
                color: #0d7c66 !important;
                background-color: #fff;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }
            .quick-link svg {
                width: 4.5rem !important;
                height: 4.5rem !important;
                max-width: 4.5rem !important;
                max-height: 4.5rem !important;
                color: #ffffff !important;
                transition: color .2s ease;
                flex-shrink: 0;
                display: block;
            }
            .quick-link:hover svg {
                color: #0d7c66 !important;
            }
            .quick-link .label {
                font-size: 1.05rem;
                font-weight: 700;
                line-height: 1.25;
                color: inherit;
                padding: 0 0.5rem;
            }
            .quick-link .badge {
                font-weight: 700;
                font-size: 0.9rem;
                color: inherit;
            }

            .dark .quick-link {
                background-color: #0d7c66;
                color: #fff !important;
                border-color: rgba(255, 255, 255, .25);
            }
            .dark .quick-link:hover {
                background-color: rgba(255, 255, 255, .08);
                color: #0d7c66 !important;
                border-color: #0d7c66;
            }
            .dark .quick-link svg {
                color: #fff !important;
            }
            .dark .quick-link:hover svg {
                color: #0d7c66 !important;
            }

            .tile-grid {
                display: grid;
                gap: 1.25rem;
                justify-content: center;
                padding: 0.5rem 0;
            }

            .auto-grid {
                grid-template-columns: repeat(auto-fit, minmax(168px, 1fr));
            }

            @media (min-width: 1024px) {
                .grid-5 {
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                }
            }
        </style>

        <x-filament::card>
            <x-filament::fieldset label="{{ __('lang.requests') }}">
                <div class="tile-grid auto-grid grid-5">
                    @foreach($items as $item)
                        @php
                            $typeId = $item['type_id'];
                        @endphp
                        <a href="{{ $item['url'] }}" class="quick-link" title="{{ $item['name'] }}">
                            @if($typeId == \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST)
                                <x-heroicon-o-finger-print />
                            @elseif($typeId == \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST)
                                <x-heroicon-m-finger-print />
                            @elseif($typeId == \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST)
                                <x-heroicon-m-banknotes />
                            @elseif($typeId == \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)
                                <x-heroicon-o-calendar-days />
                            @elseif($typeId == \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST)
                                <x-heroicon-o-fire />
                            @else
                                <x-heroicon-o-document-text />
                            @endif

                            <div class="label">{{ $item['name'] }}</div>
                            <div class="badge">
                                @if($item['pending'] > 0)
                                    {{ $item['pending'] }}
                                @else
                                    {{ $item['total'] }}
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-filament::fieldset>
        </x-filament::card>
    </div>
</x-filament-panels::page>
