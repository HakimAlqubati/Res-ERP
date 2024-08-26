<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'date',
        'supplier_id',
        'description',
        'invoice_no',
        'store_id',
        'attachment',
    ];

    public function purchaseInvoiceDetails()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchase_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getHasAttachmentAttribute()
    {
        if (strlen($this->attachment) > 0) {
            return 1;
        } else {
            return 0;
        }
    }
}
