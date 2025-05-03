<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderTransfer;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TransferOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $navigationGroup = 'Orders';
    protected static ?string $recordTitleAttribute = 'orders.id';

    protected static ?string $label = 'Transfers';
    protected static ?string $navigationLabel = 'Transfers list';
    public static ?string $slug = 'transfers-list';
    protected static ?string $cluster = MainOrdersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function getNavigationLabel(): string
    {
        return __('lang.transfers_list');
    }
    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                TextInput::make('id')->label('Order id'),
                TextInput::make('customer.name')->label('customer'),
                TextInput::make('status')->label('Status'),
                TextInput::make('total')->label('total'),
                TextInput::make('branch.name')->label('branch'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->paginated(true)
            ->columns([
                TextColumn::make('id')->label(__('lang.order_id'))->toggleable(isToggledHiddenByDefault: false)
                    ->copyable()
                    ->copyMessage('Order id copied')
                    ->copyMessageDuration(1500)
                    ->sortable()
                    ->searchable()
                    ->searchable(
                        isIndividual: true,
                        isGlobal: false
                    ),
                TextColumn::make('branch.name')->label(__('lang.branch')),
                TextColumn::make('customer.name')->label(__('lang.branch_manager'))->toggleable()
                    ->searchable(isIndividual: true)
                    ->tooltip(fn(Model $record): string => "By {$record->customer?->name}"),

                TextColumn::make('item_count')->label(__('lang.item_counts'))->alignCenter(true),
                TextColumn::make('total_amount')->label(__('lang.total_amount')),
                TextColumn::make('transfer_date')
                    ->label(__('lang.transfer_date'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->sortable(),
                // TextColumn::make('recorded'),
                // TextColumn::make('orderDetails'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                // Filter::make('active')
                //     ->query(fn (Builder $query): Builder => $query->where('active', true)),

                SelectFilter::make('customer_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch_manager'))->relationship('customer', 'name'),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->relationship('branch', 'name'),
                Filter::make('created_at')
                    ->label(__('lang.created_at'))
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label(__('lang.from')),
                        Forms\Components\DatePicker::make('created_until')->label(__('lang.to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransferOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    protected function getTableReorderColumn(): ?string
    {
        return 'sort';
    }

    protected function getTableRecordActionUsing(): ?Closure
    {
        return null;
    }

    public function isTableSearchable(): bool
    {
        return true;
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($searchQuery = $this->getTableSearchQuery())) {
            $query->whereIn('id', OrderTransfer::search($searchQuery)->keys());
        }

        return $query;
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $model): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])->count();
    }
}
