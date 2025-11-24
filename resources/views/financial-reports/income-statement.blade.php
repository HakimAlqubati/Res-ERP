<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Statement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
            }
        }

        .report-line {
            border-bottom: 1px solid #e5e7eb;
        }

        .report-line-double {
            border-bottom: 3px double #374151;
        }

        .report-line-bold {
            border-bottom: 2px solid #374151;
        }
    </style>
</head>

<body class="bg-gray-100 p-8">
    <div class="max-w-5xl mx-auto bg-white shadow-xl">
        <!-- Company Header -->
        <div class="border-b-4 border-gray-800 p-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">INCOME STATEMENT</h1>
                <p class="text-sm text-gray-600 mt-2 font-medium">
                    @if($startDate && $endDate)
                    For the Period from {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
                    @else
                    For All Periods
                    @endif
                </p>
                <p class="text-xs text-gray-500 mt-1">Generated on {{ now()->format('F d, Y \a\t H:i') }}</p>
            </div>
        </div>

        <!-- Report Content -->
        <div class="p-8">
            <table class="w-full">
                <tbody>
                    <!-- Revenue Section -->
                    <tr>
                        <td colspan="2" class="py-3">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Revenue</h2>
                        </td>
                    </tr>
                    <tr class="report-line">
                        <td class="py-3 pl-8 text-sm text-gray-700">Total Revenue</td>
                        <td class="py-3 pr-4 text-sm text-right font-semibold text-gray-900 tabular-nums">
                            {{ number_format($report['revenue']['total'], 2) }}
                        </td>
                    </tr>

                    <!-- Spacing -->
                    <tr>
                        <td colspan="2" class="py-2"></td>
                    </tr>

                    <!-- Expenses Section -->
                    <tr>
                        <td colspan="2" class="py-3">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Operating Expenses</h2>
                        </td>
                    </tr>

                    @foreach($report['expenses']['details'] as $expense)
                    <tr class="report-line">
                        <td class="py-3 pl-8 text-gray-700">
                            <div class="text-sm">{{ $expense['category_name'] }}</div>
                            @if(!empty($expense['category_description']))
                            <div class="text-xs text-gray-500 mt-0.5 italic">{{ $expense['category_description'] }}</div>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-sm text-right text-gray-900 tabular-nums">
                            {{ number_format($expense['amount'], 2) }}
                        </td>
                    </tr>
                    @endforeach

                    <!-- Total Expenses -->
                    <tr class="report-line-bold">
                        <td class="py-3 pl-8 text-sm font-semibold text-gray-900">Total Operating Expenses</td>
                        <td class="py-3 pr-4 text-sm text-right font-bold text-gray-900 tabular-nums">
                            {{ number_format($report['expenses']['total'], 2) }}
                        </td>
                    </tr>

                    <!-- Spacing -->
                    <tr>
                        <td colspan="2" class="py-2"></td>
                    </tr>

                    <!-- Net Profit -->
                    <tr class="report-line-double bg-gray-50">
                        <td class="py-4 pl-8 text-base font-bold text-gray-900 uppercase tracking-wide">Net Profit (Loss)</td>
                        <td class="py-4 pr-4 text-base text-right font-bold tabular-nums {{ $report['net_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ number_format($report['net_profit'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 p-6 bg-gray-50">
            <div class="flex justify-between items-center text-xs text-gray-600">
                <div>
                    <p class="font-medium">Prepared by: ___________________________</p>
                    <p class="mt-1">Date: ___________________________</p>
                </div>
                <div>
                    <p class="font-medium">Approved by: ___________________________</p>
                    <p class="mt-1">Date: ___________________________</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Button -->
    <div class="text-center mt-6 no-print">
        <button onclick="window.print()"
            class="bg-white border-2 border-gray-800 text-gray-800 px-8 py-3 rounded-md hover:bg-gray-800 hover:text-white transition-colors font-medium">
            Print Report
        </button>
    </div>
</body>

</html>