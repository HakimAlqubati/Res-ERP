<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $activeDoc['title'] }} - {{ __('docs.system_documentation') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap');
        
        :root {
            --primary: #14b8a6;
            --primary-light: #14b8a61a;
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --hover-bg: #334155;
            --shadow-color: rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Tajawal', system-ui, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 320px;
            background: var(--bg-sidebar);
            border-inline-end: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            inset-inline-start: 0;
            top: 0;
            bottom: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 15px rgba(0,0,0,0.02);
            z-index: 10;
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed {
            transform: translateX(100%);
        }
        html[dir="ltr"] .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
            background: linear-gradient(to bottom, var(--bg-sidebar), var(--bg-body));
        }
        
        .sidebar-header img {
            max-width: 140px;
            margin-bottom: 1.25rem;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));
        }
        
        .sidebar-header h2 {
            font-size: 1.35rem;
            color: var(--text-main);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .nav-list {
            list-style: none;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1.25rem;
            border-radius: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.25s ease;
        }

        .nav-link:hover {
            background-color: var(--hover-bg);
            color: var(--text-main);
        }

        .nav-link.active {
            background-color: var(--primary-light);
            color: var(--primary);
            border-right: 4px solid var(--primary);
        }
        
        .nav-link svg {
            width: 1.5rem;
            height: 1.5rem;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-inline-start: 320px;
            display: flex;
            flex-direction: column;
            transition: margin-inline-start 0.3s ease;
            background-color: var(--bg-body);
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-inline-start: 0;
        }

        .topbar {
            background-color: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 3.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            height: 70px;
        }

        .content-wrapper {
            padding: 3.5rem 3.5rem;
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        .toggle-btn {
            position: relative;
            background: var(--bg-sidebar);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            cursor: pointer;
            width: 2.5rem;
            height: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .toggle-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary);
            border-color: var(--primary);
        }
        .toggle-btn svg {
            position: absolute;
            width: 1.25rem;
            height: 1.25rem;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .icon-collapsed {
            opacity: 0;
            transform: rotate(-90deg) scale(0.5);
        }
        .icon-expanded {
            opacity: 1;
            transform: rotate(0) scale(1);
        }
        
        .sidebar.collapsed ~ .main-content .icon-collapsed {
            opacity: 1;
            transform: rotate(0) scale(1);
        }
        .sidebar.collapsed ~ .main-content .icon-expanded {
            opacity: 0;
            transform: rotate(90deg) scale(0.5);
        }

        .lang-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.15rem;
            background-color: var(--bg-sidebar);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .lang-btn svg {
            width: 1.25rem;
            height: 1.25rem;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        .lang-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary);
            border-color: var(--primary);
        }
        .lang-btn:hover svg {
            color: var(--primary);
        }

        .doc-header {
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            position: relative;
        }

        .doc-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            inset-inline-start: 0;
            width: 60px;
            height: 2px;
            background-color: var(--primary);
        }

        .doc-header h1 {
            font-size: 2.5rem;
            color: var(--text-main);
            font-weight: 800;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .doc-header h1 svg {
            width: 2.5rem;
            height: 2.5rem;
            color: var(--primary);
        }

        .doc-header p {
            color: var(--text-muted);
            font-size: 1.15rem;
            line-height: 1.8;
        }

        /* Accordion Styles */
        details.accordion {
            background: var(--bg-sidebar);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            margin-bottom: 1.25rem;
            overflow: hidden;
            transition: height 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        
        details.accordion[open] {
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            border-color: var(--primary);
        }

        details.accordion summary {
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.15rem;
            cursor: pointer;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--text-main);
            transition: background-color 0.2s;
            user-select: none;
        }
        
        details.accordion summary::-webkit-details-marker {
            display: none;
        }

        details.accordion summary:hover {
            background-color: var(--primary-light);
        }

        details.accordion summary::after {
            content: "+";
            font-size: 1.75rem;
            color: var(--primary);
            font-weight: 400;
            transition: transform 0.3s ease;
        }

        details.accordion[open] summary::after {
            content: "−";
            transform: rotate(180deg);
        }

        .accordion-content {
            padding: 0 1.5rem 1.5rem 1.5rem;
            color: var(--text-muted);
            line-height: 1.9;
            font-size: 1.05rem;
            border-top: 1px solid var(--border-color);
            margin-top: 0.25rem;
            padding-top: 1.5rem;
            transition: opacity 0.3s ease;
            opacity: 1;
        }
        
        .accordion-content p {
            margin-bottom: 1rem;
        }
        
        .accordion-content p:last-child {
            margin-bottom: 0;
        }
        
        code {
            background-color: var(--primary-light);
            padding: 0.2rem 0.5rem;
            border-radius: 0.375rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; border-inline-end: none; border-bottom: 1px solid var(--border-color); transform: none !important; }
            .sidebar.collapsed { display: none; }
            .sidebar-header img { max-width: 100px; }
            .nav-list { padding: 1rem; }
            .main-content { margin-inline-start: 0; }
            .topbar { padding: 1rem 1.5rem; }
            .content-wrapper { padding: 2rem 1.5rem; }
            .doc-header h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Layout -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="{{ asset('default.png') }}" alt="System Logo">
            <h2>{{ __('docs.system_documentation_guide') }}</h2>
        </div>
        <ul class="nav-list">
            @foreach($docs as $key => $doc)
            <li>
                <a href="{{ route('workbench.docs', ['section' => $key]) }}" class="nav-link {{ $currentSection === $key ? 'active' : '' }}">
                    {!! $doc['icon'] !!}
                    {{ $doc['title'] }}
                </a>
            </li>
            @endforeach
        </ul>
    </aside>

    <!-- Content Area -->
    <main class="main-content">
        <header class="topbar">
            <button id="toggle-sidebar" class="toggle-btn" aria-label="Toggle Sidebar">
                <svg class="icon-expanded" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                <svg class="icon-collapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>
            
            <div class="lang-switcher">
                @if(app()->getLocale() === 'ar')
                    <a href="{{ route('workbench.docs.lang', 'en') }}" class="lang-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        English
                    </a>
                @else
                    <a href="{{ route('workbench.docs.lang', 'ar') }}" class="lang-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        العربية
                    </a>
                @endif
            </div>
        </header>

        <div class="content-wrapper">
            <div class="doc-header">
                <h1>
                    {!! $activeDoc['icon'] !!}
                    {{ $activeDoc['title'] }}
                </h1>
                <p>{{ $activeDoc['description'] }}</p>
            </div>

            <div class="doc-body">
                @foreach($activeDoc['sections'] as $section)
                    <!-- Accordions utilizing native details/summary HTML5 tags -->
                    <details class="accordion" {{ $loop->first ? 'open' : '' }}>
                        <summary>{{ $section['title'] }}</summary>
                        <div class="accordion-content">
                            {!! $section['content'] !!}
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggle-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');

            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            });
            
            // On mobile, start collapsed by default
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }

            // Accordion exclusivity and smooth Fade/Slide animations
            const accordions = document.querySelectorAll('details.accordion');
            accordions.forEach(acc => {
                const summary = acc.querySelector('summary');
                const content = acc.querySelector('.accordion-content');
                
                summary.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent native snapping
                    
                    if (acc.classList.contains('is-animating')) return;
                    acc.classList.add('is-animating');
                    
                    if (acc.open) {
                        // Let's close it smoothly
                        content.style.opacity = '0';
                        acc.style.height = acc.offsetHeight + 'px';
                        
                        void acc.offsetHeight; // trigger reflow
                        
                        acc.style.height = summary.offsetHeight + 'px';
                        
                        setTimeout(() => {
                            acc.removeAttribute('open');
                            acc.style.height = '';
                            content.style.opacity = '';
                            acc.classList.remove('is-animating');
                        }, 300);
                        
                    } else {
                        // Let's auto-close others smoothly
                        accordions.forEach(other => {
                            if (other !== acc && other.open && !other.classList.contains('is-animating')) {
                                other.classList.add('is-animating');
                                const otherSummary = other.querySelector('summary');
                                const otherContent = other.querySelector('.accordion-content');
                                
                                otherContent.style.opacity = '0';
                                other.style.height = other.offsetHeight + 'px';
                                void other.offsetHeight;
                                other.style.height = otherSummary.offsetHeight + 'px';
                                
                                setTimeout(() => {
                                    other.removeAttribute('open');
                                    other.style.height = '';
                                    otherContent.style.opacity = '';
                                    other.classList.remove('is-animating');
                                }, 300);
                            }
                        });
                        
                        // Let's open the requested one smoothly
                        acc.setAttribute('open', '');
                        acc.style.height = summary.offsetHeight + 'px';
                        content.style.opacity = '0';
                        
                        const fullHeight = summary.offsetHeight + content.offsetHeight;
                        void acc.offsetHeight; // force reflow
                        
                        acc.style.height = fullHeight + 'px';
                        content.style.opacity = '1';
                        
                        setTimeout(() => {
                            acc.style.height = '';
                            acc.classList.remove('is-animating');
                        }, 300);
                    }
                });
            });
        });
    </script>
</body>
</html>
