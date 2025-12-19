@extends('task-proposals.layout')

@section('title', 'اقتراحات تطوير نظام المهام')

@section('content')
<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>📋 اقتراحات تطوير نظام المهام</h1>
        <p>خطة شاملة لتحويل نظام إدارة المهام إلى منصة تعاونية متكاملة</p>
    </div>
</section>

<!-- Stats Section -->
<section class="container">
    <div class="stats-grid">
        <div class="stat-card animate-fade-in delay-1">
            <div class="stat-number">{{ $statistics['total_proposals'] }}</div>
            <div class="stat-label">اقتراح تطويري</div>
        </div>
        <div class="stat-card animate-fade-in delay-2">
            <div class="stat-number">{{ $statistics['high_priority'] }}</div>
            <div class="stat-label">أولوية عالية</div>
        </div>
        <div class="stat-card animate-fade-in delay-3">
            <div class="stat-number">{{ $statistics['estimated_total_days'] }}</div>
            <div class="stat-label">للتنفيذ الكامل</div>
        </div>
        <div class="stat-card animate-fade-in delay-4">
            <div class="stat-number">4</div>
            <div class="stat-label">فئات رئيسية</div>
        </div>
    </div>
</section>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <div class="section-title">
            <h2>💡 الاقتراحات التطويرية</h2>
            <p>اضغط على أي اقتراح لمشاهدة التفاصيل الكاملة</p>
        </div>

        <div class="proposals-grid">
            @foreach($proposals as $index => $proposal)
            <a href="{{ route('tasks.proposals.show', $proposal['key']) }}"
                class="proposal-card animate-fade-in"
                style="animation-delay: {{ ($index * 0.1) }}s;">

                <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; 
                                    background: var(--{{ $proposal['color'] }}); opacity: 0.1; 
                                    border-radius: 0 20px 0 100px;"></div>

                <div class="proposal-header">
                    <div class="proposal-icon bg-{{ $proposal['color'] }}">
                        @switch($proposal['icon'])
                        @case('view-boards')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        @break
                        @case('lightning-bolt')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        @break
                        @case('template')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                        </svg>
                        @break
                        @case('bell')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @break
                        @case('clock')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        @break
                        @case('link')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        @break
                        @case('document-report')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        @break
                        @case('calendar')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        @break
                        @case('star')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        @break
                        @case('users')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        @break
                        @case('device-mobile')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        @break
                        @case('puzzle')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                        </svg>
                        @break
                        @default
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        @endswitch
                    </div>
                    <div class="proposal-info">
                        <h3>{{ $proposal['title'] }}</h3>
                        <span>{{ $proposal['title_en'] }}</span>
                    </div>
                </div>

                <p class="proposal-description">{{ $proposal['description'] }}</p>

                <div class="proposal-meta">
                    <span class="priority-badge priority-{{ $proposal['priority'] }}">
                        الأولوية: {{ $proposal['priority'] }}
                    </span>
                    <span class="effort-badge">
                        الجهد: {{ $proposal['effort'] }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>

        <!-- Call to Action -->
        <div style="text-align: center; margin-top: 60px;">
            <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); 
                            border-radius: 24px; padding: 48px; color: white; max-width: 800px; margin: 0 auto;">
                <h2 style="font-size: 1.75rem; margin-bottom: 16px;">🎯 هل ترغب في تنفيذ أي من هذه الاقتراحات؟</h2>
                <p style="opacity: 0.9; margin-bottom: 24px;">اختر الاقتراح الذي يناسب احتياجاتك وسنبدأ العمل عليه فوراً</p>
                <a href="{{ route('tasks.proposals.roadmap') }}"
                    style="display: inline-block; background: white; color: var(--primary); 
                              padding: 14px 32px; border-radius: 12px; font-weight: 700; 
                              text-decoration: none; transition: transform 0.3s ease;">
                    📋 عرض خارطة الطريق
                </a>
            </div>
        </div>
    </div>
</main>
@endsection