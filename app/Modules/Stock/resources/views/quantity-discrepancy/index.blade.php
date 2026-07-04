<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantity Discrepancy</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
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
        .filters {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filters form {
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }
        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 300px;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
        }
        .form-group input {
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 10px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .filters button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            height: 42px;
        }
        .filters button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>Quantity Discrepancy</h1>
            <p style="margin: 5px 0 0 0; color: #666;">Overview of inventory transactions with output greater than input.</p>
        </div>
        @if(isset($storeId) && $storeId)
        <div class="count">
            Total Discrepancies: {{ count($report) }}
        </div>
        @endif
    </div>

    <div class="filters">
        <form action="" method="GET">
            <div class="form-group">
                <label>Store ID</label>
                <input type="number" name="store_id" value="{{ $storeId ?? '' }}" placeholder="Enter Store ID" required>
            </div>
            <button type="submit">Filter Report</button>
        </form>
    </div>

    @if(isset($storeId) && $storeId)
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>IN Date</th>
                    <th>OUT Date</th>
                    <th>Product</th>
                    <th>IN Type</th>
                    <th>IN ID</th>
                    <th>OUT Type</th>
                    <th>OUT ID</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Q In</th>
                    <th class="text-right">Q Out</th>
                    <th class="text-right">Qty Diff</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $row)
                    <tr>
                        <td>{{ $row->in_date }}</td>
                        <td>{{ $row->out_date }}</td>
                        <td>
                            <strong>{{ $row->product_name ?? 'Unknown' }}</strong>
                            <div style="font-size: 11px; color: #888;">ID: {{ $row->product_id }}</div>
                        </td>
                        <td><span class="type-badge">{{ class_basename($row->in_type) }}</span></td>
                        <td>{{ $row->in_id }}</td>
                        <td><span class="type-badge">{{ class_basename($row->out_type) }}</span></td>
                        <td>{{ $row->out_id }}</td>
                        <td class="text-right">{{ $row->quantity }}</td>
                        <td class="text-right">{{ $row->count_ }}</td>
                        <td class="text-right">{{ $row->qin }}</td>
                        <td class="text-right">{{ $row->qout }}</td>
                        <td class="text-right danger">{{ $row->qty_diff }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="empty-state">
                            <div style="font-size: 24px; margin-bottom: 10px;">📋</div>
                            <div>No discrepancies found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state" style="background: #fff; border-radius: 8px; border: 1px dashed #cbd5e1; padding: 60px;">
        <div style="font-size: 32px; margin-bottom: 15px;">🔍</div>
        <h3 style="margin: 0 0 10px 0; color: #334155;">Select Filters to Run Report</h3>
        <p style="margin: 0; color: #64748b;">Please enter a store ID to view discrepancies.</p>
    </div>
    @endif
</div>

</body>
</html>
