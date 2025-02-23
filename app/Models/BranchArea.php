<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BranchArea extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;

    protected $fillable = ['name', 'description', 'branch_id'];

    // Define the relation with the Branch model
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
