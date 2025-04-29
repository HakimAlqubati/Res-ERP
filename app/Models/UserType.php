<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'level',
        'scope',
        'description',
        'active',
        'can_manage_stores',
        'can_manage_branches'
    ];

    // Define constants for codes
    public const SUPER_ADMIN = 'super_admin';
    public const SYSTEM_MANAGER = 'system_manager';
    public const BRANCH_MANAGER = 'branch_manager';
    public const STORE_MANAGER = 'store_manager';
    public const SUPERVISOR = 'supervisor';
    public const FINANCE_MANAGER = 'finance_manager';
    public const BRANCH_USER = 'branch_user';
    public const DRIVER = 'driver';
    public const STUFF = 'stuff';
    public const ATTENDANCE = 'attendance';
    public const MAINTENANCE_MANAGER = 'maintenance_manager';

    // Static function to return code options
    public static function getCodeOptions(): array
    {
        return [
            self::SUPER_ADMIN => 'Super Admin',
            self::SYSTEM_MANAGER => 'System Manager',
            self::BRANCH_MANAGER => 'Branch Manager',
            self::STORE_MANAGER => 'Store Manager',
            self::SUPERVISOR => 'Supervisor',
            self::FINANCE_MANAGER => 'Finance Manager',
            self::BRANCH_USER => 'Branch User',
            self::DRIVER => 'Driver',
            self::STUFF => 'Stuff',
            self::ATTENDANCE => 'Attendance',
            self::MAINTENANCE_MANAGER => 'Maintenance Manager',
        ];
    }

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class, 'user_type');
    }

    /**
     * Scope a query to only include active types
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    public static function getSelectableTypes(): array
    {
        return self::query()
            ->where('active', true)
            ->pluck('name', 'id')
            ->toArray();
    }
}
