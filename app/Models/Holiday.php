<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory,DynamicConnection;
    protected $table = 'hr_holidays';

    protected $fillable = [
        'id',
        'name',
        'from_date',
        'to_date',
        'count_days',
        'active',
        'created_by',
        'updated_by',
    ];

    // Relationship to the user who created the holiday
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to the user who updated the holiday
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
