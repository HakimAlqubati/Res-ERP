<?php

namespace App\Livewire\Topbar;

use App\Models\PurchaseInvoice;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

/**
 * Shows today's KPIs in a hover popover (no click needed).
 * Polls periodically so numbers stay fresh without page reloads.
 */
class QuickHints extends Component
{
    public int $ordersToday = 0;
    public int $purchasesToday = 0;
    public int $grnToday = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        // Adjust model namespaces to your app (examples below).
        $start = Carbon::today();
        $end   = Carbon::tomorrow();
 
        $this->ordersToday = Cache::remember('kpi.orders.today', 30, function () use ($start, $end) {
            return \App\Models\Order::whereBetween('created_at', [$start, $end])->count();
        });

        $this->purchasesToday = Cache::remember('kpi.purchases.today', 30, function () use ($start, $end) {
            return PurchaseInvoice::whereBetween('created_at', [$start, $end])->count();
        });

        // GRN = Goods Received Note (adjust model/table accordingly)
        $this->grnToday = Cache::remember('kpi.grn.today', 30, function () use ($start, $end) {
            return \App\Models\GoodsReceivedNote::whereBetween('created_at', [$start, $end])->count();
        });
    }

    public function render()
    {
        return view('livewire.topbar.quick-hints');
    }
}
