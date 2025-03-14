<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Country extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = ['name', 'code'];
    protected $auditInclude = ['name', 'code'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
