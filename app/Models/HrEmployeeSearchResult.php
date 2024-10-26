<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEmployeeSearchResult extends Model
{
    use HasFactory;

    protected $fillable = ['image', 'employee_id', 'similarity'];

    // Define the relationship with hr_employees
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
