<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class District extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $fillable = ['name', 'city_id'];
    protected $auditInclude = ['name', 'city_id'];


    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
