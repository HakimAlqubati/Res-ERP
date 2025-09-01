<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use App\Models\GoodsReceivedNote;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
class ListGoodsReceivedNotes extends ListRecords
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Active' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cancelled', 0))
                ->icon('heroicon-o-check-circle')
                ->badge(GoodsReceivedNote::query()->where('cancelled', 0)->count())
                ->badgeColor('success'),
            'Cancelled' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cancelled', 1))
                ->icon('heroicon-o-x-circle')
                ->badge(GoodsReceivedNote::query()->where('cancelled', 1)->count())
                ->badgeColor('danger'),

        ];
    }
}