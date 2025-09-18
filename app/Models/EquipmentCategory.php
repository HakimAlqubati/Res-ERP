<?php

namespace App\Models;

use App\Traits\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;

class EquipmentCategory extends Model
{
    use BranchScope;
    protected $table = 'hr_equipment_categories';

    protected $fillable = [
        'name',
        'equipment_code_start_with',
        'description',
        'active',
    ];

    public function types()
    {
        return $this->hasMany(EquipmentType::class, 'category_id');
    }
    

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
