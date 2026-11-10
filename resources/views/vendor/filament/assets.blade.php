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

    /* ========================================================= */
    /* Readonly & Disabled Fields (Light & Night Mode)           */
    /* ========================================================= */
    .fi-input-wrp:has(input[readonly]),
    .fi-input-wrp:has(input[disabled]),
    .fi-input-wrp:has(select[disabled]),
    .fi-input-wrp:has(textarea[readonly]),
    .fi-input-wrp:has(textarea[disabled]),
    .fi-input-wrp.fi-disabled,
    .fi-input-wrp.bg-readonly-gray {
        background-color: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
        cursor: not-allowed !important;
    }
    .fi-input-wrp:has(input[readonly]) input,
    .fi-input-wrp:has(input[disabled]) input,
    .fi-input-wrp:has(select[disabled]) select,
    .fi-input-wrp:has(textarea[readonly]) textarea,
    .fi-input-wrp:has(textarea[disabled]) textarea,
    .fi-input-wrp.fi-disabled input,
    .fi-input-wrp.bg-readonly-gray input {
        background-color: transparent !important;
        cursor: not-allowed !important;
        color: #475569 !important;
    }
    .fi-input-wrp:has(input[readonly]) .fi-input-wrp-prefix,
    .fi-input-wrp:has(input[disabled]) .fi-input-wrp-prefix,
    .fi-input-wrp.fi-disabled .fi-input-wrp-prefix,
    .fi-input-wrp.bg-readonly-gray .fi-input-wrp-prefix,
    .fi-input-wrp:has(input[readonly]) .fi-input-wrp-suffix,
    .fi-input-wrp:has(input[disabled]) .fi-input-wrp-suffix,
    .fi-input-wrp.fi-disabled .fi-input-wrp-suffix,
    .fi-input-wrp.bg-readonly-gray .fi-input-wrp-suffix {
        background-color: transparent !important;
        color: #64748b !important;
    }

    /* Night Mode / Dark Mode */
    .dark .fi-input-wrp:has(input[readonly]),
    .dark .fi-input-wrp:has(input[disabled]),
    .dark .fi-input-wrp:has(select[disabled]),
    .dark .fi-input-wrp:has(textarea[readonly]),
    .dark .fi-input-wrp:has(textarea[disabled]),
    .dark .fi-input-wrp.fi-disabled,
    .dark .fi-input-wrp.bg-readonly-gray {
        background-color: #334155 !important;
        border-color: #475569 !important;
        box-shadow: none !important;
        cursor: not-allowed !important;
    }
    .dark .fi-input-wrp:has(input[readonly]) input,
    .dark .fi-input-wrp:has(input[disabled]) input,
    .dark .fi-input-wrp:has(select[disabled]) select,
    .dark .fi-input-wrp:has(textarea[readonly]) textarea,
    .dark .fi-input-wrp:has(textarea[disabled]) textarea,
    .dark .fi-input-wrp.fi-disabled input,
    .dark .fi-input-wrp.bg-readonly-gray input {
        background-color: transparent !important;
        cursor: not-allowed !important;
        color: #f8fafc !important;
        font-weight: 500 !important;
    }
    .dark .fi-input-wrp:has(input[readonly]) .fi-input-wrp-prefix,
    .dark .fi-input-wrp:has(input[disabled]) .fi-input-wrp-prefix,
    .dark .fi-input-wrp.fi-disabled .fi-input-wrp-prefix,
    .dark .fi-input-wrp.bg-readonly-gray .fi-input-wrp-prefix,
    .dark .fi-input-wrp:has(input[readonly]) .fi-input-wrp-suffix,
    .dark .fi-input-wrp:has(input[disabled]) .fi-input-wrp-suffix,
    .dark .fi-input-wrp.fi-disabled .fi-input-wrp-suffix,
    .dark .fi-input-wrp.bg-readonly-gray .fi-input-wrp-suffix {
        background-color: transparent !important;
        color: #cbd5e1 !important;
    }
</style>
