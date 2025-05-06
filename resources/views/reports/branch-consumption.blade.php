<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Branch Consumption Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ url('/') . '/css/filament/filament/app.css' }}">

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('[data-tab-button]');
            const panels = document.querySelectorAll('[data-tab-content]');

            tabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.getAttribute('data-tab-button');

                    // Deactivate all
                    tabs.forEach(b => b.classList.remove('border-indigo-600', 'text-indigo-600'));
                    panels.forEach(p => p.classList.add('hidden'));

                    // Activate current
                    btn.classList.add('border-indigo-600', 'text-indigo-600');
                    document.getElementById(target).classList.remove('hidden');
                });
            });

            // Activate first tab
            if (tabs.length > 0) {
                tabs[0].click();
            }
        });
    </script>
</head>

<body class="bg-gray-100 text-gray-800">
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-indigo-800 mb-1">Branch Consumption Report</h1>
                <p class="text-sm text-gray-600">
                    From <strong>{{ $fromDate }}</strong> to <strong>{{ $toDate }}</strong> | Interval:
                    <strong class="capitalize">{{ $intervalType }}</strong>
                </p>
            </div>
            <button onclick="window.print()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow">
                🖨️ Print
            </button>
        </div>

        {{-- Tab Buttons --}}
        <div class="flex flex-wrap gap-2 mb-6 border-b">
            @foreach ($data as $index => $branch)
                <button class="px-4 py-2 text-sm border-b-2 font-medium transition-all"
                    data-tab-button="branch-{{ $index }}">
                    {{ $branch['branch_name'] }}
                </button>
            @endforeach
        </div>

        {{-- Tab Contents --}}
        @foreach ($data as $index => $branch)
            <div id="branch-{{ $index }}" data-tab-content class="hidden">
                <div class="mb-10 border rounded-lg shadow-sm bg-white">
                    <div class="p-4 border-b bg-indigo-50 rounded-t-md">
                        <h2 class="text-xl font-semibold text-indigo-700">
                            Branch: {{ $branch['branch_name'] }}
                        </h2>
                    </div>

                    <div class="p-4">
                        @foreach ($branch['products'] as $product)
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-800">
                                    {{ $product['product_name'] }}
                                    <span class="text-sm text-gray-500">({{ $product['category_name'] }})</span>
                                </h3>

                                <div class="overflow-x-auto mt-3 rounded-md border">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-semibold">Date</th>
                                                <th class="px-4 py-2 text-left font-semibold">Total Quantity</th>
                                                <th class="px-4 py-2 text-left font-semibold">Order Count</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @foreach ($product['daily'] as $entry)
                                                <tr class="hover:bg-gray-50 transition">
                                                    <td class="px-4 py-2">{{ $entry['date'] }}</td>
                                                    <td class="px-4 py-2">{{ $entry['total_quantity'] }}</td>
                                                    <td class="px-4 py-2">{{ $entry['order_count'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        @if (empty($data))
            <div class="text-center py-10 text-gray-500 text-lg">
                No data available for the selected period.
            </div>
        @endif
    </div>
</body>

</html>
