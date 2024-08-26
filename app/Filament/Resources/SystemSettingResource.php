<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Filament\Resources\SystemSettingResource\RelationManagers;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Components\Toggle;
// use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;
    protected static ?string $slug = 'system-settings';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('system_settings.system_settings');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('website_name')->label(__('system_settings.website_name')),
                TextInput::make('currency_symbol')->label(__('system_settings.currency_symbol')),
                TextInput::make('limit_days_orders')->numeric()->label(__('system_settings.limit_days_orders')),
                Select::make('calculating_orders_price_method')->label(__('system_settings.calculating_orders_price_method'))
                    ->options([
                        'from_unit_prices' => __('system_settings.from_unit_prices'),
                        'fifo' => __('system_settings.fifo'),
                    ])->default('from_unit_prices'),
                Toggle::make('completed_order_if_not_qty')->inline(false)
                    ->label(__('system_settings.completed_order_if_not_qty'))
                    ->onIcon('heroicon-s-lightning-bolt')
                    ->offIcon('heroicon-s-user')
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText(__('system_settings.note_if_order_completed_if_not_qty')),
                Toggle::make('enable_user_orders_to_store')->inline(false)
                    ->label(__('system_settings.enable_user_orders_to_store'))
                    ->onIcon('heroicon-s-lightning-bolt')
                    ->offIcon('heroicon-s-user')
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText(__('system_settings.enable_user_orders_to_store')),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('website_name')->label(__('system_settings.website_name')),
                TextColumn::make('currency_symbol')->label(__('system_settings.currency_symbol')),
                TextColumn::make('calculating_orders_price_method')
                    ->label(__('system_settings.calculating_orders_price_method')),
                TextColumn::make('limit_days_orders')
                    ->label(__('system_settings.limit_days_orders')),
                IconColumn::make('completed_order_if_not_qty')
                    ->boolean()->label(__('system_settings.completed_order_if_not_qty')),
                IconColumn::make('enable_user_orders_to_store')
                    ->boolean()->label(__('system_settings.enable_user_orders_to_store')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSystemSettings::route('/'),
            'create' => Pages\CreateSystemSetting::route('/create'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }

   
}
