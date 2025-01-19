<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'route_name',
        'date',
        'time',
        'is_approved',
        'approved_by',
        'created_by',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCreatedByNameAttribute()
    {
        return $this->creator ? $this->creator->name : 'N/A';
    }
    public function getApprovedByNameAttribute()
    {
        return $this->approver ? $this->approver->name : 'N/A';
    }

    /**
     * Store a new approval request.
     *
     * @param string $routeName
     * @param string $date
     * @param string $time
     * @param int|null $userId
     * @return Approval
     */
    public static function store(string $routeName, string $date, string $time, ?int $userId = null): self
    {
        return self::create([
            'route_name' => $routeName,
            'date' => $date,
            'time' => $time,
            'is_approved' => false,
            'approved_by' => null,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }
}
