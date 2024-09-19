<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestPhoto extends Model
{
    use HasFactory;
    protected $table = 'hr_service_request_photos';
    protected $fillable = [
        'image_name',
        'image_path',
        'updated_by',
        'created_by',
    ];

    public function imageable()
    {
        return $this->morphTo();
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
