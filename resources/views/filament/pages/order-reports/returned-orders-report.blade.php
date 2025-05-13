<x-filament::page>
    <style>
        @media print {
            #printReport {
                display: none;
            }
        }

        @media print {
            #printReport {
                display: none;
            }

            .fixed-header {
                position: static !important;
                top: auto !important;
            }
        }
    </style>
    {{ $this->getTableFiltersForm() }}
    @if ($reportData)
        <div class="flex justify-end mb-4">

            <button id="printReport"
                class="px-6 py-2 ml-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
                🖨️ {{ __('Print') }}
            </button>
        </div>
        <div id="reportContent">
            <x-filament-tables::table class="w-full text-sm text-left pretty reports" id="report-table">
                <thead class="fixed-header" style="top:64px;">
                    {{-- رأس التقرير --}}
                    <x-filament-tables::row class="header_report">
                        <th colspan="3"
                            class="{{ app()->getLocale() == 'en' ? 'no_border_right' : 'no_border_left' }}">

                            <p>{{ __('lang.start_date') . ': ' . ($start_date ?? __('lang.not_specified')) }}</p>
                            <p>{{ __('lang.end_date') . ': ' . ($end_date ?? __('lang.not_specified')) }}</p>
                        </th>
                        <th colspan="3" class="no_border_right_left text-center">
                            <p>{{ __('Returned Orders Report') }}</p>
                        </th>
                        <th colspan="3"
                            class="text-center {{ app()->getLocale() == 'en' ? 'no_border_left' : 'no_border_right' }}">
                            <img class="circle-image" src="{{ url('/storage/workbench.png') }}" alt=""
                                height="50" width="50">
                        </th>
                    </x-filament-tables::row>

                    {{-- عناوين الأعمدة --}}
                    <x-filament-tables::row
                        class="bg-gray-100 text-gray-700 text-sm font-semibold text-center uppercase">
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Store</th>
                        <th>Created By</th>
                        <th>Approved By</th>
                        <th>Items Count</th>
                        <th></th>
                    </x-filament-tables::row>
                </thead>
                <tbody>
                    @foreach ($reportData as $row)
                        <x-filament-tables::row>
                            <x-filament-tables::cell>{{ $row['id'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $row['date'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $row['branch'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $row['store'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $row['created_by'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $row['approved_by'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>{{ $row['items_count'] }}</x-filament-tables::cell>
                            <x-filament-tables::cell>

                                <x-filament::link :href="route(
                                    'filament.admin.order-reports.resources.returned-order-reports.details',
                                    [
                                        'id' => $row['id'],
                                    ],
                                )" badge-color="purple" color="primary"
                                    icon="heroicon-o-magnifying-glass" icon-position="before"
                                    tooltip="Go to Details Page">
                                    {{ __('View Details') }}
                                    {{-- <x-slot name="badge">
                                    {{ $row['items_count'] }}
                                </x-slot> --}}
                                </x-filament::link>
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                    @endforeach

                </tbody>
            </x-filament-tables::table>
        </div>
    @else
        <div class="text-center py-10">
            <h1 class="text-lg text-gray-500">{{ __('No Data') }}</h1>
        </div>
    @endif

    {{-- Print Script --}}
    <script>
        document.getElementById("printReport").addEventListener("click", function() {

            const originalContent = document.body.innerHTML;
            const reportContent = document.getElementById("reportContent").innerHTML;
            document.body.innerHTML = reportContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>


</x-filament::page>
