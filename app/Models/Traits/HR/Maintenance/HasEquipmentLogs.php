<?php

namespace App\Models\Traits\HR\Maintenance;

use App\Models\EquipmentLog;

trait HasEquipmentLogs
{
    public function logs()
    {
        return $this->hasMany(EquipmentLog::class, 'equipment_id');
    }

    public function addLog(string $action, ?string $description = null, ?int $performedBy = null): EquipmentLog
    {
        return $this->logs()->create([
            'action' => $action,
            'description' => $description,
            'performed_by' => $performedBy ?: (auth()->id() ?? null),
        ]);
    }
}
