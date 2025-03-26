<?php

namespace App\Models;

use GPBMetadata\Google\Type\Datetime;
use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    protected $fillable = ['email', 'otp', 'expires_at'];

    public $timestamps = true;

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}
