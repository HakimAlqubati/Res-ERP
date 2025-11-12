<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosImportData extends Model
{
    use SoftDeletes;

    protected $table = 'pos_import_data';

    protected $fillable = [
        'date',
        'branch_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // العلاقات
    public function details()
    {
        return $this->hasMany(PosImportDataDetail::class, 'pos_import_data_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // اختياري: عند الحذف النهائي احذف التفاصيل نهائياً
    protected static function booted()
    {
        static::deleting(function (self $header) {
            if ($header->isForceDeleting()) {
                $header->details()->withTrashed()->forceDelete();
            }
        });
    }
}
