<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Resources\StoreResource\Pages;
use App\Filament\Resources\StoreResource\RelationManagers;
use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('lang.stores');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return __('lang.stores');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(4)->schema([
                    TextInput::make('name')->label(__('lang.name'))->required(),
                    Select::make('storekeeper_id')->searchable()
                        ->label(__('stock.storekeeper'))
                        ->options(User::select('name', 'id')
                            ->stores()->pluck('name', 'id')),
                    Toggle::make('active')->label(__('lang.active'))->default(1)->inline(false),
                    Toggle::make('default_store')->label(__('lang.default'))->default(0)->inline(false),


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
                TextColumn::make('storekeeper_name')->label(__('stock.storekeeper'))->toggleable(),
                CheckboxColumn::make('default_store')
                    ->label(__('lang.default'))->disableClick()->toggleable()->alignCenter(true),

            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('report')
                    ->label(__('lang.open_report'))
                    ->action(function ($record) {
                        redirect('admin/stores-report?tableFilters[store_id][value]=' . $record->id);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
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
