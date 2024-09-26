<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestLog extends Model
{
    use HasFactory;
    protected $table = 'hr_service_request_logs';
    protected $fillable = [
        'service_request_id',
        'created_by',
        'description',
        'log_type',
    ];

    const LOG_TYPE_CREATED = 'created';
    const LOG_TYPE_UPDATED = 'updated';
    const LOG_TYPE_REASSIGN_TO_USER = 'reassign_to_user';
    const LOG_TYPE_STATUS_CHANGED = 'status_changed';
    const LOG_TYPE_COMMENT_ADDED = 'comment_added';
    const LOG_TYPE_IMAGES_ADDED = 'images_added';
    const LOG_TYPE_REMOVED = 'removed';

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Get the user who created the log.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
