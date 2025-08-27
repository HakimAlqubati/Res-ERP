<?php

namespace App\Models\FakeModelReports;

use Sushi\Sushi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingApprovalPreviousOrderDetailsReport extends Model
{
    use Sushi,HasFactory;
    public $incrementing = false;

    protected $schema = [
        'none' => 'string',
    ];

    public function getRows()
    {
        return [];
    }
}
