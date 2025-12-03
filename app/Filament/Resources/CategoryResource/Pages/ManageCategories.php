<?php

namespace App\Filament\Resources\CategoryResource\Pages;
use Filament\Schemas\Components\Tabs\Tab;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CategoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
          
            'active' => Tab::make(__('Active'))
                ->modifyQueryUsing(fn($query) => $query->where('active', 1))
                ->badge(fn() => \App\Models\Category::notForPos()->where('active', 1)->count())
                ->badgeColor('success'),

            'inactive' => Tab::make(__('Inactive'))
                ->modifyQueryUsing(fn($query) => $query->where('active', 0))
                ->badge(fn() => \App\Models\Category::notForPos()->where('active', 0)->count())
                ->badgeColor('danger'),
        ];
    }
}
