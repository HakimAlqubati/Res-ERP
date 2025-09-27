<?php

namespace App\Services\Warnings\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class RecipientsService
{
    /** جلب المستلمين حسب IDs (مثلاً [1,3]) */
    public function byRoleIds(array $ids = [1, 3]): Collection
    {
        return User::query()
            ->whereHas('roles', fn($q) => $q->whereIn('id', $ids))
            ->get()
            ->values();
    }

    /** توحيد/تنظيف قائمة المستخدمين (User|id|email|array) */
    public function normalize(Collection $users): Collection
    {
        return $users
            ->map(function ($u) {
                if ($u instanceof User) {
                    return $u;
                }

                if (is_int($u) || (is_string($u) && ctype_digit($u))) {
                    return User::find((int) $u);
                }

                if (is_string($u) && str_contains($u, '@')) {
                    return User::query()->where('email', $u)->first();
                }

                if (is_array($u)) {
                    if (isset($u['id'])) {
                        return User::find((int) $u['id']);
                    }
                    if (isset($u['email'])) {
                        return User::query()->where('email', $u['email'])->first();
                    }
                }

                return null;
            })
            ->filter()
            ->unique(fn(User $u) => $u->id)
            ->values();
    }

    /** فلترة المستلمين بهدف الاختبار بـ --user (ID أو Email) */
    public function filterByOptionUser(Collection $users, string|int|null $target): Collection
    {
        if ($target === null || $target === '') {
            return $users;
        }

        $needle = is_numeric($target) ? (int) $target : $target;

        return $users->filter(function (User $u) use ($needle) {
            return $u->id === $needle || $u->email === $needle;
        })->values();
    }
}
