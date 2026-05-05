<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use App\Models\Branch;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;
    public function getTitle(): string | Htmlable
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
