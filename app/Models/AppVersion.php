<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_versions';

    public const PLATFORM_ANDROID = 'android';
    public const PLATFORM_IOS     = 'ios';
    public const PLATFORM_ALL     = 'all';

    public const PLATFORMS = [
        self::PLATFORM_ANDROID,
        self::PLATFORM_IOS,
        self::PLATFORM_ALL,
    ];

    protected $fillable = [
        'platform',
        'version_name',
        'version_code',
        'min_supported_version',
        'min_version_code',
        'is_force_update',
        'download_url',
        'release_notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'version_code'     => 'integer',
        'min_version_code' => 'integer',
        'is_force_update'  => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active versions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific platform or 'all'.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->whereIn('platform', [$platform, self::PLATFORM_ALL]);
    }
}
