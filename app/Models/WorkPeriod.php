<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkPeriod extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hr_work_periods';

    // Define fillable fields
    protected $fillable = [
        'name',
        'description',
        'active',
        'start_at',
        'end_at',
        'allowed_count_minutes_late',
        'days',
        'created_by',
        'updated_by',
        'branch_id',
        'all_branches',
    ];

    // Relationship to the user who created the work period
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to the user who updated the work period
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    
    
}
