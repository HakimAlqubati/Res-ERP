<?php
// app/Jobs/CopyOutToInForOrdersJob.php
namespace App\Jobs;

use App\Models\Order;
use App\Models\InventoryTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

use Spatie\Multitenancy\Models\Tenant;

class CopyOutToInForOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $tenantId;
    public ?int $branchId;

    public $tries = 3;       // مرات إعادة المحاولة
    public $backoff = 10;    // ثواني بين المحاولات
    public $timeout = 900;   // 15 دقيقة

    public function __construct(?int $tenantId = null, ?int $branchId = null)
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
        $this->onQueue('inventory'); // اسم الطابور (اختياري)
    }

    public function handle(): void
    {
        // تفعيل سياق التيننت إن وجد
        if ($this->tenantId) {
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        Order::select(['id', 'branch_id', 'created_at'])
            ->with(['branch:id,store_id', 'branch.store:id'])
            ->whereNull('deleted_at')
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))

            ->whereHas('branch.store')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $store = $order->branch?->store;
                    if (! $store) {
                        continue; // لا يوجد مخزن للفرع
                    }
                    DB::transaction(function () use ($store, $order) {

                        InventoryTransaction::where('transactionable_type', Order::class)
                            ->where('transactionable_id', $order->id)
                            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                            ->where('store_id', $store->id)
                            ->withTrashed()
                            ->forceDelete();
                        $outTransactions = InventoryTransaction::where('transactionable_type', Order::class)
                            ->where('transactionable_id', $order->id)
                            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                            ->get(['id', 'product_id', 'quantity', 'unit_id', 'package_size', 'price', 'store_id']);
                        if ($outTransactions->isEmpty()) return;
                        $rows = [];
                        foreach ($outTransactions as $out) {
                            $rows[] = [
                                'product_id' => $out->product_id,
                                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                                'quantity' => $out->quantity,
                                'unit_id' => $out->unit_id,
                                'movement_date' => $order->created_at,
                                'transaction_date' => $order->created_at,
                                'package_size' => $out->package_size,
                                'price' => $out->price,
                                'notes' => 'Supplied from Order #' . $order->id,
                                'store_id' => $store->id,
                                'transactionable_type' => Order::class,
                                'transactionable_id' => $order->id,
                                'source_transaction_id' => $out->id,
                            ];
                        }
                        if ($rows) {
                            DB::table('inventory_transactions')->insert($rows);
                        }
                    });
                }
            });
    }
}
