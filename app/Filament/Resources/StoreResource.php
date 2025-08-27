<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\StoreResource\Pages\ListStores;
use App\Filament\Resources\StoreResource\Pages\CreateStore;
use App\Filament\Resources\StoreResource\Pages\EditStore;
use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Resources\StoreResource\Pages;
use App\Filament\Resources\StoreResource\RelationManagers;
use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    public static function getNavigationLabel(): string
    {
        return __('lang.stores');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return __('lang.stores');
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columns(4)->schema([
                    TextInput::make('name')->label(__('lang.name'))->required(),
                    Select::make('storekeeper_id')->searchable()
                        ->label(__('stock.storekeeper'))
                        ->options(User::select('name', 'id')
                            ->stores()->pluck('name', 'id')),
                    Toggle::make('active')->label(__('lang.active'))->default(1)->inline(false),
                    Toggle::make('default_store')->label(__('lang.default'))->default(0)->inline(false),
                    // Toggle::make('is_central_kitchen')->label(__('stock.is_central_kitchen'))->default(0)->inline(false),


                    Textarea::make('location')
                        ->columnSpanFull()
                        ->label(__('lang.location'))->required(),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->searchable()->label(__('lang.id'))->toggleable(),
                TextColumn::make('name')->searchable()->label(__('lang.name'))->toggleable(),
                TextColumn::make('location')->searchable()->label(__('lang.location'))->toggleable(),
                CheckboxColumn::make('active')->label(__('lang.active'))->toggleable(),
                TextColumn::make('storekeeper_name')->label(__('stock.storekeeper'))->toggleable()->default('-'),
                CheckboxColumn::make('default_store')
                    ->label(__('lang.default'))->disableClick()->toggleable()->alignCenter(true),
                // CheckboxColumn::make('is_central_kitchen')
                //     ->label(__('stock.is_central_kitchen'))->disableClick()->toggleable()->alignCenter(true),

            ])
            ->filters([
                
                TrashedFilter::make(),
            ])
            ->recordActions([

                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),

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
            'index' => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'edit' => EditStore::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        $query->withManagedStores();
        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}