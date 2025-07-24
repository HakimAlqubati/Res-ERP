<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceImage extends Model
{
      protected $fillable = ['user_id', 'path', 'score', 'liveness', 'meta'];

    protected $casts = [
        'liveness' => 'boolean',
        'meta' => 'array',
    ];
}