<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class StockAdjustmentReason extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'description',
        'active',
    ];
    protected $auditInclude = [
        'name',
        'description',
        'active',
    ];

    /**
     * Scope a query to only include active stock adjustment reasons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Get the first ID from the stock adjustment reasons.
     *
     * @return int|null
     */
    public static function getFirstId()
    {
        return self::query()->value('id');
    }
}
