<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Traits\Localizable;
use Spatie\Translatable\HasTranslations;

class ItemType extends Model
{
    use HasFactory, SoftDeletes, Localizable, HasTranslations;
    public $fillable = [
        'name',
        'description',
        'active',
        'item_type_id',
    ];

    public $translatable = [
        'name',
        'description',
    ];

    public $localizable = [
        'name',
        'description',
    ];
}
