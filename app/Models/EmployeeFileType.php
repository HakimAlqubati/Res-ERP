<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFileType extends Model
{
    use HasFactory;
      // Specify the table associated with the model (optional if naming conventions are followed)
      protected $table = 'hr_employee_file_types';

      // Define the fillable fields
      protected $fillable = [
          'name',
          'description',
          'active',
          'is_required',
      ];

        // Static method to get counts
    public static function getCountByRequirement()
    {
        $requiredCount = self::where('is_required', true)->count();
        $unrequiredCount = self::where('is_required', false)->count();

        return [
            'required_count' => $requiredCount,
            'unrequired_count' => $unrequiredCount,
        ];
    }
}
