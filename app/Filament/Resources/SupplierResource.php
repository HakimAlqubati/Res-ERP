<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $navigationGroup = 'Supplier & Roles';
    protected static ?string $cluster = SupplierCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function getNavigationLabel(): string
    {
        return __('lang.suppliers');
    }
    public static function form(Form $form): Form
    {

        return $form
            ->schema([

                TextInput::make('name')->label(__('lang.name'))->required(),
                TextInput::make('email')->label(__('lang.email'))->email()->required(),
                TextInput::make('whatsapp_number')->label(__('lang.whatsapp_number')),
                TextInput::make('phone_number')->label(__('lang.phone_number')),
                Textarea::make('supplier_address')->label(__('lang.address'))->columnSpanFull()


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('email')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('phone_number')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('whatsapp_number')
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('active')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()->hidden(
                    (getCurrentRole() != 1)
                ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
