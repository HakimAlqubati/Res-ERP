<?php

namespace App\Models;

use App\Services\UnitPriceSyncService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class UnitPrice extends Model implements Auditable

{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $table = 'unit_prices';
    public $primaryKey = 'id';
    protected $fillable = [
        'unit_id',
        'product_id',
        'price',
        'package_size',
        'order',
        'minimum_quantity',
        'use_in_orders',
        'date',
        'notes',
        'usage_scope',
        'selling_price',
        'show_in_invoices',
        'weight',
    ];
    protected $auditInclude = [
        'unit_id',
        'product_id',
        'price',
        'package_size',
        'order',
        'minimum_quantity',
        'usage_scope',
        'selling_price',
        'date','notes',
    ];

    protected $casts = [
        'use_in_orders'    => 'boolean',
        'show_in_invoices' => 'boolean',
        'package_size'     => 'float',
        'price'            => 'float',
        'selling_price'    => 'float',
    ];

    protected $appends = [
        'price_before_waste',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the price before waste for this unit price.
     *
     * @return float
     */
    public function getPriceBeforeWasteAttribute(): float
    {
        $product = $this->product;
        if (! $product && $this->product_id) {
            $product = Product::find($this->product_id);
        }

        if (! $product) {
            return 0.0;
        }

        $packageSize = (float) ($this->package_size ?: 1);
        $totalBeforeWaste = (float) ($product->price_before_waste ?? 0);

        return round($packageSize * $totalBeforeWaste, 4);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    const USAGE_ALL = 'all';
    const USAGE_SUPPLY_ONLY = 'supply_only';
    const USAGE_OUT_ONLY = 'out_only';
    const USAGE_MANUFACTURING_ONLY = 'manufacturing_only';
    const USAGE_NONE = 'none';

    // List of usage scope options for selection or validation
    const USAGE_SCOPES = [
        self::USAGE_ALL => 'All',
        self::USAGE_SUPPLY_ONLY => 'Supply only',
        self::USAGE_OUT_ONLY => 'Outgoing only',
        self::USAGE_MANUFACTURING_ONLY => 'Manufacturing only',
        self::USAGE_NONE => 'Disabled',
    ];
    public function toArray()
    {
        return [
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit?->name,
            'price' => $this->price,
            'price_before_waste' => $this->price_before_waste,
            'package_size' => $this->package_size,
            'order' => $this->order,
            'usage_scope' => $this->usage_scope,
            'show_in_invoices' => $this->show_in_invoices,
            'use_in_orders' => $this->use_in_orders,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $unitPrice) {
            // فقط إذا تم تغيير السعر
            if ($unitPrice->isDirty('price') && $unitPrice->price != 0) {
                ProductPriceHistory::create([
                    'product_id'     => $unitPrice->product_id,
                    'unit_id'        => $unitPrice->unit_id,
                    'old_price'      => $unitPrice->getOriginal('price') ?? 0,
                    'new_price'      => $unitPrice->price,
                    'date'           => $unitPrice->date ?? now(), // استخدم التاريخ المحدد أو تاريخ اليوم
                    'note'           => $unitPrice->notes ?? 'Auto Update from UnitPrice',
                    'source_type'    => self::class,
                    'source_id'      => $unitPrice->id,
                ]);
            }
        });
        // static::saved(function (self $unitPrice) {
        //     // Automatically update package sizes in related tables
        //     // UnitPriceSyncService::syncPackageSizeForProduct($unitPrice->product_id);
        // });
    }


    public function scopeUsableInManufacturing($query)
    {
        return $query->whereIn('usage_scope', [
            self::USAGE_ALL,
            self::USAGE_MANUFACTURING_ONLY,
        ]);
    }

    // داخل App\Models\UnitPrice
    public function scopeWhereUsageScope($query, array|string $scopes)
    {
        if (is_string($scopes)) {
            $scopes = [$scopes];
        }

        return $query->whereIn('usage_scope', $scopes);
    }

    public function scopeForSupply($query)
    {
        return $query->whereIn('usage_scope', [
            self::USAGE_ALL,
            self::USAGE_SUPPLY_ONLY,
        ]);
    }

    public function scopeForOut($query)
    {
        return $query->whereIn('usage_scope', [
            self::USAGE_ALL,
            self::USAGE_OUT_ONLY,
        ]);
    }

    public function scopeForOrders($query)
    {
        return $query->forOut()
            ->where(function ($q) {
                $q->where('use_in_orders', 1)
                    ->orWhere(function ($fallback) {
                        $fallback->where('package_size', 1)
                            ->whereNotExists(function ($sub) {
                                $sub->selectRaw(1)
                                    ->from('unit_prices as up_check')
                                    ->whereColumn('up_check.product_id', 'unit_prices.product_id')
                                    ->whereNull('up_check.deleted_at')
                                    ->whereIn('up_check.usage_scope', [
                                        self::USAGE_ALL,
                                        self::USAGE_OUT_ONLY,
                                    ])
                                    ->where('up_check.use_in_orders', 1);
                            });
                    });
            });
    }

    public function scopeForOperations($query)
    {
        return $query->whereIn('usage_scope', [
            self::USAGE_ALL,
            self::USAGE_SUPPLY_ONLY,
            self::USAGE_OUT_ONLY,
        ]);
    }
    public function scopeForReportsExcludingManufacturing($query)
    {
        return $query->whereIn('usage_scope', [
            self::USAGE_ALL,
            self::USAGE_SUPPLY_ONLY,
            self::USAGE_OUT_ONLY,
            self::USAGE_NONE,
        ]);
    }

    public function scopeForSupplyAndOut($query)
    {
        return $query->whereIn('usage_scope', [
            self::USAGE_ALL,
            self::USAGE_SUPPLY_ONLY,
            self::USAGE_OUT_ONLY,
        ]);
    }

    public static function getPackageSize(int $productId, int $unitId): ?float
    {
        return self::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->value('package_size');
    }
}
