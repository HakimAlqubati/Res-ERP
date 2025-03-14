<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskCommentPhoto extends Model 
{
    use HasFactory;

    protected $table = 'hr_task_comments_photos';
    protected $fillable = [
        'comment_id',
        'file_name',
        'file_path',
        'created_by',
        'updated_by',
    ];

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         $model->created_by = Auth::id(); // Set created_by to the current user
    //         $model->updated_by = Auth::id(); // Set updated_by to the current user
    //     });

    //     static::updating(function ($model) {
    //         $model->updated_by = Auth::id(); // Update updated_by to the current user
    //     });
    // }

    // Define relationships
    public function comment()
    {
        return $this->belongsTo(TaskComment::class, 'comment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}