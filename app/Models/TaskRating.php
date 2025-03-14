<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TaskRating extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    protected $table = 'hr_task_rating';
    protected $fillable = ['task_id','comment', 'created_by', 'employee_id','task_user_id_assigned', 'rating_value', 'status'];
    protected $auditInclude = ['task_id','comment', 'created_by', 'employee_id','task_user_id_assigned', 'rating_value', 'status'];

}
