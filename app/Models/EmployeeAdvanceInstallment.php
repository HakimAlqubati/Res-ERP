<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvanceInstallment extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_advance_installments';

    protected $fillable = [
        'employee_id',
        'application_id',
        'transaction_id',
        'installment_amount',
        'due_date',
        'is_paid',
        'paid_date',
    ];

    // Relationship: Belongs to a single application transaction
    public function transaction()
    {
        return $this->belongsTo(ApplicationTransaction::class, 'transaction_id');
    }

    // Relationship: Belongs to a single employee application
    public function application()
    {
        return $this->belongsTo(EmployeeApplication::class, 'application_id');
    }
}
