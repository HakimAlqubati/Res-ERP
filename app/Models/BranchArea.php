<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchArea extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'branch_id'];

    // Define the relation with the Branch model
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
