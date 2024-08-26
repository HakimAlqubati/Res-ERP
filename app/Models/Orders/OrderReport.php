<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

class OrderReport extends Model
{
    public $incrementing = false;

    protected $schema = [
        'none' => 'string',
    ];

    public function getRows()
    {
        return [];
    }
}
