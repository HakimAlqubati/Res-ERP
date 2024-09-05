<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;
    protected $table = 'hr_positions';

    // Define the fillable fields
    protected $fillable = [
        'title',
        'description',
        'department_id',
    ];
}
