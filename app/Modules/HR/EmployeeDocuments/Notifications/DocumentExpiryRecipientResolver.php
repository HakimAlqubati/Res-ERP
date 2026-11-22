<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Notifications;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves active notification recipients for employee document expiry digests.
 */
final class DocumentExpiryRecipientResolver
{
    public const ROLE_SUPER_ADMIN = 1;
    public const ROLE_SYSTEM_MANAGER = 3;
    public const ROLE_BRANCH_MANAGER = 7;
    public const ROLE_HR = 19;

    /**
     * Active HR, Super Admin, and System Manager users who receive the organization-wide digest.
     *
     * @return Collection<int, User>
     */
    public function resolveGlobalRecipients(): Collection
    {
        return User::query()
            ->where('active', 1)
            ->whereHas('roles', static function ($query): void {
                $query->whereIn('roles.id', [
                    self::ROLE_SUPER_ADMIN,
                    self::ROLE_SYSTEM_MANAGER,
                    self::ROLE_HR,
                ]);
            })
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Active Branch Managers responsible for a specific branch (via branches.manager_id,
     * users.branch_id, or branch_user pivot), excluding users who already received a global digest.
     *
     * @param  array<int, int>  $excludeUserIds
     * @return Collection<int, User>
     */
    public function resolveBranchManagers(int $branchId, array $excludeUserIds = []): Collection
    {
        $managerIdFromBranch = Branch::withoutGlobalScopes()
            ->where('id', $branchId)
            ->value('manager_id');

        $users = User::query()
            ->where('active', 1)
            ->when($excludeUserIds !== [], fn ($q) => $q->whereNotIn('id', $excludeUserIds))
            ->where(function ($query) use ($branchId, $managerIdFromBranch): void {
                if ($managerIdFromBranch) {
                    $query->where('id', $managerIdFromBranch);
                }

                $query->orWhere(function ($sub) use ($branchId): void {
                    $sub->whereHas('roles', static function ($roleQuery): void {
                        $roleQuery->where('roles.id', self::ROLE_BRANCH_MANAGER);
                    })->where(function ($branchQuery) use ($branchId): void {
                        $branchQuery->where('branch_id', $branchId)
                            ->orWhereHas('branches', static function ($pivotQuery) use ($branchId): void {
                                $pivotQuery->where('branches.id', $branchId);
                            });
                    });
                });
            })
            ->get()
            ->unique('id')
            ->values();

        return $users;
    }

    /**
     * Optional filter for testing with --user (ID or email).
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    public function filterByOptionUser(Collection $users, string|int|null $target): Collection
    {
        if ($target === null || $target === '') {
            return $users;
        }

        if (is_numeric($target)) {
            $id = (int) $target;

            return $users->filter(static fn (User $u): bool => (int) $u->id === $id)->values();
        }

        $needle = strtolower(trim((string) $target));

        return $users->filter(
            static fn (User $u): bool => strtolower((string) $u->email) === $needle
        )->values();
    }
}
