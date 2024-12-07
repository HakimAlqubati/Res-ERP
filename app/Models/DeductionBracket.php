<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionBracket extends Model
{
    use HasFactory;

    protected $table = 'hr_deduction_brackets';
    protected $fillable = [
        'deduction_id',
        'min_amount',
        'max_amount',
        'percentage',
    ];

    // Define the inverse relationship with the deduction
    public function deduction()
    {
        return $this->belongsTo(Deduction::class);
    }
}
