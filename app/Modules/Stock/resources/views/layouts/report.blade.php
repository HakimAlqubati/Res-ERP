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
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .report-nav {
            background: var(--surface);
            border-right: 1px solid var(--border);
            width: 250px;
            display: flex;
            flex-direction: column;
            padding: 2rem 0;
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
        }
        .nav-logo {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--text);
            padding: 0 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-tabs {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .nav-tab {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .nav-tab:hover { color: var(--text); background: #f8fafc; }
        .nav-tab.active {
            color: var(--primary);
            border-left-color: var(--primary);
            background: #eff6ff;
        }

        .main-wrapper { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; width: 100%; }

        .header { margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.75rem; font-weight: 700; color: #1e293b; }
        .header p { margin: 0.5rem 0 0; color: var(--text-light); }

        .card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            /* overflow: hidden; removed to allow sticky header relative to window */
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
            border-radius: 12px 12px 0 0; /* Add radius back for the top corners */
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
        .table-responsive {
            /* overflow-x: auto; removed to allow sticky header relative to window */
        }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); }
        th { 
            background: #f8fafc; /* A slightly different shade for the header */
            font-weight: 600; 
            color: var(--text-light); 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 0.05em;
            
            /* Sticky Header */
            position: sticky;
            top: 0; /* Changed back to 0 since navbar is on the left */
            z-index: 5;
            box-shadow: 0 1px 0 var(--border), 0 -1px 0 var(--border); /* Borders for sticky */
            border-bottom: none;
        }
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
        
        /* Autocomplete Styles */
        .autocomplete-container { position: relative; min-width: 250px; }
        .autocomplete-input { 
            width: 100%; 
            padding: 0.6rem 2.5rem 0.6rem 1rem; /* Space for both clear button and chevron */
            border: 1px solid var(--border); 
            border-radius: 6px; 
            outline: none; 
            transition: all 0.2s; 
            font-size: 0.95rem; 
            background-color: #fff;
            /* Chevron arrow to make it look like a select */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            cursor: pointer;
        }
        .autocomplete-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--border); border-top: none; border-radius: 0 0 6px 6px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); max-height: 250px; overflow-y: auto; z-index: 50; display: none; }
        .autocomplete-dropdown.show { display: block; }
        .autocomplete-option { padding: 0.5rem 1rem; cursor: pointer; font-size: 0.9rem; border-bottom: 1px solid #f1f5f9; }
        .autocomplete-option:last-child { border-bottom: none; }
        .autocomplete-option:hover { background: #f8fafc; color: var(--primary); }
        .autocomplete-option .code { font-size: 0.75rem; color: var(--text-light); margin-top: 0.2rem; }
        .clear-autocomplete { 
            position: absolute; 
            right: 2.2rem; /* Positioned left of the chevron */
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            color: #94a3b8; 
            font-weight: bold; 
            display: none; 
            padding: 0.2rem;
            z-index: 10;
        }
        .clear-autocomplete:hover { color: var(--danger); }

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
                Products Summary
            </a>
            <a href="{{ route('stock.reports.grn-consumption.index') }}" 
               class="nav-tab {{ $currentRoute === 'stock.reports.grn-consumption.index' ? 'active' : '' }}">
                Receipts & Invoices
            </a>
            <a href="{{ route('stock.reports.grn-consumption-items.index') }}" 
               class="nav-tab {{ $currentRoute === 'stock.reports.grn-consumption-items.index' ? 'active' : '' }}">
                Detailed Items
            </a>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="container">
            @yield('content')
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.autocomplete-container');
            if(!container) return;

            const input = container.querySelector('.autocomplete-input');
            const hidden = container.querySelector('input[name="product_id"]');
            const dropdown = container.querySelector('.autocomplete-dropdown');
            const clearBtn = container.querySelector('.clear-autocomplete');
            
            let timeout = null;

            function fetchProducts(query = '') {
                fetch('{{ route("stock.reports.products.search") }}?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if(data.length === 0) {
                            dropdown.innerHTML = '<div class="autocomplete-option" style="cursor:default">No products found</div>';
                        } else {
                            data.forEach(prod => {
                                const div = document.createElement('div');
                                div.className = 'autocomplete-option';
                                div.innerHTML = `<div>${prod.product_name}</div><div class="code">${prod.product_code || ''}</div>`;
                                div.addEventListener('click', () => {
                                    input.value = prod.product_name;
                                    hidden.value = prod.product_id;
                                    dropdown.classList.remove('show');
                                    clearBtn.style.display = 'block';
                                });
                                dropdown.appendChild(div);
                            });
                        }
                    });
            }

            input.addEventListener('focus', () => {
                dropdown.classList.add('show');
                if (dropdown.children.length === 0) {
                    fetchProducts(input.value);
                }
            });

            input.addEventListener('input', (e) => {
                clearTimeout(timeout);
                hidden.value = ''; // clear selection if typing
                clearBtn.style.display = input.value ? 'block' : 'none';
                dropdown.classList.add('show');
                dropdown.innerHTML = '<div class="autocomplete-option" style="cursor:default">Searching...</div>';
                timeout = setTimeout(() => fetchProducts(e.target.value), 300);
            });

            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                input.value = '';
                hidden.value = '';
                clearBtn.style.display = 'none';
                dropdown.classList.remove('show');
                fetchProducts('');
            });

            document.addEventListener('click', (e) => {
                if(!container.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // If we have an initial value, show the clear button
            if (hidden.value) {
                clearBtn.style.display = 'block';
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
