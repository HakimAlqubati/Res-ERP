<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductHalalCertificate extends Model
{
    use HasFactory;

    protected $table = 'product_halal_certificates';

    protected $fillable = [
        'product_id',
        'shelf_life_value',
        'shelf_life_unit',
        'net_weight',
        'allergen_info',
    ];

    protected $casts = [
        'shelf_life_value' => 'integer',
        'net_weight' => 'string',
    ];

    /**
     * Shelf life unit options
     */
    public const UNIT_DAY = 'day';
    public const UNIT_WEEK = 'week';
    public const UNIT_MONTH = 'month';
    public const UNIT_YEAR = 'year';

    /**
     * Get shelf life unit options for select inputs
     */
    public static function getShelfLifeUnitOptions(): array
    {
        return [
            self::UNIT_DAY => __('lang.day'),
            self::UNIT_WEEK => __('lang.week'),
            self::UNIT_MONTH => __('lang.month_label'),
            // self::UNIT_YEAR => __('lang.year'),
        ];
    }

    /**
     * Relationship: belongs to Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate expiry date (BEST BEFORE) based on production date
     */
    public function calculateExpiryDate(Carbon $productionDate): ?Carbon
    {
        if (!$this->shelf_life_value || !$this->shelf_life_unit) {
            return null;
        }

        return match ($this->shelf_life_unit) {
            self::UNIT_DAY => $productionDate->copy()->addDays($this->shelf_life_value),
            self::UNIT_WEEK => $productionDate->copy()->addWeeks($this->shelf_life_value),
            self::UNIT_MONTH => $productionDate->copy()->addMonths($this->shelf_life_value),
            // self::UNIT_YEAR => $productionDate->copy()->addYears($this->shelf_life_value),
            default => null,
        };
    }

    /**
     * Get formatted shelf life string
     */
    public function getFormattedShelfLifeAttribute(): ?string
    {
        if (!$this->shelf_life_value || !$this->shelf_life_unit) {
            return null;
        }

        $unitLabel = self::getShelfLifeUnitOptions()[$this->shelf_life_unit] ?? $this->shelf_life_unit;
        return "{$this->shelf_life_value} {$unitLabel}";
    }
}
