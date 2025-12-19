@extends('maintenance-proposals.layout')

@section('title', 'خارطة طريق التطوير - نظام الصيانة')

@section('content')
<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>📋 خارطة طريق التطوير</h1>
        <p>خطة مرتبة لتنفيذ الاقتراحات التطويرية حسب الأولوية والجهد المطلوب</p>
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
            <div class="stat-number">{{ count($groupedByEffort['منخفض'] ?? []) }}</div>
            <div class="stat-label">جهد منخفض</div>
        </div>
        <div class="stat-card animate-fade-in delay-3">
            <div class="stat-number">{{ count($groupedByEffort['متوسط'] ?? []) }}</div>
            <div class="stat-label">جهد متوسط</div>
        </div>
        <div class="stat-card animate-fade-in delay-4">
            <div class="stat-number">{{ count($groupedByEffort['عالي'] ?? []) + count($groupedByEffort['عالي جداً'] ?? []) }}</div>
            <div class="stat-label">جهد عالي</div>
        </div>
    </div>
</section>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <!-- Timeline by Effort -->
        <div class="section-title">
            <h2>🎯 خطة التنفيذ حسب الجهد</h2>
            <p>ابدأ بالمهام ذات الجهد المنخفض ثم انتقل تدريجياً للمهام الأكبر</p>
        </div>

        <!-- Phase 1: Low Effort -->
        @if(count($groupedByEffort['منخفض'] ?? []) > 0)
        <div class="section-card animate-fade-in" style="border-right: 4px solid #22c55e;">
            <h3 style="color: #22c55e;">
                <span style="font-size: 1.5rem;">🟢</span>
                المرحلة الأولى: جهد منخفض (1-5 أيام لكل مهمة)
            </h3>
            <div class="features-grid" style="margin-top: 20px;">
                @foreach($groupedByEffort['منخفض'] as $proposal)
                <a href="{{ route('maintenance.proposals.show', $proposal['key']) }}" class="feature-card" style="text-decoration: none; color: inherit;">
                    <div class="feature-icon bg-{{ $proposal['color'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: white;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h4>{{ $proposal['title'] }}</h4>
                        <p>{{ $proposal['description'] }}</p>
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <span class="priority-badge priority-{{ $proposal['priority'] }}">
                                الأولوية: {{ $proposal['priority'] }}
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Phase 2: Medium Effort -->
        @if(count($groupedByEffort['متوسط'] ?? []) > 0)
        <div class="section-card animate-fade-in" style="border-right: 4px solid #eab308; animation-delay: 0.1s;">
            <h3 style="color: #eab308;">
                <span style="font-size: 1.5rem;">🟡</span>
                المرحلة الثانية: جهد متوسط (5-10 أيام لكل مهمة)
            </h3>
            <div class="features-grid" style="margin-top: 20px;">
                @foreach($groupedByEffort['متوسط'] as $proposal)
                <a href="{{ route('maintenance.proposals.show', $proposal['key']) }}" class="feature-card" style="text-decoration: none; color: inherit;">
                    <div class="feature-icon bg-{{ $proposal['color'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: white;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h4>{{ $proposal['title'] }}</h4>
                        <p>{{ $proposal['description'] }}</p>
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <span class="priority-badge priority-{{ $proposal['priority'] }}">
                                الأولوية: {{ $proposal['priority'] }}
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Phase 3: High Effort -->
        @if(count($groupedByEffort['عالي'] ?? []) > 0)
        <div class="section-card animate-fade-in" style="border-right: 4px solid #f97316; animation-delay: 0.2s;">
            <h3 style="color: #f97316;">
                <span style="font-size: 1.5rem;">🟠</span>
                المرحلة الثالثة: جهد عالي (10-21 يوم لكل مهمة)
            </h3>
            <div class="features-grid" style="margin-top: 20px;">
                @foreach($groupedByEffort['عالي'] as $proposal)
                <a href="{{ route('maintenance.proposals.show', $proposal['key']) }}" class="feature-card" style="text-decoration: none; color: inherit;">
                    <div class="feature-icon bg-{{ $proposal['color'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: white;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h4>{{ $proposal['title'] }}</h4>
                        <p>{{ $proposal['description'] }}</p>
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <span class="priority-badge priority-{{ $proposal['priority'] }}">
                                الأولوية: {{ $proposal['priority'] }}
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Phase 4: Very High Effort -->
        @if(count($groupedByEffort['عالي جداً'] ?? []) > 0)
        <div class="section-card animate-fade-in" style="border-right: 4px solid #ef4444; animation-delay: 0.3s;">
            <h3 style="color: #ef4444;">
                <span style="font-size: 1.5rem;">🔴</span>
                المرحلة الرابعة: جهد عالي جداً (30+ يوم لكل مهمة)
            </h3>
            <div class="features-grid" style="margin-top: 20px;">
                @foreach($groupedByEffort['عالي جداً'] as $proposal)
                <a href="{{ route('maintenance.proposals.show', $proposal['key']) }}" class="feature-card" style="text-decoration: none; color: inherit;">
                    <div class="feature-icon bg-{{ $proposal['color'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: white;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h4>{{ $proposal['title'] }}</h4>
                        <p>{{ $proposal['description'] }}</p>
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <span class="priority-badge priority-{{ $proposal['priority'] }}">
                                الأولوية: {{ $proposal['priority'] }}
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Phase 5: Varied Effort -->
        @if(count($groupedByEffort['متنوع'] ?? []) > 0)
        <div class="section-card animate-fade-in" style="border-right: 4px solid #6b7280; animation-delay: 0.4s;">
            <h3 style="color: #6b7280;">
                <span style="font-size: 1.5rem;">⚫</span>
                مهام متنوعة (يمكن تنفيذها على مراحل)
            </h3>
            <div class="features-grid" style="margin-top: 20px;">
                @foreach($groupedByEffort['متنوع'] as $proposal)
                <a href="{{ route('maintenance.proposals.show', $proposal['key']) }}" class="feature-card" style="text-decoration: none; color: inherit;">
                    <div class="feature-icon bg-{{ $proposal['color'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: white;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h4>{{ $proposal['title'] }}</h4>
                        <p>{{ $proposal['description'] }}</p>
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <span class="priority-badge priority-{{ $proposal['priority'] }}">
                                الأولوية: {{ $proposal['priority'] }}
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Priority Table -->
        <div class="section-card animate-fade-in" style="animation-delay: 0.5s;">
            <h3>
                <span style="font-size: 1.5rem;">📊</span>
                جدول الأولويات الكامل
            </h3>
            <div class="table-card">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>الأولوية</th>
                            <th>الاقتراح</th>
                            <th>الجهد</th>
                            <th>الوصف</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $sortedProposals = collect($proposals)->sortBy('priority')->values();
                        @endphp
                        @foreach($sortedProposals as $proposal)
                        <tr>
                            <td>
                                <span class="priority-badge priority-{{ $proposal['priority'] }}">
                                    {{ $proposal['priority'] }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $proposal['title'] }}</strong>
                                <br>
                                <small style="color: var(--gray);">{{ $proposal['title_en'] }}</small>
                            </td>
                            <td>
                                <span class="effort-badge">{{ $proposal['effort'] }}</span>
                            </td>
                            <td style="max-width: 300px;">{{ $proposal['description'] }}</td>
                            <td>
                                <a href="{{ route('maintenance.proposals.show', $proposal['key']) }}"
                                    style="display: inline-block; padding: 8px 16px; background: var(--primary); 
                                              color: white; border-radius: 8px; text-decoration: none; 
                                              font-size: 0.875rem; font-weight: 600;">
                                    التفاصيل
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Call to Action -->
        <div style="text-align: center; margin-top: 60px;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); 
                            border-radius: 24px; padding: 48px; color: white; max-width: 800px; margin: 0 auto;">
                <h2 style="font-size: 1.75rem; margin-bottom: 16px;">🚀 جاهز للبدء؟</h2>
                <p style="opacity: 0.9; margin-bottom: 24px;">اختر الاقتراح الذي تريد تنفيذه وسنبدأ العمل عليه فوراً!</p>
                <a href="{{ route('maintenance.proposals.index') }}"
                    style="display: inline-block; background: var(--primary); color: white; 
                              padding: 14px 32px; border-radius: 12px; font-weight: 700; 
                              text-decoration: none; transition: transform 0.3s ease;">
                    🔙 العودة للاقتراحات
                </a>
            </div>
        </div>
    </div>
</main>
@endsection