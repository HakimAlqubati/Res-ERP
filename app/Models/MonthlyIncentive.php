<?php
namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyIncentive extends Model
{
    use HasFactory, SoftDeletes,DynamicConnection   ;
   protected $table = 'hr_monthly_incentives';
    protected $fillable = ['name', 'description', 'active'];
}
