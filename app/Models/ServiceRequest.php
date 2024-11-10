<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;
    protected $table = 'hr_service_requests';

    // Fillable fields
    protected $fillable = [
        'name',
        'description',
        'branch_id',
        'branch_area_id',
        'assigned_to',
        'urgency',
        'impact',
        'status',
        'created_by',
        'updated_by',
    ];

    
    // Status constants
    const STATUS_NEW = 'New';
    const STATUS_PENDING = 'Pending';
    const STATUS_IN_PROGRESS = 'In progress';
    const STATUS_CLOSED = 'Closed';

    const STATUS_LABELS = [
        self::STATUS_NEW => 'New',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_CLOSED => 'Closed',
    ];

    // Urgency constants
    const URGENCY_HIGH = 'High';
    const URGENCY_MEDIUM = 'Medium';
    const URGENCY_LOW = 'Low';

    const URGENCY_LABELS = [
        self::URGENCY_HIGH => 'High',
        self::URGENCY_MEDIUM => 'Medium',
        self::URGENCY_LOW => 'Low',
    ];

    // Impact constants
    const IMPACT_HIGH = 'High';
    const IMPACT_MEDIUM = 'Medium';
    const IMPACT_LOW = 'Low';

    const IMPACT_LABELS = [
        self::IMPACT_HIGH => 'High',
        self::IMPACT_MEDIUM => 'Medium',
        self::IMPACT_LOW => 'Low',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branchArea()
    {
        return $this->belongsTo(BranchArea::class, 'branch_area_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Define the relationship with comments
    public function comments()
    {
        return $this->hasMany(ServiceRequestComment::class, 'service_request_id');
    }

    // Define the polymorphic relationship with photos
    public function photos()
    {
        return $this->morphMany(ServiceRequestPhoto::class, 'imageable');
    }

    public function getFirstPhotoUrlAttribute()
    {
        // Get the first photo, or return a default image if none exists
        $firstPhoto = $this->photos()->first();
        return $firstPhoto ? url('storage/' . $firstPhoto->image_path) : url('path/to/default/image.jpg');
    }


    public function getPhotosCountAttribute()
    {
        return $this->photos()->count();
    }
    public function logs()
    {
        return $this->hasMany(ServiceRequestLog::class, 'service_request_id');
    }

    protected static function booted()
    {
        if (isBranchManager()) {
            static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
            });
        } 
        if (isStuff()) {
            static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('assigned_to', auth()->user()->employee->id)
                ->orWhere('created_by',auth()->user()->id)
                ; // Add your default query here
            });
        }  elseif (isFinanceManager()) {
            static::addGlobalScope(function (\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('assigned_to', auth()->user()->employee->id)
                ->orWhere('created_by',auth()->user()->id)
                ; // Add your default query here
            });
        }
    }
}
