<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN Consumption Report</title>
    <!-- Modern Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-light: #64748b;
            --border: #e2e8f0;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 2rem;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }

        .card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            overflow: hidden;
        }

        .filter-form {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            background: #fafafa;
        }

        .filter-form input {
            padding: 0.6rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            outline: none;
            transition: all 0.2s;
            font-size: 0.95rem;
            min-width: 250px;
        }

        .filter-form input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-link {
            padding: 0.6rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            align-self: center;
            font-weight: 500;
            transition: color 0.2s;
        }

        .btn-link:hover {
            color: var(--text);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--surface);
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .grn-row td {
            background: #f1f5f9;
            font-weight: 600;
            color: #334155;
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid var(--border);
        }

        .item-row {
            transition: background-color 0.15s;
        }

        .item-row:hover {
            background: #f8fafc;
        }

        .item-row td {
            font-size: 0.9rem;
            color: #475569;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.025em;
        }

        .badge.green { background: #d1fae5; color: #065f46; }
        .badge.yellow { background: #fef3c7; color: #92400e; }
        .badge.blue { background: #dbeafe; color: #1e40af; }
        .badge.gray { background: #f1f5f9; color: #475569; }

        .text-right { text-align: right; }
        .font-semibold { font-weight: 600; }
        
        .empty-state {
            text-align: center;
            padding: 4rem;
            color: var(--text-light);
        }

        .pagination-container {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: center;
            border-top: 1px solid var(--border);
            background: var(--surface);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GRN Consumption Tracking</h1>
        </div>

        <div class="card">
            <!-- Filter Section -->
            <form class="filter-form" method="GET" action="{{ route('stock.reports.grn-consumption.index') }}">
                <input type="text" name="grn_number" placeholder="Search by GRN Number..." value="{{ request('grn_number') }}">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('stock.reports.grn-consumption.index') }}" class="btn-link">Clear</a>
            </form>

            <!-- Table Section -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Unit</th>
                            <th class="text-right">Entry Qty</th>
                            <th class="text-right">Remaining Qty</th>
                            <th>Entry Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report as $grnResult)
                            <!-- GRN Header Row -->
                            <tr class="grn-row">
                                <td colspan="5">
                                    <span style="font-size: 1.05rem;">📦 GRN: #{{ $grnResult->grnNumber }}</span>
                                    <span style="color: var(--text-light); font-size: 0.85rem; margin-left: 0.5rem; font-weight: normal;">
                                        ({{ $grnResult->grnDate ?? 'No Date' }})
                                    </span>
                                    @if($grnResult->isLinkedToInvoice)
                                        <span class="badge blue" style="margin-left: 1rem;">Invoice: #{{ $grnResult->invoiceNumber }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($grnResult->isFullyCompleted)
                                        <span class="badge green">Fully Completed</span>
                                    @else
                                        <span class="badge yellow">In Progress</span>
                                    @endif
                                </td>
                            </tr>
                            
                            <!-- GRN Items Rows -->
                            @foreach($grnResult->items as $item)
                                <tr class="item-row">
                                    <td style="padding-left: 3rem;">{{ $item->productName }}</td>
                                    <td>{{ $item->unitName }}</td>
                                    <td class="text-right">{{ $item->entryQuantity }}</td>
                                    <td class="text-right font-semibold" style="color: {{ $item->remainingQuantity > 0 ? 'var(--primary)' : 'var(--text-light)' }};">
                                        {{ $item->remainingQuantity }}
                                    </td>
                                    <td>{{ $item->entryDate ? \Carbon\Carbon::parse($item->entryDate)->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        @if($item->isCompleted)
                                            <span class="badge green">Completed</span>
                                        @elseif($item->hasStartedLeaving)
                                            <span class="badge yellow">Consuming</span>
                                        @else
                                            <span class="badge gray">Untouched</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: #cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <div>No Goods Received Notes found.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($report->hasPages())
                <div class="pagination-container">
                    {{ $report->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>
