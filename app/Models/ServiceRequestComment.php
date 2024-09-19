<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestComment extends Model
{
    use HasFactory;
    protected $table = 'hr_service_request_comments';
    protected $fillable = [
        'service_request_id',
        'created_by',
        'comment',
    ];

    // Define the polymorphic relationship with photos
    public function photos()
    {
        return $this->morphMany(ServiceRequestPhoto::class, 'imageable');
    }

    
    public function getPhotosCountAttribute()
    {
        return $this->photos()->count();
    }
    // Define the relationship with the service request
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    // Define the relationship with the user who created the comment
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
