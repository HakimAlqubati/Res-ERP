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
            color-scheme: light;
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-alt: #fafafa;
            --text: #0f172a;
            --text-light: #64748b;
            --border: #e2e8f0;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --row-bg: #f8fafc;
            --grn-row-bg: #f1f5f9;
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-alt: #0f172a;
            --text: #f8fafc;
            --text-light: #94a3b8;
            --border: #334155;
            --primary: #3b82f6;
            --primary-hover: #60a5fa;
            --row-bg: #0f172a;
            --grn-row-bg: #1e293b;
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
        .nav-tab:hover { color: var(--text); background: var(--surface-alt); }
        .nav-tab.active {
            color: var(--primary);
            border-left-color: var(--primary);
            background: var(--surface-alt);
        }

        .main-wrapper { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; width: 100%; }

        .header { margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.75rem; font-weight: 700; color: var(--text); }
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
            background: var(--surface-alt);
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
            background-color: var(--surface);
            color: var(--text);
        }
        .filter-form input:focus, .filter-form select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
            background: var(--surface-alt); /* A slightly different shade for the header */
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
        .text-center { text-align: center; }
        .font-bold, .font-semibold { font-weight: 600; }
        .text-muted { color: var(--text-light); font-size: 0.85rem; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.025em; }
        .badge.green { background: #d1fae5; color: #065f46; }
        .badge.yellow { background: #fef3c7; color: #92400e; }
        .badge.blue { background: #dbeafe; color: #1e40af; }
        .badge.gray { background: #f1f5f9; color: #475569; }

        [data-theme="dark"] .badge.green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        [data-theme="dark"] .badge.yellow { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        [data-theme="dark"] .badge.blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        [data-theme="dark"] .badge.gray { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }

        /* Specific Table Rows */
        .grn-row td { background: var(--grn-row-bg); font-weight: 600; color: var(--text); padding-top: 1.25rem; padding-bottom: 1.25rem; border-bottom: 2px solid var(--border); }
        .item-row { transition: background-color 0.15s; }
        .item-row:hover { background: var(--row-bg); }
        .item-row td { font-size: 0.95rem; color: var(--text); }

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
        .page-link { padding: 0.5rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; color: var(--text); text-decoration: none; font-size: 0.875rem; transition: all 0.2s; display: block; background: var(--surface); }
        .page-link:hover { background: var(--surface-alt); border-color: var(--text-light); }
        .page-item.active .page-link { background: var(--primary); color: white; border-color: var(--primary); }
        .page-item.disabled .page-link { color: var(--text-light); background: var(--surface-alt); cursor: not-allowed; border-color: var(--border); }
        
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
            background-color: var(--surface);
            color: var(--text);
            /* Chevron arrow to make it look like a select */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat !important;
            background-position: right 0.75rem center !important;
            background-size: 16px 12px !important;
            cursor: pointer;
        }
        [data-theme="dark"] .autocomplete-input {
            background-color: var(--surface);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat !important;
            background-position: right 0.75rem center !important;
            background-size: 16px 12px !important;
        }
        .autocomplete-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--surface); border: 1px solid var(--border); border-top: none; border-radius: 0 0 6px 6px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); max-height: 250px; overflow-y: auto; z-index: 50; display: none; }
        .autocomplete-dropdown.show { display: block; }
        .autocomplete-option { padding: 0.5rem 1rem; cursor: pointer; font-size: 0.9rem; border-bottom: 1px solid var(--border); color: var(--text); }
        .autocomplete-option:last-child { border-bottom: none; }
        .autocomplete-option:hover { background: var(--surface-alt); color: var(--primary); }
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

        /* Theme Switcher */
        .theme-switcher {
            margin-top: auto;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            cursor: pointer;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        .theme-switcher:hover { color: var(--text); }
        .theme-switcher svg { width: 20px; height: 20px; }

        @yield('styles')
    </style>
    <script>
        // Init theme before render to prevent flash
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>
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
                By GRN
            </a>
            <a href="{{ route('stock.reports.grn-consumption-items.index') }}" 
               class="nav-tab {{ $currentRoute === 'stock.reports.grn-consumption-items.index' ? 'active' : '' }}">
                By Product
            </a>

            <!-- Theme Switcher -->
            <div class="theme-switcher" id="themeSwitcher">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
                <span id="themeText">Dark Mode</span>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="container">
            @yield('content')
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme Switcher Logic
            const themeSwitcher = document.getElementById('themeSwitcher');
            const themeText = document.getElementById('themeText');
            
            function updateThemeUI() {
                if (document.documentElement.getAttribute('data-theme') === 'dark') {
                    themeText.textContent = 'Light Mode';
                    themeSwitcher.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />';
                } else {
                    themeText.textContent = 'Dark Mode';
                    themeSwitcher.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />';
                }
            }
            
            updateThemeUI();

            themeSwitcher.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeUI();
            });

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
