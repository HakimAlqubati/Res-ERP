<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wrong Package Size Discrepancy</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1300px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .header .count {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .danger {
            color: #dc3545;
            font-weight: bold;
        }
        .success {
            color: #198754;
            font-weight: bold;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .type-badge {
            background: #e2e8f0;
            color: #475569;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .table-container {
            max-height: calc(100vh - 160px);
            overflow: auto;
            border-bottom: 1px solid #ddd;
        }
        .section-divider {
            border-left: 2px solid #e2e8f0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>Wrong Package Size Discrepancy</h1>
            <p style="margin: 5px 0 0 0; color: #666;">Stock adjustments with mismatched package sizes compared to unit prices.</p>
        </div>
        <div class="count">
            Total Discrepancies: {{ count($report) }}
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th colspan="2" style="text-align: center;">General Info</th>
                    <th colspan="2" class="section-divider" style="text-align: center; background-color: #fee2e2; color: #991b1b;">Wrong Entry (Inventory)</th>
                    <th colspan="2" class="section-divider" style="text-align: center; background-color: #dcfce7; color: #166534;">Correct Unit Info</th>
                    <th colspan="2" class="section-divider" style="text-align: center; background-color: #e0f2fe; color: #075985;">Base Unit (Size 1)</th>
                </tr>
                <tr>
                    <th>Store</th>
                    <th>Product</th>
                    
                    <th class="section-divider">Entered Unit</th>
                    <th class="text-right">Pkg Size / Price</th>
                    
                    <th class="section-divider text-right">Correct Pkg Size</th>
                    <th class="text-right">Correct Price</th>
                    
                    <th class="section-divider">Base Unit</th>
                    <th class="text-right">Base Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $row)
                    <tr>
                        <td>
                            <strong>{{ $row->store_name ?? 'Unknown' }}</strong>
                            <div style="font-size: 11px; color: #888;">ID: {{ $row->store_id }}</div>
                        </td>
                        <td>
                            <strong>{{ $row->product_name ?? 'Unknown' }}</strong>
                            <div style="font-size: 11px; color: #888;">ID: {{ $row->product_id }}</div>
                        </td>
                        
                        <td class="section-divider">
                            <span class="type-badge">{{ $row->entered_unit_name ?? 'Unknown' }}</span>
                            <div style="font-size: 11px; color: #888; margin-top: 2px;">ID: {{ $row->entered_unit_id }}</div>
                        </td>
                        <td class="text-right danger">
                            <div>Size: {{ $row->wrong_package_size }}</div>
                            <div style="font-size: 11px;">Price: {{ $row->wrong_price }}</div>
                        </td>
                        
                        <td class="section-divider text-right success">{{ $row->correct_package_size }}</td>
                        <td class="text-right success">{{ $row->correct_price }}</td>
                        
                        <td class="section-divider">
                            <span class="type-badge">{{ $row->base_unit_name ?? 'Unknown' }}</span>
                            <div style="font-size: 11px; color: #888; margin-top: 2px;">ID: {{ $row->base_unit_id_size_1 }}</div>
                        </td>
                        <td class="text-right" style="color: #0284c7; font-weight: 500;">{{ $row->base_unit_price_size_1 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div style="font-size: 24px; margin-bottom: 10px;">📋</div>
                            <div>No discrepancies found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
