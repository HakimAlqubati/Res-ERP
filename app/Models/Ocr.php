<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ocr extends Model
{
    protected $table = 'ocr_results';
    protected $fillable = [
        'image_path',
        'extracted_text'
    ];
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}
