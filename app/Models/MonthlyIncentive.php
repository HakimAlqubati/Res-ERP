<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyIncentive extends Model
{
    use HasFactory, SoftDeletes;
   protected $table = 'hr_monthly_incentives';
    protected $fillable = ['name', 'description', 'active'];
}
