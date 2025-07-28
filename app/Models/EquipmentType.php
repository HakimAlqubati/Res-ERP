<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentType extends Model
{
    protected $table = 'hr_equipment_types';

    protected $fillable = [
        'name',
        'description',
        'active', 
        'code', 
    ];

    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'type_id');
    }
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}