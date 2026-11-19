<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Builders;

use App\Models\HolidayOccurrence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class HolidayOccurrenceQueryBuilder
{
    private Builder $query;

    public function __construct(?Builder $baseQuery = null)
    {
        $this->query = $baseQuery ?? HolidayOccurrence::query();
    }

    public static function make(?Builder $baseQuery = null): self
    {
        return new self($baseQuery);
    }

    public function withStandardRelations(): self
    {
        $this->query->with([
            'template:id,name,type,day,month,count_days',
            'createdBy:id,name',
            'updatedBy:id,name',
            'confirmedBy:id,name',
        ]);

        return $this;
    }

    public function onlyActive(): self
    {
        $this->query->active();

        return $this;
    }

    public function forYear(?int $year): self
    {
        if ($year) {
            $this->query->forYear($year);
        }

        return $this;
    }

    public function forStatus(?string $status): self
    {
        if ($status) {
            $this->query->where('status', $status);
        }

        return $this;
    }

    public function withoutTrashedScope(): self
    {
        $this->query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        return $this;
    }

    public function orderedForCalendar(): self
    {
        $this->query
            ->orderByDesc('year')
            ->orderBy('from_date');

        return $this;
    }

    public function build(): Builder
    {
        return $this->query;
    }
}
