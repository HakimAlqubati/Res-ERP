<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;
    protected $table = 'employee_profile';

    protected $fillable = [
        'job_title',
        'employee_id',
        'emp_id',
        'department_id',
        'user_role_id',
        'employee_no',
    ];

    public function department(){
        return $this->belongsTo(Department::class);
    }

}
