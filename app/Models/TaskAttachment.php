<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class TaskAttachment extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'hr_task_attachments';

    protected $fillable = [
        'task_id',
        'file_name',
        'file_path',
        'created_by',
        'updated_by',
    ];
    protected $auditInclude = [
        'task_id',
        'file_name',
        'file_path',
        'created_by',
        'updated_by',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
