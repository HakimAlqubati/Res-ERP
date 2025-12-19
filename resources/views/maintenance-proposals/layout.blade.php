<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'اقتراحات تطوير نظام الصيانة')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Heroicons (via CDN) -->
    <script src="https://unpkg.com/@heroicons/v2.0.18/24/outline/esm/index.js" type="module"></script>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --accent: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --border: #e2e8f0;

            /* Color palette for cards */
            --blue: #3b82f6;
            --green: #22c55e;
            --yellow: #eab308;
            --purple: #a855f7;
            --indigo: #6366f1;
            --orange: #f97316;
            --teal: #14b8a6;
            --red: #ef4444;
            --amber: #f59e0b;
            --cyan: #06b6d4;
            --pink: #ec4899;
            --gray-color: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark);
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .logo-text h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }

        .logo-text span {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .nav-links {
            display: flex;
            gap: 8px;
        }

        .nav-link {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--primary);
            color: white;
        }

        /* Hero Section */
        .hero {
            padding: 60px 0;
            text-align: center;
            color: white;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 16px;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: -40px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.875rem;
            margin-top: 4px;
        }

        /* Main Content */
        .main-content {
            background: var(--light);
            border-radius: 32px 32px 0 0;
            padding: 60px 0;
            min-height: 100vh;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .section-title p {
            color: var(--gray);
        }

        /* Proposal Cards */
        .proposals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
        }

        .proposal-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .proposal-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            border-radius: 0 20px 0 100px;
            opacity: 0.1;
            transition: all 0.3s ease;
        }

        .proposal-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .proposal-card:hover::before {
            width: 150px;
            height: 150px;
        }

        .proposal-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .proposal-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .proposal-icon svg {
            width: 28px;
            height: 28px;
            color: white;
        }

        .proposal-info h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .proposal-info span {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .proposal-description {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .proposal-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .effort-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--light);
            color: var(--gray);
        }

        /* Color utilities */
        .bg-blue {
            background: var(--blue);
        }

        .bg-green {
            background: var(--green);
        }

        .bg-yellow {
            background: var(--yellow);
        }

        .bg-purple {
            background: var(--purple);
        }

        .bg-indigo {
            background: var(--indigo);
        }

        .bg-orange {
            background: var(--orange);
        }

        .bg-teal {
            background: var(--teal);
        }

        .bg-red {
            background: var(--red);
        }

        .bg-amber {
            background: var(--amber);
        }

        .bg-cyan {
            background: var(--cyan);
        }

        .bg-pink {
            background: var(--pink);
        }

        .bg-gray {
            background: var(--gray-color);
        }

        /* Priority colors */
        .priority-1,
        .priority-2,
        .priority-3 {
            background: #dcfce7;
            color: #166534;
        }

        .priority-4,
        .priority-5,
        .priority-6 {
            background: #fef9c3;
            color: #854d0e;
        }

        .priority-7,
        .priority-8,
        .priority-9 {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-10,
        .priority-11,
        .priority-12 {
            background: #f3e8ff;
            color: #6b21a8;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        .footer p {
            opacity: 0.7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .proposals-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 16px;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        .delay-4 {
            animation-delay: 0.4s;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 24px;
        }

        .back-btn:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Detail Page Styles */
        .detail-header {
            background: white;
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .detail-header-content {
            display: flex;
            align-items: flex-start;
            gap: 24px;
        }

        .detail-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .detail-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        .detail-info h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .detail-info p {
            color: var(--gray);
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .detail-badges {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .detail-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--light);
            border-radius: 12px;
            font-size: 0.875rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 24px;
            height: 24px;
        }

        .feature-content h4 {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .feature-content p {
            color: var(--gray);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .section-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .section-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .list-styled {
            list-style: none;
        }

        .list-styled li {
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .list-styled li:last-child {
            border-bottom: none;
        }

        .list-styled li::before {
            content: '✓';
            width: 24px;
            height: 24px;
            background: #dcfce7;
            color: #166534;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        .table-card {
            overflow-x: auto;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
        }

        .styled-table th,
        .styled-table td {
            padding: 14px 16px;
            text-align: right;
            border-bottom: 1px solid var(--border);
        }

        .styled-table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        .styled-table tr:hover {
            background: #f8fafc;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            background: white;
            border-radius: 16px;
            padding: 20px;
            position: sticky;
            top: 100px;
        }

        .sidebar-nav h4 {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--primary);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--gray);
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: var(--light);
            color: var(--primary);
        }

        .sidebar-link-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        /* Two column layout */
        .two-column {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 32px;
        }

        @media (max-width: 1024px) {
            .two-column {
                grid-template-columns: 1fr;
            }

            .sidebar-nav {
                display: none;
            }
        }
    </style>
    @stack('styles')
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="{{ route('maintenance.proposals.index') }}" class="logo">
                    <div class="logo-icon">🔧</div>
                    <div class="logo-text">
                        <h1>نظام الصيانة</h1>
                        <span>Maintenance System</span>
                    </div>
                </a>
                <nav class="nav-links">
                    <a href="{{ route('maintenance.proposals.index') }}" class="nav-link {{ request()->routeIs('maintenance.proposals.index') ? 'active' : '' }}">
                        الاقتراحات
                    </a>
                    <a href="{{ route('maintenance.proposals.roadmap') }}" class="nav-link {{ request()->routeIs('maintenance.proposals.roadmap') ? 'active' : '' }}">
                        خارطة الطريق
                    </a>
                    <a href="{{ url('/admin') }}" class="nav-link">
                        لوحة التحكم
                    </a>
                </nav>
            </div>
        </div>
    </header>

    @yield('content')

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© {{ date('Y') }} - نظام إدارة طلبات الخدمة والمعدات | Res-ERP</p>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>