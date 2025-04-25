<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'audits';

    protected $fillable = [
        'user_type',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->morphTo(null, 'user_type', 'user_id');
    }

    public function getParentIdAttribute(): ?int
    {
        try {
            if ($this->auditable_type === 'App\\Models\\UnitPrice') {
                $unitPrice = \App\Models\UnitPrice::find($this->auditable_id);
                return $unitPrice?->product_id;
            }
            if ($this->auditable_type === 'App\\Models\\OrderDetails') {
                $detail = \App\Models\OrderDetails::find($this->auditable_id);
                return $detail?->order_id;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    public function getParentNameAttribute(): ?string
    {
        try {
            if ($this->auditable_type === 'App\\Models\\UnitPrice') {
                $unitPrice = \App\Models\UnitPrice::find($this->auditable_id);
                return $unitPrice?->product?->name;
            }
            // if ($this->auditable_type === 'App\\Models\\PurchaseInvoiceDetail') {
            //     $unitPrice = \App\Models\UnitPrice::find($this->auditable_id);
            //     return $unitPrice?->product?->name;
            // }
            if ($this->auditable_type === 'App\\Models\\OrderDetails') {
                return '-';
            }
        } catch (\Throwable) {
        }

        return null;
    }

    public function getHasParentAttribute(): bool
    {
        return (bool) $this->parent_id;
    }
}
