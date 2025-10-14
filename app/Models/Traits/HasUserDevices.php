<?php

namespace App\Models\Traits;

use App\Models\UserDevice;

trait HasUserDevices
{
    /**
     * علاقة المستخدم بالأجهزة المصرّح بها.
     */
    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * التحقق هل الجهاز الحالي مصرح لهذا المستخدم.
     */
    public function hasAuthorizedDevice(string $deviceId): bool
    {
        return $this->devices()
            ->where('device_hash', UserDevice::hashOf($deviceId))
            ->where('active', true)
            ->exists();
    }

    /**
     * تسجيل أو تفويض الجهاز للمستخدم.
     */
    public function authorizeDevice(
        string $deviceId,
        ?string $platform = null,
        ?string $notes = null
    ): UserDevice {
        return UserDevice::bindDevice($this->id, $deviceId, $platform, $notes);
    }

    /**
     * تعطيل جهاز محدد.
     */
    public function disableDevice(string $deviceId): int
    {
        return UserDevice::unbindDevice($this->id, $deviceId);
    }

    /**
     * تحديث وقت آخر تسجيل دخول لهذا الجهاز.
     */
    public function touchDeviceLogin(string $deviceId): int
    {
        return UserDevice::touchLogin($this->id, $deviceId);
    }
}
