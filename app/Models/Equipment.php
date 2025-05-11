<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Equipment extends Model implements Auditable, HasMedia
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, InteractsWithMedia;

    protected $table = 'hr_equipment';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'asset_tag',
        'qr_code',
        'make',
        'model',
        'serial_number',
        'branch_id',
        'purchase_price',
        'warranty_file',
        'profile_picture',
        'size',
        'periodic_service',
        'last_serviced',
        'created_by',
        'name',
        'type_id',
        'operation_start_date',
        'warranty_end_date',
        'next_service_date',
        'branch_area_id',
        'status',
        'service_interval_days',
        'warranty_years',
        'purchase_date',
    ];
    protected $auditInclude = [
        'asset_tag',
        'qr_code',
        'make',
        'model',
        'serial_number',
        'branch_id',
        'purchase_price',
        'warranty_file',
        'profile_picture',
        'size',
        'periodic_service',
        'last_serviced',
        'created_by',
        'name',
        'type_id',
        'operation_start_date',
        'warranty_end_date',
        'next_service_date',
        'branch_area_id',
        'status',
        'service_interval_days',
        'warranty_years',
        'purchase_date',
    ];

    const STATUS_ACTIVE = 'Active';
    const STATUS_UNDER_MAINTENANCE = 'Under Maintenance';
    const STATUS_RETIRED = 'Retired';

    const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_UNDER_MAINTENANCE => 'Under Maintenance',
        self::STATUS_RETIRED => 'Retired',
    ];
    /**
     * Relationship with the branch model (assuming you have one).
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Relationship with the user model to track who created the record (assuming you have a `User` model).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Automatically set the 'created_by' attribute when creating a new record.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($equipment) {
            $equipment->created_by = Auth::id();
            $equipment->qr_code = 'QR-' . date('YmdHis') . '-' . Auth::id();
        });
    }

    public function type()
    {
        return $this->belongsTo(EquipmentType::class, 'type_id');
    }

    // Optional helper to access category directly from equipment
    public function category()
    {
        return $this->type?->category();
    }

    public function branchArea()
    {
        return $this->belongsTo(BranchArea::class, 'branch_area_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    public static function getStatusLabels(): array
    {
        return self::STATUS_LABELS;
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_UNDER_MAINTENANCE => 'warning',
            self::STATUS_RETIRED => 'danger',
            default => 'secondary',
        };
    }
}
