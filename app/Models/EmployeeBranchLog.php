<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBranchLog extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_branch_logs';
    protected $fillable = ['employee_id', 'branch_id', 'start_at', 'end_at','created_by'];

    // Define the relationship with the Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Define the relationship with the Branch model (assuming you have a Branch model)
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by');
    }
}
