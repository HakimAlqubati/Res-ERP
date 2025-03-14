<?php

namespace App\Models;

use App\Traits\DynamicConnection;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Setting extends Model  implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    // The table associated with the model.
    protected $table = 'settings';

    // The attributes that are mass assignable.
    protected $fillable = ['key', 'value'];
    protected $auditInclude = ['key', 'value'];

    // Disables the auto-incrementing ID if you choose to use string keys for settings
    public $incrementing = false;

    // If you want to disable timestamps, uncomment the line below
    // public $timestamps = false;

    /**
     * Get a setting by its key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function getSetting($key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by its key.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public static function setSetting($key, $value)
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
