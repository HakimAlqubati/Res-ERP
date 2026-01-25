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
                    <th class="px-4 py-2 no-print">Actions</th>
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
                            icon="heroicon-o-eye" size="sm" color="" tooltip="View Details">
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
                <span>Product Label Details</span>
            </div>
        </x-slot>

        @if ($selectedLabelDetails)
        <div class="space-y-6 p-4">
            {{-- Product Header --}}
            <div class="border-b pb-4">
                <h2 class="text-2xl font-bold text-gray-800">Product Name: {{ $selectedLabelDetails['product_name'] }}</h2>
                <p class="text-sm text-gray-500">Product Code: {{ $selectedLabelDetails['code'] }}</p>
            </div>

            {{-- Information Grid --}}
            <div class="grid grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs uppercase font-bold text-gray-400 mb-1">Batch Code</p>
                    <p class="text-lg font-medium text-gray-900">{{ $selectedLabelDetails['batch_code'] }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs uppercase font-bold text-gray-400 mb-1">Net Weight</p>
                    <p class="text-lg font-medium text-gray-900">{{ $selectedLabelDetails['net_weight'] }}</p>
                </div>
                <div class="bg-blue-50 p-4 rounded-xl shadow-sm border border-blue-100">
                    <p class="text-xs uppercase font-bold text-blue-400 mb-1">Production Date</p>
                    <p class="text-lg font-medium text-blue-900">{{ $selectedLabelDetails['production_date'] }}</p>
                </div>
                <div class="bg-orange-50 p-4 rounded-xl shadow-sm border border-orange-100">
                    <p class="text-xs uppercase font-bold text-orange-400 mb-1">Best Before</p>
                    <p class="text-lg font-medium text-orange-900">{{ $selectedLabelDetails['best_before'] }}</p>
                </div>
            </div>

            {{-- Manufacturer Info --}}
            <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                <h3 class="text-sm font-bold text-indigo-900 mb-3 flex items-center gap-2">
                    Manufacturer Information
                </h3>
                <div class="space-y-2 text-sm text-indigo-800">
                    <p><span class="font-bold">Company:</span> {{ $selectedLabelDetails['manufactured_by'] }}</p>
                    <p><span class="font-bold">Address:</span> {{ $selectedLabelDetails['address'] }}</p>
                    <p><span class="font-bold">Telephone:</span> {{ $selectedLabelDetails['tel'] }}</p>
                    <p><span class="font-bold">Country:</span> {{ $selectedLabelDetails['country_of_origin'] }}</p>
                </div>
            </div>

            {{-- Additional Details --}}
            @if($selectedLabelDetails['allergen_info'])
            <div class="bg-red-50 p-4 rounded-xl border border-red-100 italic">
                <p class="text-xs font-bold text-red-600 mb-1 flex items-center gap-1">
                    Allergen Information:
                </p>
                <p class="text-sm text-red-800">{{ $selectedLabelDetails['allergen_info'] }}</p>
            </div>
            @endif

            @if($selectedLabelDetails['halal_logo'])
            <div class="flex justify-center pt-4">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-2">
                        <span class="text-green-700 font-bold text-xl">حلال</span>
                    </div>
                    <p class="text-xs font-bold text-green-700 uppercase tracking-widest">Certified Halal</p>
                </div>
            </div>
            @endif
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