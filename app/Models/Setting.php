<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    // The table associated with the model.
    protected $table = 'settings';

    // The attributes that are mass assignable.
    protected $fillable = ['key', 'value'];

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
