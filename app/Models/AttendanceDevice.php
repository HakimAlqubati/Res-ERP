<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDevice extends Model
{

    protected $table = 'hr_attendance_device';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'active',
    ];

    /**
     * Define the one-to-one relationship with User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
