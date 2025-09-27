<?php

namespace App\Services\Warnings\Handlers;

use App\Enums\Warnings\WarningLevel;
use App\Facades\Warnings;
use App\Models\AppLog;
use App\Models\Store;
use App\Models\User;
use App\Services\Warnings\Contracts\WarningHandler;
use App\Services\Warnings\Support\EnvContext;
use App\Services\Warnings\Support\RecipientsService;
use App\Services\Warnings\Support\ReportUrlResolver;
use App\Services\Warnings\WarningPayload;
use Illuminate\Support\Collection;

final class LowStockHandler implements WarningHandler
{
    public function __construct(
        private readonly RecipientsService $recipients,
        private readonly ReportUrlResolver $urls,
        private readonly EnvContext $env,
    ) {}

    /** @var array{user?:string|int|null, limit?:int} */
    private array $options = [
        'user'  => null,
        'limit' => 100,
    ];

    public function key(): string
    {
        return 'low_stock';
    }

    public function setOptions(array $options): void
    {
        $this->options = array_replace($this->options, $options);
    }

    public function handle(): array
    {
        $sent = 0; $failed = 0;

        $store = Store::query()->defaultStore()->with('storekeeper')->first();
        if (!$store instanceof Store) {
            return [0, 0];
        }

        // IDs وليس slugs
        $users = $this->recipients->byRoleIds([1, 3]);

        if ($store->storekeeper instanceof User) {
            $users->push($store->storekeeper);
        }

        $users = $this->recipients->normalize($users);
        $users = $this->recipients->filterByOptionUser($users, $this->options['user'] ?? null);

        if ($users->isEmpty()) {
            return [0, 0];
        }

        $limit = (int)($this->options['limit'] ?? 100) ?: 100;
        $users = $users->take(max(1, $limit));

        $tenantId  = $this->env->tenantId();
        $reportUrl = $this->urls->lowStockReport($store->id);

        foreach ($users as $user) {
            try {
                $payload = $this->buildPayload($tenantId, $store->id)->url($reportUrl);
                Warnings::send($user, $payload);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                AppLog::write(
                    'Failed to send low stock warning',
                    AppLog::LEVEL_WARNING,
                    'inventory',
                    [
                        'store_id'  => $store->id,
                        'tenant_id' => $tenantId,
                        'error'     => $e->getMessage(),
                    ]
                );
            }
        }

        return [$sent, $failed];
    }

    private function buildPayload(string $tenantId, int $storeId): WarningPayload
    {
        return WarningPayload::make(
            'Inventory Low',
            'Inventory qty is lower',
            WarningLevel::Warning
        )
        ->ctx(['tenant_id' => $tenantId, 'store_id' => $storeId])
        ->scope("lowstock-tenant:{$tenantId}-store:{$storeId}")
        ->expires(now()->addHours(6));
    }
}
