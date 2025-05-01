<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'level',
        'scope',
        'description',
        'active',
        'can_manage_stores',
        'can_manage_branches',
        'parent_type_id',
    ];



    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class, 'user_type');
    }

    /**
     * Scope a query to only include active types
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function parent()
    {
        return $this->belongsTo(UserType::class, 'parent_type_id');
    }

    public function children()
    {
        return $this->hasMany(UserType::class, 'parent_type_id');
    }

    public function isRootType(): bool
    {
        return is_null($this->parent_type_id);
    }

    public function requiresOwner(): bool
    {
        return !$this->isRootType();
    }

    public function getAncestors(): array
    {
        $ancestors = [];
        $parent = $this->parent;

        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parent;
        }

        return array_reverse($ancestors);
    }

    public function getLevel(): int
    {
        return count($this->getAncestors()) + 1;
    }

    public function isSameScope(UserType $other): bool
    {
        return $this->scope === $other->scope;
    }

    protected static function booted(): void
    {
        static::saving(function (UserType $userType) {
            if ($userType->isDirty('parent_type_id')) {
                if (!$userType->relationLoaded('parent') && $userType->parent_type_id) {
                    $userType->load('parent');
                }

                $userType->level = count($userType->getAncestors()) + 1;
            }
        });
    }
}
