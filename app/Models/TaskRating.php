<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskRating extends Model
{
    use HasFactory;
    protected $table = 'hr_task_rating';
    protected $fillable = ['task_id','comment', 'created_by', 'employee_id','task_user_id_assigned', 'rating_value', 'status'];

}
