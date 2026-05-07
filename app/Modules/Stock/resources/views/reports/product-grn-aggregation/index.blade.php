<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Consumption Aggregation</title>
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
            --danger: #ef4444;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 2rem;
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        .header { margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.75rem; font-weight: 700; color: #1e293b; }
        .header p { margin: 0.5rem 0 0; color: var(--text-light); }

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
        .btn-primary:hover { background: var(--primary-hover); }

        .btn-link {
            padding: 0.6rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            align-self: center;
            font-weight: 500;
        }
        .btn-link:hover { color: var(--text); }

        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 1.25rem 1.5rem;
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

        .item-row { transition: background-color 0.15s; }
        .item-row:hover { background: #f8fafc; }
        .item-row td { font-size: 0.95rem; color: #334155; }

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
        .badge.gray { background: #f1f5f9; color: #475569; }

        .text-right { text-align: right; }
        .font-bold { font-weight: 600; }
        .text-muted { color: var(--text-light); font-size: 0.85rem; }

        /* Progress Bar Styles */
        .progress-wrapper {
            width: 100%;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .progress-bg {
            background-color: var(--border);
            border-radius: 9999px;
            height: 6px;
            width: 100%;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 9999px;
        }
        .progress-bar.low { background-color: var(--success); }
        .progress-bar.med { background-color: var(--warning); }
        .progress-bar.high { background-color: var(--danger); }
        .progress-text {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-light);
            min-width: 40px;
        }

        .empty-state { text-align: center; padding: 4rem; color: var(--text-light); }
        .pagination-container { padding: 1rem 1.5rem; display: flex; justify-content: center; border-top: 1px solid var(--border); background: var(--surface); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Product Aggregated Consumption</h1>
            <p>High-performance analytics of stock entry and consumption grouped by Product.</p>
        </div>

        <div class="card">
            <!-- Filter Section -->
            <form class="filter-form" method="GET" action="{{ route('stock.reports.product-grn-aggregation.index') }}">
                <input type="text" name="search" placeholder="Search by Product name or Code..." value="{{ request('search') }}">
                <input type="date" name="date_from" value="{{ request('date_from') }}" title="GRN Date From">
                <input type="date" name="date_to" value="{{ request('date_to') }}" title="GRN Date To">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('stock.reports.product-grn-aggregation.index') }}" class="btn-link">Clear</a>
            </form>

            <!-- Table Section -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th class="text-right">Total Entry (Base)</th>
                            <th class="text-right">Total Consumed</th>
                            <th class="text-right">Remaining Stock</th>
                            <th style="width: 25%;">Consumption Rate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report as $item)
                            <tr class="item-row">
                                <td>
                                    <div class="font-bold" style="color: #0f172a; font-size: 1rem;">{{ $item->productName }}</div>
                                    <div class="text-muted">Code: {{ $item->productCode }}</div>
                                </td>
                                <td class="text-right font-bold">{{ $item->totalEntryQty }}</td>
                                <td class="text-right" style="color: var(--danger); font-weight: 500;">
                                    {{ $item->totalConsumedQty }}
                                </td>
                                <td class="text-right font-bold" style="color: {{ $item->remainingQty > 0 ? 'var(--primary)' : 'var(--text-light)' }}; font-size: 1.05rem;">
                                    {{ $item->remainingQty }}
                                </td>
                                <td>
                                    @php
                                        $barColor = 'low';
                                        if ($item->consumptionPercentage > 50) $barColor = 'med';
                                        if ($item->consumptionPercentage > 85) $barColor = 'high';
                                    @endphp
                                    <div class="progress-wrapper">
                                        <div class="progress-bg">
                                            <div class="progress-bar {{ $barColor }}" style="width: {{ $item->consumptionPercentage }}%;"></div>
                                        </div>
                                        <div class="progress-text">{{ $item->consumptionPercentage }}%</div>
                                    </div>
                                </td>
                                <td>
                                    @if($item->isFullyConsumed)
                                        <span class="badge green">100% Consumed</span>
                                    @elseif($item->consumptionPercentage > 0)
                                        <span class="badge" style="background: #e0f2fe; color: #0284c7;">Active</span>
                                    @else
                                        <span class="badge gray">Untouched</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem auto; color: #cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <div>No Products found matching the criteria.</div>
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
