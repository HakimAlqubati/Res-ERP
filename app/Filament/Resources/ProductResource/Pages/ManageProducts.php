<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->url(fn () => static::getResource()::getUrl('create', ['type' => $this->activeTab]))

            ,
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 30, 50];
    }
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All Products'))
                ->modifyQueryUsing(fn (Builder $query) => $query)
                ->icon('heroicon-o-rectangle-stack'),

            'manufacturing' => Tab::make(__('Manufacturing Products'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('category', fn ($q) => $q->where('is_manafacturing', true)))
                ->icon('heroicon-o-cog'),

            'non_manufacturing' => Tab::make(__('Non-Manufacturing Products'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('category', fn ($q) => $q->where('is_manafacturing', false)))
                ->icon('heroicon-o-cube'),
        ];
    }
}
