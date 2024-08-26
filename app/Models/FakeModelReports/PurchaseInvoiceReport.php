<?php

namespace App\Models\FakeModelReports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceReport extends Model
{
    use \Sushi\Sushi,HasFactory;
    public $incrementing = false;

    protected $schema = [
        'none' => 'string',
    ];

    public function getRows()
    {
        return [];
    }
}
