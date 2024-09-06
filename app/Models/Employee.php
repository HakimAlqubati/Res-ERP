<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use  HasFactory, SoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'hr_employees';
    protected $fillable = [
        'name',
        'position_id',
        'email',
        'phone_number',
        'job_title',
        'user_id',
        'branch_id',
        'department_id',
        'employee_no',
        'active',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function files()
    {
        return $this->hasMany(EmployeeFile::class, 'employee_id');
    }

    // Accessor for required_documents_count
    public function getRequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', true);
        })->count();
    }

    // Accessor for unrequired_documents_count
    public function getUnrequiredDocumentsCountAttribute()
    {
        return $this->files()->whereHas('fileType', function ($query) {
            $query->where('is_required', false);
        })->count();
    }
}
