<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Circular extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'hr_circulars';

    protected $fillable = [
        'title',
        'description',
        'group_id',
        'branch_ids',
        'released_date',
        'active',
        'created_by',
        'updated_by',
    ];
    protected $auditInclude = [
        'title',
        'description',
        'group_id',
        'branch_ids',
        'released_date',
        'active',
        'created_by',
        'updated_by',
    ];

    public function photos()
    {
        return $this->hasMany(CircularPhoto::class);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPhotosCountAttribute()
    {
        return $this->photos()->count();
    }

    public function group()
    {
        return $this->belongsTo(UserType::class, 'group_id');
    }

    protected static function booted()
    {


        // Branch scope logic moved to ApplyBranchScopes middleware
        // to avoid relationship issues during model boot cycle.
        // See: app/Http/Middleware/ApplyBranchScopes.php

    }
}
