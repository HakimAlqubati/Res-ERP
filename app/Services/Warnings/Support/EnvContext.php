<?php

namespace App\Services\Warnings\Support;

use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

final class EnvContext
{
    private ?string $forcedTenantId = null;

    /** يتيح للـOrchestrator حقن معرف التينانت يدوياً (اختياري) */
    public function setTenantId(?string $tenantId): void
    {
        $this->forcedTenantId = $tenantId;
    }

    public function tenantId(): string
    {
        // 1) إن تم حقنه مسبقاً من الأمر/الأوركستريتور
        if ($this->forcedTenantId !== null) {
            return $this->forcedTenantId;
        }

        // 2) جرّب API الرسمي لـ Spatie: Tenant::current()
        if (class_exists(SpatieTenant::class) && method_exists(SpatieTenant::class, 'current')) {
            /** @var object|null $t */
            $t = SpatieTenant::current();
            if ($t && isset($t->id)) {
                return (string) $t->id;
            }
        }

        // 3) جرّب أي binding شائع في الحاوية
        foreach (['tenant', 'currentTenant'] as $key) {
            if (app()->bound($key)) {
                $t = app($key);
                if ($t && isset($t->id)) {
                    return (string) $t->id;
                }
            }
        }

        // 4) أخيراً: سنترال
        return 'central';
    }
}
