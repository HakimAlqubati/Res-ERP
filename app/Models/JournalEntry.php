<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;
    protected $fillable = ['date', 'description', 'related_model_type', 'related_model_id'];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function related_model()
    {
        return $this->morphTo();
    }
    
}
