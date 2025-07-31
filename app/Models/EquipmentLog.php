<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentLog extends Model
{
    use HasFactory;

    protected $table = 'hr_equipment_logs';
    protected $fillable = [
        'equipment_id',
        'action',
        'description',
        'performed_by',
    ];

    // Action constants
    const ACTION_CREATED  = 'Created';
    const ACTION_UPDATED  = 'Updated';
    const ACTION_SERVICED = 'Serviced';
    const ACTION_MOVED    = 'Moved';
    const ACTION_RETIRED  = 'Retired';

    const ACTION_LABELS = [
        self::ACTION_CREATED  => 'Created',
        self::ACTION_UPDATED  => 'Updated',
        self::ACTION_SERVICED => 'Serviced',
        self::ACTION_MOVED    => 'Moved',
        self::ACTION_RETIRED  => 'Retired',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
    protected static function booted()
    {
        parent::booted();

        static::created(function ($log) {
            if (empty($log->performed_by)) {
                $log->performed_by = auth()->id();
                $log->save();
            }
        });

        
    }

    public function addLog(string $action, ?string $description = null, ?int $performedBy = null): void
    {
        \App\Models\EquipmentLog::create([
            'equipment_id' => $this->id,
            'action'       => $action,
            'description'  => $description,
            'performed_by' => $performedBy ?? auth()->id(),
        ]);
    }

    public function getLastLog()
    {
        return $this->logs()->latest()->first();
    }
    public function hasBeenServicedRecently(int $days = 30): bool
    {
        return $this->logs()
            ->where('action', \App\Models\EquipmentLog::ACTION_SERVICED)
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }
}