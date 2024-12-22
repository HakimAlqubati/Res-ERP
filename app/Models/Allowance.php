<?php
namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allowance extends Model
{
    use HasFactory, SoftDeletes,DynamicConnection;
    protected $table = 'hr_allowances';
    protected $fillable = ['name', 'description', 'is_monthly', 'active','is_specific', 'amount', 'percentage','is_percentage'];
}
