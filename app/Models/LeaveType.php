<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name',
        'count_days',
        'description',
        'active',
        'created_by',
        'updated_by',
    ];

    // Relationship to the user who created the leave type
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to the user who updated the leave type
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
