<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    protected $table = 'hr_user_devices';

    protected $fillable = [
        'user_id',
        'device_hash',
        'active',
        'last_login',
        'plat_form',
        'notes',
        'branch_id',
        'branch_area_id'
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_login' => 'datetime',
    ];

    /* ===========================
     | علاقات
     |===========================*/
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ===========================
     | Scopes
     |===========================*/
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true);
    }

    /* ===========================
     | أدوات مساعدة
     |===========================*/

    // توحيد طريقة الهاش (sha256)
    public static function hashOf(string $deviceId): string
    {
        return hash('sha256', $deviceId);
    }

    // هل هذا الجهاز مصرح لهذا المستخدم؟
    public static function isAuthorized(int $userId, string $deviceId): bool
    {
        return static::where('user_id', $userId)
            // ->where('device_hash', static::hashOf($deviceId))
            ->where('device_hash', $deviceId)
            ->where('active', true)
            ->exists();
    }

    // اربط جهاز (ينشئ أو يفعّل إن وجد)
    public static function bindDevice(int $userId, string $deviceId, ?string $platform = null, ?string $notes = null): self
    {
        $hash = static::hashOf($deviceId);

        return static::updateOrCreate(
            ['user_id' => $userId, 'device_hash' => $hash],
            [
                'active'     => true,
                'plat_form'  => $platform,
                'notes'      => $notes,
                'last_login' => now(),
            ]
        );
    }

    // فك الارتباط/تعطيل
    public static function unbindDevice(int $userId, string $deviceId): int
    {
        return static::where('user_id', $userId)
            ->where('device_hash', static::hashOf($deviceId))
            ->update(['active' => false]);
    }

    // تحدّث آخر تسجيل دخول لهذا الجهاز
    public static function touchLogin(int $userId, string $deviceId): int
    {
        return static::where('user_id', $userId)
            ->where('device_hash', static::hashOf($deviceId))
            ->update(['last_login' => now()]);
    }

    /**
     * Model events.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            // Default platform to "android" if not provided
            if (blank($model->plat_form)) {
                $model->plat_form = 'android';
            } else {
                // normalize to lowercase to keep consistency
                $model->plat_form = strtolower($model->plat_form);
            }
        });
    }


    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branchArea()
    {
        return $this->belongsTo(BranchArea::class, 'branch_area_id');
    }
}
