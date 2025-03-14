<?php
namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Allowance extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $table = 'hr_allowances';
    protected $fillable = ['name', 'description', 'is_monthly', 'active','is_specific', 'amount', 'percentage','is_percentage'];
    protected $auditInclude = ['name', 'description', 'is_monthly', 'active','is_specific', 'amount', 'percentage','is_percentage'];
}
