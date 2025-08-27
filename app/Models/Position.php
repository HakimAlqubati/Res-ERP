<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model implements Auditable
{
    use HasFactory,DynamicConnection, \OwenIt\Auditing\Auditable;
    protected $table = 'hr_positions';

    // Define the fillable fields
    protected $fillable = [
        'title',
        'description',
        'department_id',
    ];
    protected $auditInclude = [
        'title',
        'description',
        'department_id',
    ];
}
