<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePeriodLog extends Model
{
    use HasFactory;
    protected $table = 'hr_employee_period_logs';
    protected $fillable = [
        'employee_id',
        'period_ids', // JSON field
        'action',
    ];

      // Define the relationship with the Employee model
      public function employee()
      {
          return $this->belongsTo(Employee::class);
      }
  
      // If you want to decode period_ids for easier access
      public function getPeriodIdsAttribute($value)
      {
          return json_decode($value, true); // Decode JSON to array
      }
}
