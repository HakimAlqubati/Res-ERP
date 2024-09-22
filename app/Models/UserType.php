<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'role_ids'];

    // Define role_ids as a JSON column
    protected $casts = [
        'role_ids' => 'array',
    ];
}
