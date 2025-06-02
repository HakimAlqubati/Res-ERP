<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تقرير المنتجات في مخازن غير خاصة بها</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">
    <div class="container mx-auto px-4 py-6">
        {{-- <h1 class="text-3xl font-bold text-center text-gray-700 mb-6">📦 تقرير المنتجات في مخازن غير خاصة بها</h1> --}}

        @if (count($report) > 0)
            <div class="overflow-x-auto rounded shadow bg-white p-4">
                <table class="min-w-full border text-sm text-center">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            {{-- <th class="py-2 px-4 border">#</th> --}}
                            <th class="py-2 px-4 border">كود المنتج</th>
                            <th class="py-2 px-4 border">اسم المنتج</th>
                            <th class="py-2 px-4 border">المخزن الفعلي</th>
                            <th class="py-2 px-4 border">المخزن المتوقع</th>
                            <th class="py-2 px-4 border">تاريخ الحركة</th>
                            <th class="py-2 px-4 border">الكمية</th>
                            <th class="py-2 px-4 border">البيان</th>
                            <th class="py-2 px-4 border">رقم الحركة</th>
                            <th class="py-2 px-4 border">نوع الحركة</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600">
                        @foreach ($report as $index => $item)
                            <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100 transition">
                                {{-- <td class="py-1 px-2 border">{{ $index + 1 }}</td> --}}
                                <td class="py-1 px-2 border font-mono">{{ $item['product_code'] }}</td>
                                <td class="py-1 px-2 border">{{ $item['product_name'] }}</td>
                                <td class="py-1 px-2 border text-red-600 font-semibold">{{ $item['actual_store'] }}</td>
                                <td class="py-1 px-2 border text-green-600 font-semibold">{{ $item['expected_store'] }}</td>
                                <td class="py-1 px-2 border">{{ \Carbon\Carbon::parse($item['movement_date'])->format('Y-m-d H:i') }}</td>
                                <td class="py-1 px-2 border font-bold">{{ $item['quantity'] }}</td>
                                <td class="py-1 px-2 border text-xs">{{ $item['notes'] }}</td>
                                <td class="py-1 px-2 border">{{ $item['transactionable_id'] }}</td>
                                <td class="py-1 px-2 border">{{ $item['transactionable_type'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 p-4 rounded text-center mt-4">
                لا توجد بيانات لعرضها حالياً.
            </div>
        @endif
    </div>
</body>

</html>
