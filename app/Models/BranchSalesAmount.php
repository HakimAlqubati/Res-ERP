<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchSalesAmount extends Model
{

    protected $fillable = [
        'branch_id',
        'amount',
        'sales_at',
        'notes',
        'created_by',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($paid) {
            $paid->created_by = auth()->id();
        });
    }
}