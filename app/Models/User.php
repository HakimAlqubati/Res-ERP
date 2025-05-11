<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\DynamicConnection;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements FilamentUser, Auditable
// implements FilamentUser

{
    use HasApiTokens,
        HasFactory,
        Notifiable,
        HasRoles,
        SoftDeletes,
        HasPanelShield,
        DynamicConnection,
        \OwenIt\Auditing\Auditable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
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
        'branch_area_id',
        'is_attendance_user',
        'fcm_token',
        'last_seen_at',
    ];
    protected $auditInclude = [
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
        'branch_area_id',
        'is_attendance_user',
        'fcm_token',
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

    protected $appends = ['managed_stores_ids'];
    public static $filamentUserColumn = 'is_filament_user'; // The name of a boolean column in your database.

    public static $filamentAdminColumn = 'is_filament_admin'; // The name of a boolean column in your database.

    public static $filamentRolesColumn = 'filament_roles';

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
        return $this->group === 'Filament Users';
    }

    public function manageBranches()
    {
        return $this->hasMany(Branch::class, 'manager_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
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
        // Check if avatar is set and exists on S3
        if ($this->avatar && Storage::disk('s3')->exists($this->avatar)) {
            return Storage::disk('s3')->url($this->avatar);
        }

        // Ensure the default image exists on the local storage
        $defaultAvatarPath = 'employees/default/avatar.png';

        if (Storage::disk('public')->exists($defaultAvatarPath)) {
            return url('/') .  Storage::disk('public')->url($defaultAvatarPath);
            return Storage::disk('public')->url($defaultAvatarPath);
        }
        // If file is not found, return a fallback URL
        return asset('images/default-avatar.png');
    }
    public function getAvatarImageAttribute_old()
    {
        $default = 'users/default/avatar.png';
        if (is_null($this->avatar)) {
            return url('/storage') . '/' . $default;
        }
        return url('/storage') . '/' . $this->avatar;
        return storage_path($this->avatar);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    public function isSuperVisor()
    {
        return in_array(15, $this->roles->pluck('id')->toArray());
    }
    public function isBranchManager()
    {
        return in_array(7, $this->roles->pluck('id')->toArray());

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
        return in_array(5, $this->roles->pluck('id')->toArray()) || isFinanceManager();

        if (getCurrentRole() == 5) {
            return true;
        }
        return false;
    }
    public function isBranchUser()
    {
        return in_array(8, $this->roles->pluck('id')->toArray());
    }
    public function isDriver()
    {
        return in_array(6, $this->roles->pluck('id')->toArray());
    }
    public function isMaintenanceManager()
    {
        if (getCurrentRole() == 14) {
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

    public function attendanceDevice()
    {
        return $this->hasOne(AttendanceDevice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeStores($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('id', 5); // Filter users by the selected role
        });
    }

    public function managedStores()
    {
        return $this->hasMany(Store::class, 'storekeeper_id');
    }

    public function getManagedStoresIdsAttribute()
    {
        if (!auth()->check()) {
            return [];
        }
        $ids = auth()->user()->managedStores->pluck('id')->toArray() ?? [];
        return $ids;
    }

    public function routeNotificationForFcm($notification)
    {
        return $this->fcm_token; // Replace with the actual field that stores the user's FCM token
    }

    public function getIsBlockedAttribute()
    {
        $isBlocked = false;
        if (setting('type_reactive_blocked_users') == 'manual') {
            $failedAttempts = LoginAttempt::where('email', $this->email)
                ->where('successful', false)
                ->count();
            if ($failedAttempts >= setting('threshold')) {
                $isBlocked = true;
            }
        }
        return $isBlocked;
    }

    public function getRolesTitleAttribute()
    {
        return $this->roles->pluck('name')->implode(', ');
    }

    public function ownedUsers()
    {
        return $this->hasMany(User::class, 'owner_id');
    }
    public function loginHistories()
    {
        return $this->hasMany(UserLoginHistory::class);
    }
    public function getLastLoginAtAttribute()
    {
        return $this->loginHistories()->latest()->first()?->created_at;
    }

    public function getLastSeenAttribute()
    {
        return $this->last_seen_at?->diffForHumans();
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'user_branches');
    }

    public function getBranchIdsAttribute(): array
    {
        return $this->branches()->pluck('branch_id')->toArray();
    }
    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_type');
    }
    public function scopeWithBranch($query)
    {
        $user = auth()->user();
        if ($user?->userType?->can_access_all_branches) {
            return $query;
        }
        return $query->whereIn('branch_id', accessBranchesIds());
    }
    public function getCanAccessAllBranchesAttribute(): bool
    {
        return $this->userType?->can_access_all_branches ?? false;
    }
    public function getCanAccessAllStoresAttribute(): bool
    {
        return $this->userType?->can_access_all_stores ?? false;
    }
    public function getHidePricesAttribute(): bool
    {
        $user = auth()->user();
        return $user->userType?->hide_prices ?? false;
    }
}
