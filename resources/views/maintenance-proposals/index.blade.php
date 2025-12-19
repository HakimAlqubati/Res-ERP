@extends('maintenance-proposals.layout')

@section('title', 'اقتراحات تطوير نظام الصيانة')

@section('content')
<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>🚀 اقتراحات تطوير نظام الصيانة</h1>
        <p>خطة شاملة لتحويل نظام طلبات الخدمة والمعدات إلى نظام متكامل وذكي</p>
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
            <div class="stat-label">يوم للتنفيذ الكامل</div>
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
            <a href="{{ route('maintenance.proposals.show', $proposal['key']) }}"
                class="proposal-card animate-fade-in"
                style="animation-delay: {{ ($index * 0.1) }}s;">

                <div class="proposal-card" style="background: transparent; box-shadow: none; padding: 0;">
                    {{-- Colored corner --}}
                    <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; 
                                        background: var(--{{ $proposal['color'] }}); opacity: 0.1; 
                                        border-radius: 0 20px 0 100px;"></div>

                    <div class="proposal-header">
                        <div class="proposal-icon bg-{{ $proposal['color'] }}">
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
                <a href="{{ route('maintenance.proposals.roadmap') }}"
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