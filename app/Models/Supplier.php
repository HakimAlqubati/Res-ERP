<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
        'phone_number',
        'address',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
