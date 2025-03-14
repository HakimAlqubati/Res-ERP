<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BranchArea extends Model implements HasMedia, Auditable
{
    use HasFactory,InteractsWithMedia, \OwenIt\Auditing\Auditable;

    protected $fillable = ['name', 'description', 'branch_id'];
    protected $auditInclude = ['name', 'description', 'branch_id'];

    // Define the relation with the Branch model
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
