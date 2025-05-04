<?php

namespace App\Traits;

use App\Models\UserType;

trait HasUserTypeAccess
{
    public function isType(string|array $codes): bool
    {
        if (!$this->userType) {
            return false;
        }

        $codes = (array) $codes;

        return in_array($this->userType->code, $codes);
    }

    public function isSuperAdmin(): bool
    {
        return in_array(1, $this->roles->pluck('id')->toArray());
    }

    public function isSystemManager(): bool
    {
        return $this->isType('system_manager');
    }

    public function isFinanceManager(): bool
    {
        return $this->isType('finance_manager');
    }

    public function isBranchManager(): bool
    {
        return $this->userType?->isRootType() && $this->userType?->scope === 'branch';
    }

    public function isStoreManager(): bool
    {
        return $this->userType?->isRootType() && $this->userType?->scope === 'store';
    }

    public function isBranchUser(): bool
    {
        return !$this->userType?->isRootType() && $this->userType?->scope === 'branch';
    }

    public function isAttendance(): bool
    {
        return $this->isType('attendance');
    }

    public function isDriver(): bool
    {
        return $this->isType('driver');
    }

    public function isStuff(): bool
    {
        return $this->isType('stuff');
    }

    public function isSuperVisor(): bool
    {
        return $this->isType('super_visor');
    }

    public function isMaintenanceManager(): bool
    {
        return $this->isType('maintenance_manager');
    }

    public function getAccessibleBranchNamesAttribute(): string
    {
        return $this->branches()
            ->pluck('name')
            ->implode(', ');
    }
}
