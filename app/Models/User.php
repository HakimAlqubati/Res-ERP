<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
// implements FilamentUser

{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, HasPanelShield;


    // Use only the traits you need, or none if you handle it manually
    use UsesLandlordConnection, UsesTenantConnection {
        UsesLandlordConnection::getConnectionName insteadof UsesTenantConnection;
        UsesTenantConnection::getConnectionName as getTenantConnectionName;
    }


    public function getConnectionName()
    {
        $explodeHost = explode('.', request()->getHost());
        $count = count($explodeHost);
        // Example logic: Use tenant connection if tenant is active, otherwise use landlord
        // dd($count, $explodeHost, $this->getTenantConnectionName(),env('APP_ENV'),env('APP_ENV') == 'local',Branch::all());
        if (
            env('APP_ENV') == 'local' && $count == 2
            || env('APP_ENV') == 'production' && $count == 3
        ) {
            return $this->getTenantConnectionName();
        }


        return 'landlord'; // Or explicitly return the landlord connection
    }
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
        'branch_id',
        'phone_number',
        'avatar',
        'is_employee',
        'user_type',
        'active',
        'gender',
        'nationality',
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
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getFirstRoleAttribute()
    {
        return $this->roles->first();
    }

    public function getAvatarImageAttribute()
    {
        // $default = 'users/default/avatar.png';
        // if (is_null($this->avatar) || $this->avatar == $default) {
        //     return storage_path($default);
        // }
        return url('/storage') . '/' . $this->avatar;
        return storage_path($this->avatar);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    public function isBranchManager()
    {
        if (getCurrentRole() == 7) {
            return true;
        }
        return false;
    }
    public function isFinanceManager()
    {
        if (getCurrentRole() == 16) {
            return true;
        }
        return false;
    }
    public function isSuperAdmin()
    {
        if (getCurrentRole() == 1) {
            return true;
        }
        return false;
    }
    public function isAttendance()
    {
        if (getCurrentRole() == 17) {
            return true;
        }
        return false;
    }
    public function isSystemManager()
    {
        if (getCurrentRole() == 3) {
            return true;
        }
        return false;
    }
    public function isStoreManager()
    {
        if (getCurrentRole() == 5) {
            return true;
        }
        return false;
    }
    public function isMaintenanceManager()
    {
        if (getCurrentRole() == 7) {
            return true;
        }
        return false;
    }
    public function isStuff()
    {
        if (in_array(getCurrentRole(), [8, 9, 10, 6])) {
            return true;
        }
        return false;
    }

    public function getIsBranchManagerAttribute() {}
    // public function canAccessFilament(): bool
    // {
    //     return true;
    // }

    protected static function booted()
    {
        // dd(auth()->check());
        if (auth()->check()) {
            if (isBranchManager()) {
                static::addGlobalScope('active', function (\Illuminate\Database\Eloquent\Builder $builder) {
                    $builder->where('branch_id', auth()->user()->branch_id); // Add your default query here
                });
            }
        }
    }

    public function getHasEmployeeAttribute()
    {
        if ($this->employee()->exists()) {
            return true;
        }
        return false;
    }
}
