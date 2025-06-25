<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ResellersCluster;
use App\Filament\Resources\BranchResellerResource\BranchPaidAmountsRelationManagerResource\RelationManagers\PaidAmountsRelationManager;
use App\Filament\Resources\BranchResellerResource\BranchSalesAmountsRelationManagerResource\RelationManagers\SalesAmountsRelationManager;
use App\Filament\Resources\BranchResellerResource\Pages;
use App\Filament\Resources\BranchResellerResource\RelationManagers;
use App\Models\Branch;
use App\Models\BranchReseller;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResellerResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = ResellersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 0;
    public static function getPluralLabel(): ?string
    {
        return __('menu.resellers');
    }
    public static function getNavigationLabel(): string
    {
        return __('menu.resellers');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->label(__('lang.branch_id'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                SpatieMediaLibraryImageColumn::make('')->label('')->size(50)
                    ->circular()->alignCenter(true)->getStateUsing(function () {
                        return null;
                    })->limit(3),
                TextColumn::make('name')->label(__('lang.name'))->searchable(),
                IconColumn::make('active')->boolean()->label(__('lang.active'))->alignCenter(true),
                TextColumn::make('address')->label(__('lang.address'))
                    // ->limit(100)
                    ->words(5)->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')->label(__('lang.manager')),

                TextColumn::make('user.email')->label('Email')->copyable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('orders_count')
                    ->formatStateUsing(fn($record): string => $record?->orders()?->count() ?? 0)
                    ->label(__('lang.orders'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: false),
                // TextColumn::make('reseller_balance')
                //     ->label('Balance')
                //     ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                //     ->sortable(),

                TextColumn::make('total_orders_amount')
                    ->label('Orders Total')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),

                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),


                TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),



            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('active')
                    ->options([
                        1 => __('lang.active'),
                        0 => __('lang.status_unactive'),
                    ])->default(1),

            ])
            ->actions([
                Action::make('addPayment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->form([
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required(),
                        DateTimePicker::make('paid_at')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),
                        Textarea::make('note')
                            ->label('Note')
                            ->rows(2),
                    ])
                    ->visible(fn(Model $record) => $record->type === \App\Models\Branch::TYPE_RESELLER)
                    ->action(function (Model $record, array $data) {

                        $record->paidAmounts()->create([
                            'amount'   => $data['amount'],
                            'paid_at'  => $data['paid_at'],
                            'note'     => $data['note'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Payment added successfully')
                            ->success()
                            ->send();
                    })->button(),

                Action::make('addSale')
                    ->label('Add Sale')
                    ->icon('heroicon-o-chart-bar')
                    ->form([
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required(),
                        DateTimePicker::make('sale_at')
                            ->label('Sale Date')
                            ->default(now())
                            ->required(),
                        Textarea::make('note')
                            ->label('Note')
                            ->rows(2),
                    ])
                    ->visible(fn(Model $record) => $record->type === \App\Models\Branch::TYPE_RESELLER)
                    ->action(function (Model $record, array $data) {

                        $record->salesAmounts()->create([
                            'amount'   => $data['amount'],
                            'sale_at'  => $data['sale_at'],
                            'note'     => $data['note'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Sale added successfully')
                            ->success()
                            ->send();
                    })->button(),



                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                // Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SalesAmountsRelationManager::class,
            PaidAmountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranchResellers::route('/'),
            'create' => Pages\CreateBranchReseller::route('/create'),
            'view' => Pages\ViewBranchReseller::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()->where('type', Branch::TYPE_RESELLER);

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return Color::Red;
    }
}