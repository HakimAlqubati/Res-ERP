@php
    /** @var \App\Models\TenantInvoice $invoice */
    $badgeDays = $invoice->getWarningBadgeDays();
    $tooltip = $invoice->getWarningTooltip();
@endphp

<div class="ms-2 flex items-center gap-1" style="padding-right: 20px; padding-left: 20px;">
    <div>
        <div class="invoice-warn-badge inline-block" data-days="{{ $badgeDays }}">
            <x-filament::icon-button
                icon="heroicon-o-document-currency-dollar"
                :tooltip="$tooltip"
                size="lg"
                color="danger"
                label=""
            />
        </div>
    </div>
</div>

<style>
    .invoice-warn-badge {
        position: relative;
    }

    @keyframes invoice-badge-glow {
        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.0);
        }

        50% {
            box-shadow:
                0 0 10px 4px rgba(220, 38, 38, 0.7),
                0 0 20px 8px rgba(220, 38, 38, 0.4);
        }
    }

    .invoice-warn-badge::after {
        content: attr(data-days);
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        display: grid;
        place-items: center;
        border-radius: 999px;
        font-weight: 700;
        font-size: 11px;
        line-height: 1;
        color: #fff;
        background: linear-gradient(180deg, #ef4444, #dc2626);
        border: 2px solid #fff;
        animation: invoice-badge-glow 1.2s ease-in-out infinite;
        pointer-events: none;
    }
</style>
