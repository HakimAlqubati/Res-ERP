<x-filament::page>
    <style>
        table {
            width: 100%;
            border-collapse: inherit;
            border-spacing: initial;
        }

        .fi-tabs {
            display: none !important;
        }

        @media print {
            #printReport {
                display: none;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>



    {{ $this->getTableFiltersForm() }}

    <div id="reportContent">
        @if (empty($store))
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">Please select a store.</h1>
        </div>
        @elseif (empty($reportData))
        <div class="please_select_message_div text-center">
            <h1 class="please_select_message_text">No halal label data found for store ({{ $store }}).</h1>
        </div>
        @else
        <table class="w-full text-sm text-left border reports table-striped" id="report-table">
            <thead class="fixed-header" style="top:64px;">
                <tr class="header_report">
                    <th class="no_border_right text-center">
                        <img src="{{ asset('/storage/' . setting('company_logo')) }}" alt="Company Logo"
                            class="logo-img-report circle-image">
                    </th>
                    <th colspan="4" class="no_border_right_left text-left">
                        <h3>Halal Label Report for ({{ $store }})</h3>
                        <h5>
                            Between {{ $startDate . ' & ' . $endDate }}
                        </h5>
                    </th>
                    <th colspan="2" class="no_border_left text-center">
                        <img class="circle-image" src="{{ asset('storage/workbench.png') }}" alt="">
                    </th>
                </tr>
                <tr class="bg-blue-50 text-xs text-gray-700">
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Batch</th>
                    <th class="px-4 py-2">Prod. Date</th>
                    <th class="px-4 py-2">Exp. Date</th>
                    <th class="px-4 py-2">Net Weight</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $row)
                <tr>
                    <td class="border px-4 py-1 text-center">{{ $row['product_name'] }}</td>
                    <td class="border px-4 py-1 text-center">{{ $row['patch_number'] }}</td>
                    <td class="border px-4 py-1 text-center">{{ $row['production_date'] }}</td>
                    <td class="border px-4 py-1 text-center">{{ $row['expiry_date'] }}</td>
                    <td class="border px-4 py-1 text-center">{{ $row['net_weight'] }}</td>
                    <td class="border px-4 py-1 text-center">{{ $row['quantity'] }} {{ $row['unit'] }}</td>
                    <td class="border px-4 py-1 text-center no-print">
                        <x-filament::button wire:click="showDetails({{ $row['product_id'] }}, '{{ $row['patch_number'] }}')"
                            size="sm" color="" tooltip="View Details">
                            Details
                        </x-filament::button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <x-filament::modal id="label-details-modal" width="2xl">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span class="font-bold text-lg text-gray-800"> </span>
            </div>
        </x-slot>

        @if ($selectedLabelDetails)
        <div class="p-8 font-serif text-black bg-white" style="font-family: 'Times New Roman', Times, serif; position: relative; min-height: 350px;">
            <div style="padding-right: 120px;">
                {{-- Top Section: Product Details --}}
                <div class="mb-6">
                    <table class="w-full text-base font-bold leading-relaxed border-none">
                        <tbody>
                            <tr>
                                <td class="w-40 align-top uppercase pb-1">PRODUCT</td>
                                <td class="w-4 align-top pb-1">:</td>
                                <td class="align-top pb-1">
                                    {{ $selectedLabelDetails['product_name'] }}
                                </td>
                            </tr>
                            <tr>
                                <td class="align-top uppercase pb-1">CODE</td>
                                <td class="align-top pb-1">:</td>
                                <td class="align-top pb-1">{{ $selectedLabelDetails['code'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="align-top uppercase pb-1">BATCH CODE</td>
                                <td class="align-top pb-1">:</td>
                                <td class="align-top pb-1">{{ $selectedLabelDetails['batch_code'] }}</td>
                            </tr>
                            <tr>
                                <td class="align-top uppercase pb-1">PROD. DATE</td>
                                <td class="align-top pb-1">:</td>
                                <td class="align-top pb-1">{{ $selectedLabelDetails['production_date'] }}</td>
                            </tr>
                            <tr>
                                <td class="align-top uppercase pb-1">BEST BEFORE</td>
                                <td class="align-top pb-1">:</td>
                                <td class="align-top pb-1">{{ $selectedLabelDetails['best_before'] ?? $selectedLabelDetails['expiry_date'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="align-top uppercase pb-1">NETT WEIGHT</td>
                                <td class="align-top pb-1">:</td>
                                <td class="align-top pb-1 uppercase">{{ $selectedLabelDetails['net_weight'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Middle Section: Manufacturer Info --}}
                <div class="mb-6">
                    <div class="mb-1 font-bold uppercase">MANUFACTURED BY:</div>
                    <div class="font-bold uppercase leading-tight mb-2">
                        {{ $selectedLabelDetails['manufactured_by'] }}
                    </div>
                    <div class="text-base leading-snug mb-1 font-bold">
                        {!! nl2br(e($selectedLabelDetails['address'])) !!}
                    </div>
                    <div class="font-bold mt-2">
                        <span class="uppercase">TEL :</span> {{ $selectedLabelDetails['tel'] }}
                    </div>
                </div>

                {{-- Bottom Section: Origin & Allergens --}}
                <div class="space-y-1 font-bold uppercase">
                    <div>
                        COUNTRY OF ORIGIN: {{ $selectedLabelDetails['country_of_origin'] }}
                    </div>
                    @if(!empty($selectedLabelDetails['allergen_info']))
                    <div class="uppercase">
                        {{ $selectedLabelDetails['allergen_info'] }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Halal Logo on right --}}
            <div style="position: absolute; right: 32px; bottom: 32px; width: 100px; text-align: center;">
                <div style="margin-bottom: 6px; display: flex; justify-content: center;">
                    @if($selectedLabelDetails['halal_logo'])
                    <img src="{{ $selectedLabelDetails['halal_logo'] }}" alt="Halal Logo" style="height: 80px; width: auto; object-fit: contain;">
                    @else
                    <div style="height: 80px; width: 80px; border: 1px dashed #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #555;">LOGO</div>
                    @endif
                </div>

                <div style="font-size: 11px; font-weight: bold; line-height: 1.2;">
                    MS1500<br>
                    X XXX-XX/XXXX
                </div>
            </div>
        </div>
        @else
        <div class="p-12 flex flex-col items-center justify-center text-gray-400">
            <x-filament::loading-indicator class="h-10 w-10 mb-4" />
            <p>Loading details...</p>
        </div>
        @endif
    </x-filament::modal>

    {{-- Print Script --}}
    <script>
        document.getElementById("printReport").addEventListener("click", function() {
            const originalContent = document.body.innerHTML;
            const printContent = document.getElementById("reportContent") ? document.getElementById("reportContent").innerHTML : document.querySelector('.fi-main-content').innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        });
    </script>
</x-filament::page>