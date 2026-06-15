<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Batch Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tom Select for searchable dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Tom Select styling tweaks to match Tailwind */
        .ts-control {
            padding: 0.5rem;
            border-radius: 0.375rem;
            border-color: #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">

    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4">Inventory Batch Report</h1>

        <!-- Filters -->
        <form method="GET" action="{{ route('reports.batch') }}" class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Store</label>
                    <select name="store_id" class="searchable-select w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Store --</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <select name="product_id" class="searchable-select w-full border-gray-300 rounded-md shadow-sm border p-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ $productId == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow hover:bg-blue-700 transition" style="height: 42px;">
                        View Report
                    </button>
                </div>
            </div>
        </form>

        <!-- Table -->
        @if(request()->has('store_id') && request()->has('product_id'))
            @if($batches->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-semibold">Transaction ID</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Product</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Unit</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Pkg Size</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Date</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Unit Price</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Total In</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Total Out</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-blue-600">Remaining</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-green-600">Remaining Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                                @php
                                    $remaining = $batch->total_in - $batch->total_out;
                                    $packageSize = max($batch->package_size, 1);
                                    $remainingTotalPrice = $remaining * ($batch->unit_price);
                                @endphp
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $batch->id }}</td>
                                    <td class="px-6 py-4">{{ $batch->product ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $batch->unit ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $batch->package_size ?? 1 }}</td>
                                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($batch->movement_date)->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4">{{ number_format($batch->unit_price, 2) }}</td>
                                    <td class="px-6 py-4">{{ $batch->total_in }}</td>
                                    <td class="px-6 py-4">{{ $batch->total_out }}</td>
                                    <td class="px-6 py-4 font-bold text-blue-600">{{ $remaining }}</td>
                                    <td class="px-6 py-4 font-bold text-green-600">{{ number_format($remainingTotalPrice, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4 rounded-r-md">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                No available batches found for this product in the selected store.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-4 rounded-r-md">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Please select a store and a product to view the report.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.searchable-select').forEach((el) => {
                new TomSelect(el, {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    }
                });
            });
        });
    </script>
</body>
</html>
