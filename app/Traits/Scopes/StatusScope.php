<?php

namespace App\Traits\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait StatusScope
{
    public function scopeStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }
    public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }
    public function scopeApproved(Builder $query)
    {
        return $query->where('status', 'approved');
    }
    public function scopeRejected(Builder $query)
    {
        return $query->where('status', 'rejected');
    }
}
