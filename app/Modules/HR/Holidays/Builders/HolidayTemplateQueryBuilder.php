<?php

declare(strict_types=1);

namespace App\Modules\HR\Holidays\Builders;

use App\Models\HolidayTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class HolidayTemplateQueryBuilder
{
    private Builder $query;

    public function __construct(?Builder $baseQuery = null)
    {
        $this->query = $baseQuery ?? HolidayTemplate::query();
    }

    public static function make(?Builder $baseQuery = null): self
    {
        return new self($baseQuery);
    }

    public function withStandardRelations(): self
    {
        $this->query
            ->with([
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->withCount('occurrences');

        return $this;
    }

    public function onlyActive(): self
    {
        $this->query->active();

        return $this;
    }

    public function forType(?string $type): self
    {
        if ($type) {
            $this->query->where('type', $type);
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

    public function ordered(): self
    {
        $this->query
            ->orderBy('type')
            ->orderBy('month')
            ->orderBy('day');

        return $this;
    }

    public function build(): Builder
    {
        return $this->query;
    }
}
