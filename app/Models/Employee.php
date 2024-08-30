<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Concerns\IsFilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable implements FilamentUser
// implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'owner_id',
        'role_id',
        'phone_number',
        'whatsapp_number',
        'supplier_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static $filamentUserColumn = 'is_filament_user'; // The name of a boolean column in your database.

    public static $filamentAdminColumn = 'is_filament_admin'; // The name of a boolean column in your database.

    public static $filamentRolesColumn = 'filament_roles';


    public function canAccessPanel(Panel $panel): bool
    {
        return true;
        return $this->group === 'Filament Users';
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'manager_id');
    }


    protected static function booted()
    {
        static::creating(function ($model) {
            $model->role_id = $model->role_id ?? 10;
            $model->password = Hash::make('123456');
        });
    }

    /**
     * Get a new query builder instance for the model.
     *
     * @param  bool  $excludeDeleted
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        $query = parent::newQuery();
        // Add your default where condition here
        $query->where('role_id', 10);

        return $query;
    }

    // public function canAccessFilament(): bool
    // {
    //     return true;
    // }

    public function employee_profile(){
        return $this->hasOne(EmployeeProfile::class,'employee_id');
    }
}
