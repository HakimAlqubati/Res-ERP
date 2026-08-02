<!DOCTYPE html>
<html dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'sans-serif'; font-size: 12px; }
        .batch-report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .batch-report-table th,
        .batch-report-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        .batch-report-table thead th {
            background-color: #f9fafb;
            font-weight: bold;
        }
        .batch-report-table .product-group-row td {
            font-weight: bold;
            background-color: #e5e7eb;
            text-align: center;
        }
        .shortage-row {
            color: red;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div id="reportContent">
        <table class="batch-report-table">
            <thead>
                <tr>
                    <th colspan="7" style="text-align: center; border:none; padding-bottom: 20px;">
                        <h3>Recipe Ingredients Stock Report</h3>
                        @if($compoundProduct)
                            <p style="margin-top: 5px; font-weight: normal;">
                                 <strong>{{ $compoundProduct->code }} - {{ $compoundProduct->name }}</strong>
                            </p>
                        @elseif($category)
                            <p style="margin-top: 5px; font-weight: normal;">
                                 <strong>Category: {{ $category->name }}</strong>
                            </p>
                        @endif
                    </th>
                </tr>
                <tr>
                    <th>Code</th>
                    <th>Component Name</th>
                    <th>Unit</th>
                    <th>Recipe Qty</th>
                    <th>Waste %</th>
                    <th>Required Qty</th>
                    <th>Qty in Stock</th>
                </tr>
            </thead>
            <tbody>
                @if($reportResult && $reportResult->count() > 0)
                    @php
                        $groupedComponents = $reportResult->groupBy('compound_product_id');
                    @endphp
                    @foreach ($groupedComponents as $compoundId => $componentsGroup)
                        @if(!$compoundProduct)
                            <tr class="product-group-row">
                                <td colspan="7">
                                    {{ $componentsGroup->first()['compound_product_code'] }} - {{ $componentsGroup->first()['compound_product_name'] }}
                                </td>
                            </tr>
                        @endif
                        @foreach ($componentsGroup as $component)
                            <tr class="{{ $component['has_shortage'] ? 'shortage-row' : '' }}">
                                <td>{{ $component['product_code'] }}</td>
                                <td>{{ $component['product_name'] }}</td>
                                <td>{{ $component['unit_name'] ?? '-' }}</td>
                                <td>{{ $component['recipe_quantity'] }}</td>
                                <td>{{ $component['waste_percentage'] }}%</td>
                                <td>{{ formatQunantity($component['required_quantity_for_one_unit']) }}</td>
                                <td style="font-weight: 600;">
                                    {{ formatQunantity($component['available_balance']) }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center">No components found for this selection.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>
