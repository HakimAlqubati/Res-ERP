<?php

namespace App\Models\FakeModelHRReports;

use Sushi\Sushi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceReport extends Model
{
    use Sushi, HasFactory; 

    public $incrementing = false; 

    protected $schema = [
        'none' => 'string', 
    ];

    public function getRows()
    {
        return [];
    }
}
