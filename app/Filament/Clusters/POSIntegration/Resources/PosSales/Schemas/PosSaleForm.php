<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class PosSaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->columns(2)->schema([
                    TextInput::make('sale_date')->label('Sale Date'),
                    Select::make('branch_id')->label('branch')->options(Branch::query()->select('id', 'name')->pluck('name', 'id')),

                ])
            ]);
    }
}
