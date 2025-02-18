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
}
