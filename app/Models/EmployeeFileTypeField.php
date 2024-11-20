<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFileTypeField extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_file_type_fields';
    protected $fillable = [
        'file_type_id',
        'field_name',
        'field_type',
    ];

    public function fileType()
    {
        return $this->belongsTo(EmployeeFileType::class, 'file_type_id');
    }
}
