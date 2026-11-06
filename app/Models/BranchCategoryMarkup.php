<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchCategoryMarkup extends Model
{
    use HasFactory;

    protected $table = 'branch_category_markups';

    protected $fillable = [
        'branch_id',
        'category_id',
        'transfer_markup_percentage',
    ];

    protected $casts = [
        'transfer_markup_percentage' => 'float',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
