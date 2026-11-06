@if (isset($data))
    <script>
        window.filamentData = @js($data)
    </script>
@endif

@foreach ($assets as $asset)
    @if (! $asset->isLoadedOnRequest())
        {{ $asset->getHtml() }}
    @endif
@endforeach

<style>
    :root {
        @foreach ($cssVariables ?? [] as $cssVariableName => $cssVariableValue) --{{ $cssVariableName }}:{{ $cssVariableValue }}; @endforeach
    }

    @foreach ($customColors ?? [] as $customColorName => $customColorShades) .fi-color-{{ $customColorName }} { @foreach ($customColorShades as $customColorShade) --color-{{ $customColorShade }}:var(--{{ $customColorName }}-{{ $customColorShade }}); @endforeach } @endforeach

    .fi-input-wrp:has(input[readonly]),
    .fi-input-wrp:has(input[disabled]),
    .fi-input-wrp.fi-disabled,
    .fi-input-wrp.bg-readonly-gray {
        background-color: #e5e7eb !important;
        border-color: #cbd5e1 !important;
        cursor: not-allowed !important;
    }
    .fi-input-wrp:has(input[readonly]) input,
    .fi-input-wrp:has(input[disabled]) input,
    .fi-input-wrp.fi-disabled input,
    .fi-input-wrp.bg-readonly-gray input {
        background-color: #e5e7eb !important;
        cursor: not-allowed !important;
        color: #374151 !important;
    }
    .fi-input-wrp:has(input[readonly]) .fi-input-wrp-prefix,
    .fi-input-wrp:has(input[disabled]) .fi-input-wrp-prefix,
    .fi-input-wrp.fi-disabled .fi-input-wrp-prefix,
    .fi-input-wrp.bg-readonly-gray .fi-input-wrp-prefix {
        background-color: #e5e7eb !important;
        color: #6b7280 !important;
    }
    .dark .fi-input-wrp:has(input[readonly]),
    .dark .fi-input-wrp:has(input[disabled]),
    .dark .fi-input-wrp.fi-disabled,
    .dark .fi-input-wrp.bg-readonly-gray {
        background-color: #374151 !important;
        border-color: #4b5563 !important;
    }
    .dark .fi-input-wrp:has(input[readonly]) input,
    .dark .fi-input-wrp:has(input[disabled]) input,
    .dark .fi-input-wrp.fi-disabled input,
    .dark .fi-input-wrp.bg-readonly-gray input {
        background-color: #374151 !important;
        color: #e5e7eb !important;
    }
    .dark .fi-input-wrp:has(input[readonly]) .fi-input-wrp-prefix,
    .dark .fi-input-wrp:has(input[disabled]) .fi-input-wrp-prefix,
    .dark .fi-input-wrp.fi-disabled .fi-input-wrp-prefix,
    .dark .fi-input-wrp.bg-readonly-gray .fi-input-wrp-prefix {
        background-color: #374151 !important;
        color: #9ca3af !important;
    }
</style>
