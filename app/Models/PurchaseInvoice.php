<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PurchaseInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'supplier_id',
        'description',
        'invoice_no',
        'store_id',
        'attachment',
        'cancelled',
        'cancel_reason',
    ];
    protected $appends = ['has_attachment', 'has_description', 'details_count'];

    /**
     * Get the count of purchase invoice details.
     *
     * @return int
     */
    public function getDetailsCountAttribute()
    {
        return $this->purchaseInvoiceDetails()->count();
    }

    /**
     * Scope to filter purhchase invoices with details only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithDetails($query)
    {
        return $query->withCount('purchaseInvoiceDetails') // Count unitPrices
            ->having('purchase_invoice_details_count', '>', 1); // Filter based on the count
    }

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

    public function getHasDescriptionAttribute()
    {
        return !empty($this->description) ? 1 : 0;
    }

    public function cancelInvoice(string $reason)
    {
        DB::beginTransaction();

        try {

            // Check if there is an inventory transaction of type order for this purchase invoice
            $orderExists = \App\Models\InventoryTransaction::where('purchase_invoice_id', $this->id)
                ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_OUT)
                ->exists();

            if ($orderExists) {
                return ['status' => 'error', 'message' => 'Cannot cancel purchase invoice because there are related inventory transactions of type order.'];
            }

            $this->cancelled = true;
            $this->cancel_reason = $reason;
            $this->save();

            // Delete related inventory transactions
            \App\Models\InventoryTransaction::where('reference_id', $this->id)
                ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
                ->delete();

            DB::commit();

            return ['status' => 'success', 'message' => 'Purchase invoice canceled successfully.'];
        } catch (Exception $e) {
            DB::rollBack();

            return ['status' => 'error', 'message' => 'Failed to cancel purchase invoice: ' . $e->getMessage()];
        }
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::updated(function ($purchaseInvoice) {
    //         if ($purchaseInvoice->cancelled) {
    //             \App\Models\InventoryTransaction::where('reference_id', $purchaseInvoice->id)
    //                 ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
    //                 ->delete();
    //         }
    //     });
    // }
}
