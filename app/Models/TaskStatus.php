<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskStatus extends Model
{
    use SoftDeletes;

    protected $table = 'hr_task_statuses';

    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'status_id');
    }
}
