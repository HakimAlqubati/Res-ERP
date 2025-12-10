<x-mail::message>
    # {{ $title }}

    {{ __('Hello') }} {{ $userName }},

    @php
    $levelColors = [
    'critical' => '#dc2626',
    'warning' => '#f59e0b',
    'info' => '#3b82f6',
    ];
    $levelColor = $levelColors[$level] ?? '#6b7280';
    @endphp

    <div style="border-left: 4px solid {{ $levelColor }}; padding-left: 16px; margin: 20px 0;">
        {{ $body }}
    </div>

    @if(!empty($context['employees']))
    ## {{ __('Employees Details') }}

    @foreach($context['employees'] as $empData)
    @php
    $emp = $empData['employee'] ?? [];
    $periods = $empData['periods'] ?? [];
    @endphp

    **{{ $emp['name'] ?? 'N/A' }}** ({{ $emp['employee_no'] ?? '' }})
    @if(!empty($emp['branch']))
    - {{ __('Branch') }}: {{ $emp['branch'] }}
    @endif
    @if(!empty($periods))
    - {{ __('Missed Periods') }}: {{ count($periods) }}
    @endif

    @endforeach
    @endif

    @if(!empty($context['products']))
    ## {{ __('Products') }}

    | {{ __('Product') }} | {{ __('Current') }} | {{ __('Minimum') }} |
    |:---|:---:|:---:|
    @foreach($context['products'] as $product)
    | {{ $product['name'] ?? $product['id'] ?? 'N/A' }} | {{ $product['remaining'] ?? '-' }} | {{ $product['min'] ?? '-' }} |
    @endforeach
    @endif

    @if($url)
    <x-mail::button :url="$url" color="primary">
        {{ __('View Details') }}
    </x-mail::button>
    @endif

    {{ __('Thanks') }},<br>
    {{ config('app.name') }}
</x-mail::message>