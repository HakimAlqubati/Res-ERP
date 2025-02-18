<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockIssueOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_date',
        'store_id',
        'issued_by',
        'notes',
        'cancelled',
        'cancel_reason',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function details()
    {
        return $this->hasMany(StockIssueOrderDetail::class, 'stock_issue_order_id');
    }
}
