<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPurchaseInvoices extends ListRecords
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            'Active' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cancelled', 0))
                ->icon('heroicon-o-check-circle' )
                ->badge(PurchaseInvoice::query()->where('cancelled', 0)->count())
                ->badgeColor('success'),
            'Cancelled' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cancelled', 1))
                ->icon('heroicon-o-x-circle')
                ->badge(PurchaseInvoice::query()->where('cancelled', 1)->count())
                ->badgeColor('danger'),

        ];
    }
}