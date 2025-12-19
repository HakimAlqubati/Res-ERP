@extends('task-proposals.layout')

@section('title', $proposal['title'] . ' - اقتراحات التطوير')

@section('content')
<!-- Hero Section -->
<section class="hero" style="padding: 40px 0;">
    <div class="container">
        <a href="{{ route('tasks.proposals.index') }}" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            العودة للاقتراحات
        </a>
    </div>
</section>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <div class="two-column">
            <!-- Main Content Area -->
            <div>
                <!-- Header Card -->
                <div class="detail-header animate-fade-in">
                    <div class="detail-header-content">
                        <div class="detail-icon bg-{{ $proposal['color'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="detail-info">
                            <h1>{{ $proposal['title'] }}</h1>
                            <p>{{ $proposal['description'] }}</p>
                            <div class="detail-badges">
                                <div class="detail-badge">
                                    <span style="color: var(--primary);">📊</span>
                                    الأولوية: {{ $proposal['priority'] }}
                                </div>
                                <div class="detail-badge">
                                    <span style="color: var(--secondary);">⏱️</span>
                                    الجهد: {{ $proposal['effort'] }}
                                </div>
                                @if(isset($proposal['estimated_days']))
                                <div class="detail-badge">
                                    <span style="color: var(--accent);">📅</span>
                                    المدة: {{ $proposal['estimated_days'] }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                @if(isset($proposal['features']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.1s;">
                    <h3>
                        <span style="font-size: 1.5rem;">✨</span>
                        المميزات الرئيسية
                    </h3>
                    <div class="features-grid">
                        @foreach($proposal['features'] as $feature)
                        <div class="feature-card">
                            <div class="feature-icon" style="background: var(--light); color: var(--{{ $proposal['color'] }});">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="feature-content">
                                <h4>{{ $feature['title'] }}</h4>
                                <p>{{ $feature['description'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Automation Rules (for automation proposal) -->
                @if(isset($proposal['automation_rules']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.2s;">
                    <h3>
                        <span style="font-size: 1.5rem;">⚡</span>
                        أمثلة على قواعد الأتمتة
                    </h3>
                    <div class="table-card">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>الحدث (Trigger)</th>
                                    <th>الشرط (Condition)</th>
                                    <th>الإجراء (Action)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proposal['automation_rules'] as $rule)
                                <tr>
                                    <td>{{ $rule['trigger'] }}</td>
                                    <td>{{ $rule['condition'] }}</td>
                                    <td><strong>{{ $rule['action'] }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Reports (for reports proposal) -->
                @if(isset($proposal['reports']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.2s;">
                    <h3>
                        <span style="font-size: 1.5rem;">📊</span>
                        التقارير المقترحة
                    </h3>
                    <div class="table-card">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>التقرير</th>
                                    <th>الاسم بالعربية</th>
                                    <th>الوصف</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proposal['reports'] as $report)
                                <tr>
                                    <td><strong>{{ $report['name'] }}</strong></td>
                                    <td>{{ $report['name_ar'] }}</td>
                                    <td>{{ $report['description'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Point System (for gamification proposal) -->
                @if(isset($proposal['point_system']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.2s;">
                    <h3>
                        <span style="font-size: 1.5rem;">⭐</span>
                        نظام النقاط
                    </h3>
                    <ul class="list-styled">
                        @foreach($proposal['point_system'] as $point)
                        <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Badges (for gamification proposal) -->
                @if(isset($proposal['badges']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.25s;">
                    <h3>
                        <span style="font-size: 1.5rem;">🏆</span>
                        الشارات
                    </h3>
                    <ul class="list-styled">
                        @foreach($proposal['badges'] as $badge)
                        <li>{{ $badge }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Integrations (for integration proposal) -->
                @if(isset($proposal['integrations']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.2s;">
                    <h3>
                        <span style="font-size: 1.5rem;">🔗</span>
                        التكاملات المقترحة
                    </h3>
                    <div class="features-grid">
                        @foreach($proposal['integrations'] as $integration)
                        <div class="feature-card">
                            <div class="feature-icon" style="background: var(--light); color: var(--gray-color);">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <div class="feature-content">
                                <h4>{{ $integration['system'] }}</h4>
                                <p>{{ $integration['description'] }}</p>
                                <span style="display: inline-block; background: #dcfce7; color: #166534; 
                                                padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; margin-top: 8px;">
                                    {{ $integration['benefits'] }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Technologies Section -->
                @if(isset($proposal['technologies']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.3s;">
                    <h3>
                        <span style="font-size: 1.5rem;">🛠️</span>
                        التقنيات المستخدمة
                    </h3>
                    <ul class="list-styled">
                        @foreach($proposal['technologies'] as $tech)
                        <li>{{ $tech }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Notification Channels -->
                @if(isset($proposal['notification_channels']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.3s;">
                    <h3>
                        <span style="font-size: 1.5rem;">📲</span>
                        قنوات الإشعارات
                    </h3>
                    <ul class="list-styled">
                        @foreach($proposal['notification_channels'] as $channel)
                        <li>{{ $channel }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Database Changes Section -->
                @if(isset($proposal['database_changes']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.35s;">
                    <h3>
                        <span style="font-size: 1.5rem;">🗄️</span>
                        التغييرات في قاعدة البيانات
                    </h3>
                    <div class="table-card">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>الجدول</th>
                                    <th>الحقول</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proposal['database_changes'] as $change)
                                <tr>
                                    <td><code style="background: var(--light); padding: 4px 8px; border-radius: 6px;">{{ $change['table'] }}</code></td>
                                    <td>
                                        @foreach($change['fields'] as $field)
                                        <span style="display: inline-block; background: #e0f2fe; color: #0369a1; 
                                                        padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; 
                                                        margin: 2px;">{{ $field }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Implementation Steps Section -->
                @if(isset($proposal['implementation_steps']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.4s;">
                    <h3>
                        <span style="font-size: 1.5rem;">📋</span>
                        خطوات التنفيذ
                    </h3>
                    <ol style="list-style: none; counter-reset: step;">
                        @foreach($proposal['implementation_steps'] as $index => $step)
                        <li style="display: flex; align-items: center; gap: 16px; padding: 16px 0; 
                                       border-bottom: 1px solid var(--border);">
                            <span style="width: 32px; height: 32px; background: var(--{{ $proposal['color'] }}); 
                                            color: white; border-radius: 50%; display: flex; align-items: center; 
                                            justify-content: center; font-weight: 700; flex-shrink: 0;">
                                {{ $index + 1 }}
                            </span>
                            <span>{{ $step }}</span>
                        </li>
                        @endforeach
                    </ol>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div>
                <div class="sidebar-nav">
                    <h4>📌 الاقتراحات الأخرى</h4>
                    @foreach($allProposals as $prop)
                    <a href="{{ route('tasks.proposals.show', $prop['key']) }}"
                        class="sidebar-link {{ $prop['key'] === $key ? 'active' : '' }}">
                        <span class="sidebar-link-dot bg-{{ $prop['color'] }}"></span>
                        {{ $prop['title'] }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</main>
@endsection