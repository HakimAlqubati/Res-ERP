<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Stock Reports')</title>
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
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Navigation */
        .report-nav {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            height: 4rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .nav-logo {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--text);
            margin-right: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-tabs {
            display: flex;
            gap: 1rem;
            height: 100%;
        }
        .nav-tab {
            display: inline-flex;
            align-items: center;
            padding: 0 1rem;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .nav-tab:hover { color: var(--text); }
        .nav-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }

        .header { margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.75rem; font-weight: 700; color: #1e293b; }
        .header p { margin: 0.5rem 0 0; color: var(--text-light); }

        .card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            overflow: hidden;
        }

        /* Forms */
        .filter-form {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            background: #fafafa;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-form input, .filter-form select {
            padding: 0.6rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            outline: none;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .filter-form input:focus, .filter-form select:focus {
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

        /* Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); }
        th { background: var(--surface); font-weight: 600; color: var(--text-light); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        .text-right { text-align: right; }
        .font-bold, .font-semibold { font-weight: 600; }
        .text-muted { color: var(--text-light); font-size: 0.85rem; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.025em; }
        .badge.green { background: #d1fae5; color: #065f46; }
        .badge.yellow { background: #fef3c7; color: #92400e; }
        .badge.blue { background: #dbeafe; color: #1e40af; }
        .badge.gray { background: #f1f5f9; color: #475569; }

        /* Specific Table Rows */
        .grn-row td { background: #f1f5f9; font-weight: 600; color: #334155; padding-top: 1.25rem; padding-bottom: 1.25rem; border-bottom: 2px solid var(--border); }
        .item-row { transition: background-color 0.15s; }
        .item-row:hover { background: #f8fafc; }
        .item-row td { font-size: 0.95rem; color: #334155; }

        /* Progress Bar Styles */
        .progress-wrapper { width: 100%; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .progress-bg { background-color: var(--border); border-radius: 9999px; height: 6px; width: 100%; overflow: hidden; }
        .progress-bar { height: 100%; border-radius: 9999px; }
        .progress-bar.low { background-color: var(--success); }
        .progress-bar.med { background-color: var(--warning); }
        .progress-bar.high { background-color: var(--danger); }
        .progress-text { font-size: 0.75rem; font-weight: 600; color: var(--text-light); min-width: 40px; }

        .empty-state { text-align: center; padding: 4rem; color: var(--text-light); }
        .pagination-container { padding: 1.5rem; border-top: 1px solid var(--border); background: var(--surface); }
        
        /* Custom Pagination Styles */
        .pagination { display: flex; padding-left: 0; list-style: none; gap: 0.35rem; margin: 0; flex-wrap: wrap; justify-content: center; }
        .page-item { margin: 0; }
        .page-link { padding: 0.5rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; color: var(--text); text-decoration: none; font-size: 0.875rem; transition: all 0.2s; display: block; background: #fff; }
        .page-link:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .page-item.active .page-link { background: var(--primary); color: white; border-color: var(--primary); }
        .page-item.disabled .page-link { color: #94a3b8; background: #f8fafc; cursor: not-allowed; border-color: var(--border); }
        
        @yield('styles')
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="report-nav">
        <div class="nav-logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);">
                <path d="M3 3v18h18"></path><path d="M18 17V9"></path><path d="M13 17V5"></path><path d="M8 17v-3"></path>
            </svg>
            Stock Analytics
        </div>
        <div class="nav-tabs">
            @php
                $currentRoute = request()->route()->getName();
            @endphp
            <a href="{{ route('stock.reports.product-grn-aggregation.index') }}" 
               class="nav-tab {{ $currentRoute === 'stock.reports.product-grn-aggregation.index' ? 'active' : '' }}">
                Aggregated Overview
            </a>
            <a href="{{ route('stock.reports.grn-consumption.index') }}" 
               class="nav-tab {{ $currentRoute === 'stock.reports.grn-consumption.index' ? 'active' : '' }}">
                Hierarchical GRN
            </a>
            <a href="{{ route('stock.reports.grn-consumption-items.index') }}" 
               class="nav-tab {{ $currentRoute === 'stock.reports.grn-consumption-items.index' ? 'active' : '' }}">
                Detailed Flat Items
            </a>
        </div>
    </nav>

    <div class="container">
        @yield('content')
    </div>
</body>
</html>
