<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIssueOrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_issue_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'package_size',
    ];

    public function order()
    {
        return $this->belongsTo(StockIssueOrder::class, 'stock_issue_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    protected static function boot()
    {
        parent::boot(); 
        static::created(function ($stockIssueDetail) {
            $notes = 'Stock issue with id ' . $stockIssueDetail->order_id;
            if (isset($stockIssueDetail->order->store_id)) {
                $notes .= ' in (' . $stockIssueDetail->order->store->name . ')';
            }
            // Subtract from inventory transactions
            \App\Models\InventoryTransaction::create([
                'product_id' => $stockIssueDetail->product_id,
                'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                'quantity' =>  $stockIssueDetail->quantity,
                'unit_id' => $stockIssueDetail->unit_id,
                'movement_date' => now(),
                'package_size' => $stockIssueDetail->package_size,
                'store_id' => $stockIssueDetail->order?->store_id,
                'transaction_date' => $stockIssueDetail->order->date ?? now(),
                'notes' => $notes,
                'transactionable_id' => $stockIssueDetail->stock_issue_order_id,
                'transactionable_type' => StockIssueOrder::class,
            ]);
        });
    }
}
