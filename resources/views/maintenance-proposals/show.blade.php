@extends('maintenance-proposals.layout')

@section('title', $proposal['title'] . ' - اقتراحات التطوير')

@section('content')
<!-- Hero Section -->
<section class="hero" style="padding: 40px 0;">
    <div class="container">
        <a href="{{ route('maintenance.proposals.index') }}" class="back-btn">
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
                            @switch($proposal['icon'])
                            @case('bell')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @break
                            @case('chart-bar')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            @break
                            @case('clock')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @break
                            @case('cpu')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                            @break
                            @case('device-mobile')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            @break
                            @case('cog')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            @break
                            @case('document-report')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            @break
                            @case('adjustments')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            @break
                            @case('star')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            @break
                            @case('book-open')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            @break
                            @case('wifi')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                            </svg>
                            @break
                            @case('code')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            @break
                            @default
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            @endswitch
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
                                    المدة المتوقعة: {{ $proposal['estimated_days'] }}
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

                <!-- Reports Section (for reports proposal) -->
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

                <!-- SLA Definitions (for SLA proposal) -->
                @if(isset($proposal['sla_definitions']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.2s;">
                    <h3>
                        <span style="font-size: 1.5rem;">⏰</span>
                        تعريفات SLA
                    </h3>
                    <div class="table-card">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>الأولوية</th>
                                    <th>وقت الاستجابة</th>
                                    <th>وقت الحل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proposal['sla_definitions'] as $sla)
                                <tr>
                                    <td>
                                        <span style="padding: 4px 12px; border-radius: 12px; 
                                                        background: var(--{{ $sla['color'] }}); color: white; 
                                                        font-weight: 600; font-size: 0.875rem;">
                                            {{ $sla['priority'] }}
                                        </span>
                                    </td>
                                    <td>{{ $sla['response'] }}</td>
                                    <td>{{ $sla['resolution'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Technologies Section -->
                @if(isset($proposal['technologies']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.2s;">
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

                <!-- Database Changes Section -->
                @if(isset($proposal['database_changes']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.3s;">
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

                <!-- Technical Improvements (special for technical proposal) -->
                @if(isset($proposal['improvements']))
                <div class="section-card animate-fade-in" style="animation-delay: 0.2s;">
                    <h3>
                        <span style="font-size: 1.5rem;">⚙️</span>
                        التحسينات التقنية
                    </h3>
                    <div class="features-grid">
                        @foreach($proposal['improvements'] as $improvement)
                        <div class="feature-card">
                            <div class="feature-icon" style="background: var(--light); color: var(--gray-color);">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="feature-content">
                                <h4>{{ $improvement['title'] }}</h4>
                                <p>{{ $improvement['description'] }}</p>
                                <span style="display: inline-block; background: var(--light); padding: 4px 12px; 
                                                border-radius: 8px; font-size: 0.75rem; margin-top: 8px;">
                                    الجهد: {{ $improvement['effort'] }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div>
                <div class="sidebar-nav">
                    <h4>📌 الاقتراحات الأخرى</h4>
                    @foreach($allProposals as $prop)
                    <a href="{{ route('maintenance.proposals.show', $prop['key']) }}"
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