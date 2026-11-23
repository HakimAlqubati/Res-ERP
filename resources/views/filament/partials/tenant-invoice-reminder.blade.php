@php
    /** @var \App\Models\TenantInvoice $invoice */
    $badgeDays = $invoice->getWarningBadgeDays();
    $isUrgent = $invoice->isUrgentWarning();
    $tooltip = $invoice->getWarningTooltip();
@endphp

<div class="ms-2 flex items-center" style="padding-right: 10px; padding-left: 10px;">
    <button
        type="button"
        class="invoice-warn-badge {{ $isUrgent ? 'invoice-warn-badge--danger' : 'invoice-warn-badge--warning' }}"
        data-days="{{ $badgeDays }}"
        aria-label="{{ $tooltip }}"
        x-tooltip="{
            content: @js($tooltip),
            theme: $store.theme,
        }"
    >
        <span class="invoice-warn-badge__content">
            <span class="invoice-warn-badge__number">{{ $badgeDays }}</span>
            <span class="invoice-warn-badge__label">Days Left</span>
        </span>
    </button>
</div>

<style>
    .invoice-warn-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 14px;
        border-width: 2px;
        border-style: solid;
        border-radius: 0;
        border-top-right-radius: 10px;
        border-bottom-left-radius: 10px;
        background: transparent;
        cursor: pointer;
        font-family: Poppins, ui-sans-serif, system-ui, sans-serif;
        transition: box-shadow 0.25s ease-in-out, transform 0.16s ease;
    }

    .invoice-warn-badge:hover {
        transform: translateY(-1px);
    }

    .invoice-warn-badge__content {
        display: inline-flex;
        flex-direction: row;
        align-items: baseline;
        gap: 5px;
        white-space: nowrap;
        direction: ltr;
    }

    .invoice-warn-badge__number {
        font-size: 18px;
        font-weight: 800;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .invoice-warn-badge__label {
        font-size: 12px;
        font-weight: 600;
        line-height: 1.2;
    }

    /* Warning tone (> 3 days remaining) */
    .invoice-warn-badge--warning {
        border-color: #d97706;
        box-shadow:
            0 0 4px rgba(217, 119, 6, 0.55),
            0 0 8px rgba(217, 119, 6, 0.35);
    }

    .invoice-warn-badge--warning .invoice-warn-badge__number {
        color: #b45309;
    }

    .invoice-warn-badge--warning .invoice-warn-badge__label {
        color: #b45309;
    }

    /* Danger tone (<= 3 days, due today, or overdue) */
    .invoice-warn-badge--danger {
        border-color: #dc2626;
        box-shadow:
            0 0 4px rgba(220, 38, 38, 0.55),
            0 0 8px rgba(220, 38, 38, 0.35);
    }

    .invoice-warn-badge--danger .invoice-warn-badge__number {
        color: #dc2626;
    }

    .invoice-warn-badge--danger .invoice-warn-badge__label {
        color: #dc2626;
    }

    /* Dark mode */
    .dark .invoice-warn-badge--warning {
        border-color: #f59e0b;
    }

    .dark .invoice-warn-badge--warning .invoice-warn-badge__number,
    .dark .invoice-warn-badge--warning .invoice-warn-badge__label {
        color: #fbbf24;
    }

    .dark .invoice-warn-badge--danger {
        border-color: #ef4444;
    }

    .dark .invoice-warn-badge--danger .invoice-warn-badge__number,
    .dark .invoice-warn-badge--danger .invoice-warn-badge__label {
        color: #f87171;
    }
</style>
