<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AppLog extends Model
{
    protected $fillable = [
        'level',
        'context',
        'message',
        'extra',
        'user_id',
        'ip_address',
        'user_agent',
    ];
    public const LEVEL_DEBUG     = 'debug';
    public const LEVEL_INFO      = 'info';
    public const LEVEL_NOTICE    = 'notice';
    public const LEVEL_WARNING   = 'warning';
    public const LEVEL_ERROR     = 'error';
    public const LEVEL_CRITICAL  = 'critical';
    public const LEVEL_ALERT     = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    protected $casts = [
        'extra' => 'array',
    ];

    // علاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // دوال مساعدة (Scopes)
    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeContext(Builder $query, string $context): Builder
    {
        return $query->where('context', $context);
    }

    // دوال static لسهولة الاستخدام
    public static function write(
        string $message,
        string $level = self::LEVEL_INFO,
        ?string $context = null,
        array $extra = [],
        ?int $userId = null
    ): self {
        return static::create([
            'level'      => $level,
            'context'    => $context,
            'message'    => $message,
            'extra'      => $extra,
            'user_id'    => $userId ?? auth()->id(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
