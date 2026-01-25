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

    {{-- Print Button --}}
    <div class="flex justify-end mb-4">
        <button id="printReport"
            class="px-6 py-2 font-semibold rounded-md border border-blue-600 bg-blue-500 hover:bg-blue-700 transition duration-300 shadow-md">
            🖨️ Print
        </button>
    </div>

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
                <span class="font-bold text-lg text-gray-800">تفاصيل المنتج</span>
            </div>
        </x-slot>

        @if ($selectedLabelDetails)
        <div class="p-4">
            {{-- حاوية المنتج --}}
            <div class="mb-4 p-4 border border-gray-200 rounded-lg bg-gray-50 flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $selectedLabelDetails['product_name'] }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Code: <span class="font-mono font-medium text-gray-700">{{ $selectedLabelDetails['code'] }}</span></p>
                </div>
                @if($selectedLabelDetails['halal_logo'])
                <div class="px-1 py-1 bg-white border border-gray-200 rounded text-center">
                    <img src="{{ $selectedLabelDetails['halal_logo'] }}" alt="Halal Logo" style="max-height: 100px;width: auto;">
                </div>
                @endif
            </div>

            {{-- الجدول بحدود كاملة --}}
            <div class="overflow-hidden rounded-lg border border-gray-200">
                <table class="min-w-full text-sm border-collapse">
                    <tbody>
                        {{-- الصف الأول --}}
                        <tr>
                            <td class="w-1/4 px-4 py-3 bg-gray-100 font-bold text-gray-600 border-b border-r border-gray-200">
                                Batch Code
                            </td>
                            <td class="w-1/4 px-4 py-3 text-gray-900 font-mono border-b border-r border-gray-200">
                                {{ $selectedLabelDetails['batch_code'] }}
                            </td>
                            <td class="w-1/4 px-4 py-3 bg-gray-100 font-bold text-gray-600 border-b border-r border-gray-200">
                                Net Weight
                            </td>
                            <td class="w-1/4 px-4 py-3 text-gray-900 border-b border-gray-200">
                                {{ $selectedLabelDetails['net_weight'] }}
                            </td>
                        </tr>

                        {{-- صف التواريخ --}}
                        <tr>
                            <td class="px-4 py-3 bg-blue-50/50 font-bold text-gray-600 border-b border-r border-gray-200">
                                Production Date
                            </td>
                            <td class="px-4 py-3 text-blue-700 font-medium border-b border-r border-gray-200">
                                {{ $selectedLabelDetails['production_date'] }}
                            </td>
                            <td class="px-4 py-3 bg-orange-50/50 font-bold text-gray-600 border-b border-r border-gray-200">
                                Best Before
                            </td>
                            <td class="px-4 py-3 text-orange-700 font-medium border-b border-gray-200">
                                {{ $selectedLabelDetails['best_before'] }}
                            </td>
                        </tr>

                        {{-- عنوان قسم المصنع --}}
                        <tr>
                            <td colspan="4" class="px-4 py-2 bg-gray-200 text-gray-800 font-bold text-center text-xs uppercase tracking-wider border-b border-gray-200">
                                Manufacturer Information
                            </td>
                        </tr>

                        {{-- معلومات المصنع --}}
                        <tr>
                            <td class="px-4 py-3 bg-gray-100 font-bold text-gray-600 border-b border-r border-gray-200">
                                Company
                            </td>
                            <td colspan="3" class="px-4 py-3 text-gray-900 border-b border-gray-200">
                                {{ $selectedLabelDetails['manufactured_by'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 bg-gray-100 font-bold text-gray-600 border-b border-r border-gray-200">
                                Address
                            </td>
                            <td colspan="3" class="px-4 py-3 text-gray-900 border-b border-gray-200">
                                {{ $selectedLabelDetails['address'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 bg-gray-100 font-bold text-gray-600 border-b border-r border-gray-200">
                                Country
                            </td>
                            <td class="px-4 py-3 text-gray-900 border-b border-r border-gray-200">
                                {{ $selectedLabelDetails['country_of_origin'] }}
                            </td>
                            <td class="px-4 py-3 bg-gray-100 font-bold text-gray-600 border-b border-r border-gray-200">
                                Telephone
                            </td>
                            <td class="px-4 py-3 text-gray-900 font-mono border-b border-gray-200">
                                {{ $selectedLabelDetails['tel'] }}
                            </td>
                        </tr>

                        {{-- الحساسية --}}
                        @if($selectedLabelDetails['allergen_info'])
                        <tr>
                            <td class="px-4 py-3 bg-red-50 font-bold text-red-700 border-r border-red-100">
                                Allergens
                            </td>
                            <td colspan="3" class="px-4 py-3 bg-red-50/30 text-red-800 italic">
                                {{ $selectedLabelDetails['allergen_info'] }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
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