<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyHoliday extends Model
{
    use HasFactory,DynamicConnection;
    protected $table = 'hr_weekly_holiday';

    protected $fillable = [
        'days',
        'description',
        'created_by',
        'updated_by',
    ];

    // Cast the 'days' column as an array
    protected $casts = [
        'days' => 'array',
    ];

    // Relationship to the creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to the updater
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
