<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFile extends Model
{
    use HasFactory;
    // Specify the table associated with the model (optional)
    protected $table = 'hr_employee_files';

    protected $casts = [
        'dynamic_field_values' => 'array', // Automatically decode JSON to array
    ];
    
    // Define the fillable fields
    protected $fillable = [
        'employee_id',
        'file_type_id',
        'attachment',
        'active',
        'description',
        'dynamic_field_values',
    ];

    // Define relationships (optional)
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
    
    public function fileType()
    {
        return $this->belongsTo(EmployeeFileType::class, 'file_type_id');
    }
}
