<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularPhoto extends Model
{
    use HasFactory;
    protected $table = 'hr_circular_photos';
    protected $fillable = [
        'image_name',
        'image_path',
        'updated_by',
        'created_by',
        'cirular_id',
    ];

    public function cirular()
    {
        return $this->belongsTo(Circular::class);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
