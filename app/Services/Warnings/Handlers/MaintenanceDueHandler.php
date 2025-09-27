<?php

namespace App\Services\Warnings\Handlers;

use App\Services\Warnings\Contracts\WarningHandler;
use App\Services\Warnings\Support\MaintenanceRepository;
use Illuminate\Support\Carbon;

final class MaintenanceDueHandler implements WarningHandler
{
    protected array $options = [];

    public function __construct(
        private readonly MaintenanceRepository $repo
    ) {}

    public function key(): string
    {
        return 'maintenance-due';
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function handle(): array
    {
        // فلاتر اختيارية
        $filters = [
            'branch_id'      => $this->options['branch_id'] ?? null,
            'branch_area_id' => $this->options['branch_area_id'] ?? null,
            'status'         => $this->options['status'] ?? null,
        ];

        $dueWindowDays = (int)($this->options['days'] ?? 7);

        // الآن: نجمع البيانات فقط (بدون إرسال)
        $overdue = [];
        foreach ($this->repo->overdue($filters) as $eq) {
            $overdue[] = $this->present($eq, overdue: true);
        }

        $dueSoon = [];
        foreach ($this->repo->dueWithin($dueWindowDays, $filters) as $eq) {
            $dueSoon[] = $this->present($eq);
        }

        // مبدئياً نرجّع 0/0 حتى نضيف الإرسال لاحقاً
        return [0, 0];
    }

    private function present($eq, bool $overdue = false): array
    {
        $next = $eq->next_service_date ? Carbon::parse($eq->next_service_date) : null;
        return [
            'id'                   => $eq->id,
            'asset_tag'            => $eq->asset_tag,
            'name'                 => $eq->name,
            'type'                 => $eq->type?->name,
            'branch'               => $eq->branch?->name,
            'status'               => $eq->status,
            'last_serviced'        => $eq->last_serviced,
            'next_service_date'    => $eq->next_service_date,
            'service_interval_days'=> $eq->service_interval_days,
            'overdue'              => $overdue,
            'days_until_due'       => $next ? now()->diffInDays($next, false) : null,
        ];
    }
}
