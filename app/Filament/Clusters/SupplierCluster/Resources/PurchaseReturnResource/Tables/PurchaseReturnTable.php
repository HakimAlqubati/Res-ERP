<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Tables;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Supplier;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseReturnTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->recordUrl(fn(PurchaseReturn $record): string => PurchaseReturnResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('return_no')
                    ->label('Return No')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('return_date')
                    ->label('Return Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('purchaseInvoice.invoice_no')
                    ->label('Invoice No')
                    ->default('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('store.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        PurchaseReturn::STATUS_DRAFT     => 'warning',
                        PurchaseReturn::STATUS_APPROVED  => 'success',
                        PurchaseReturn::STATUS_REJECTED  => 'danger',
                        PurchaseReturn::STATUS_CANCELLED => 'gray',
                        default                          => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->alignEnd()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->summarize(
                        Summarizer::make()
                            ->using(fn(Table $t) => formatMoneyWithCurrency($t->getRecords()->sum('total_amount')))
                    ),

                TextColumn::make('details_count')
                    ->label('Items')
                    ->alignCenter(),

                TextColumn::make('creator_name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PurchaseReturn::getStatusOptions()),

                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(Supplier::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::where('active', 1)->pluck('name', 'id'))
                    ->searchable(),

                Filter::make('return_date_range')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('return_date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('return_date', '<=', $date));
                    }),
            ], FiltersLayout::Modal)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn(PurchaseReturn $record) => $record->status === PurchaseReturn::STATUS_DRAFT && !$record->cancelled),
                    PurchaseReturnResource::getApproveAction(),
                    PurchaseReturnResource::getCancelAction(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
