<?php

namespace App\Models\FakeModelReports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportProductQuantities extends Model
{
    use \Sushi\Sushi, HasFactory;
    // public $incrementing = false;

    public $incrementing = false; // Disable auto-incrementing
    // protected $primaryKey = null; // No primary key column

    // protected $schema = [
    //     'none' => 'string',
    // ];

    // public function getRows()
    // {
    //     return [];
    // }

    protected $schema = [
        'none' => 'string',
        // 'product' => 'string',
        // 'branch' => 'string',
        // 'unit' => 'string',
        // 'quantity' => 'decimal',
        // 'price' => 'decimal',
    ];

    public function getRows()
    {
        return [];
    }
}
