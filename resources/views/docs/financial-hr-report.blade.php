<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $meta['title'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d7c66;
            --primary-light: #10a37f;
            --primary-dark: #095c4c;
            --bg-dark: #0a1f1c;
            --bg-card: rgba(13, 124, 102, 0.08);
            --text-light: #e0e0e0;
            --text-muted: #94a3b8;
            --success: #4ade80;
            --danger: #f87171;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f2922 50%, #1a3d35 100%);
            min-height: 100vh;
            color: var(--text-light);
        }

        /* Fixed Header */
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f2922 100%);
            border-bottom: 1px solid rgba(13, 124, 102, 0.2);
            padding: 20px 20px 0;
        }

        .fixed-header .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 1rem;
            color: var(--text-muted);
        }

        /* Tabs in Fixed Header */
        .tabs {
            display: flex;
            gap: 10px;
            padding-bottom: 0;
        }

        .tab-btn {
            flex: 1;
            padding: 14px 20px;
            background: rgba(13, 124, 102, 0.1);
            border: 1px solid rgba(13, 124, 102, 0.2);
            border-bottom: none;
            border-radius: 12px 12px 0 0;
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover {
            background: rgba(13, 124, 102, 0.2);
        }

        .tab-btn.active {
            background: var(--bg-card);
            color: #fff;
            border-color: rgba(13, 124, 102, 0.3);
        }

        .tab-btn.active.success {
            border-top: 3px solid var(--success);
        }

        .tab-btn.active.danger {
            border-top: 3px solid var(--danger);
        }

        .tab-btn.active.primary {
            border-top: 3px solid var(--primary-light);
        }

        .tab-btn .icon {
            font-size: 1rem;
        }

        .count-badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        /* Main Content with padding for fixed header */
        .main-content {
            padding: 200px 20px 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Tab Content */
        .tab-content {
            display: none;
            background: var(--bg-card);
            border: 1px solid rgba(13, 124, 102, 0.2);
            border-radius: 0 0 20px 20px;
            padding: 30px;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: right;
            padding: 15px;
            background: rgba(13, 124, 102, 0.15);
            font-weight: 600;
            color: #fff;
        }

        th:first-child {
            border-radius: 0 8px 0 0;
        }

        th:last-child {
            border-radius: 8px 0 0 0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(13, 124, 102, 0.1);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(13, 124, 102, 0.08);
        }

        .item-name {
            font-weight: 500;
            color: #fff;
        }

        .item-reason {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .rule-box {
            background: linear-gradient(135deg, rgba(13, 124, 102, 0.25) 0%, rgba(9, 92, 76, 0.25) 100%);
            border: 1px solid rgba(13, 124, 102, 0.4);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            margin-top: 30px;
        }

        .rule-box h3 {
            font-size: 1.2rem;
            color: var(--primary-light);
            margin-bottom: 10px;
        }

        .rule-box p {
            font-size: 1.1rem;
            color: #fff;
            line-height: 1.8;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(13, 124, 102, 0.2);
            color: #64748b;
            font-size: 0.9rem;
        }

        .footer-logo {
            width: 40px;
            height: auto;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-light);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
            margin-bottom: 15px;
        }

        .back-link:hover {
            color: var(--success);
        }

        @media (max-width: 640px) {
            .fixed-header {
                padding: 15px 15px 0;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .main-content {
                padding-top: 250px;
            }

            .tabs {
                flex-direction: column;
                gap: 5px;
            }

            .tab-btn {
                border-radius: 10px;
                border: 1px solid rgba(13, 124, 102, 0.2);
                padding: 12px;
                font-size: 0.9rem;
            }

            .tab-content {
                border-radius: 12px;
                margin-top: 10px;
                padding: 20px;
            }

            th,
            td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>

    {{-- Fixed Header with Tabs --}}
    <div class="fixed-header">
        <div class="container">
            <a href="{{ url('/admin') }}" class="back-link">→ {{ $labels['backLink'] }}</a>

            <div class="header">
                <h1>{{ $meta['title'] }}</h1>
                <p>{{ $meta['description'] }}</p>
            </div>

            <div class="tabs">
                <button class="tab-btn success active" onclick="showTab('required')">
                    <span class="icon">✓</span>
                    {{ $labels['requiredTitle'] }}
                    <span class="count-badge">{{ count($requiredItems) }}</span>
                </button>
                <button class="tab-btn danger" onclick="showTab('notRequired')">
                    <span class="icon">✗</span>
                    {{ $labels['notRequiredTitle'] }}
                    <span class="count-badge">{{ count($notRequiredItems) }}</span>
                </button>
                <button class="tab-btn primary" onclick="showTab('categories')">
                    <span class="icon">📂</span>
                    {{ $labels['categoriesTitle'] }}
                    <span class="count-badge">{{ count($financialCategories) }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="main-content">

        {{-- Tab: Required Items --}}
        <div id="required" class="tab-content active">
            <table>
                <thead>
                    <tr>
                        <th>{{ $labels['itemColumn'] }}</th>
                        <th>{{ $labels['reasonColumn'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requiredItems as $item)
                    <tr>
                        <td class="item-name">{{ $item['name'] }}</td>
                        <td class="item-reason">{{ $item['reason'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tab: Not Required Items --}}
        <div id="notRequired" class="tab-content">
            <table>
                <thead>
                    <tr>
                        <th>{{ $labels['itemColumn'] }}</th>
                        <th>{{ $labels['reasonColumn'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notRequiredItems as $item)
                    <tr>
                        <td class="item-name">{{ $item['name'] }}</td>
                        <td class="item-reason">{{ $item['reason'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tab: Financial Categories --}}
        <div id="categories" class="tab-content">
            <table>
                <thead>
                    <tr>
                        <th>{{ $labels['itemColumn'] }}</th>
                        <th>{{ $labels['typeColumn'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($financialCategories as $category)
                    <tr>
                        <td class="item-name">{{ $category['name'] }}</td>
                        <td class="item-reason">{{ $category['type'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Golden Rule --}}
        <div class="rule-box">
            <h3>📌 {{ $labels['goldenRuleTitle'] }}</h3>
            <p>{{ $goldenRule }}</p>
        </div>

        <div class="footer">
            <img src="{{ asset('workbench.png') }}" alt="Logo" class="footer-logo">
            <div>{{ $meta['copyright'] }} © {{ date('Y') }} | {{ $meta['brand'] }}</div>
        </div>

    </div>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>

</html>