<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantInvoices\Tables;

use App\Models\TenantInvoice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('Invoice Number'))
                    ->weight('bold')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->label(__('Tenant'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label(__('Invoice Date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label(__('Due Date'))
                    ->date('Y-m-d')
                    ->color(fn (TenantInvoice $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('remaining_days')
                    ->label(__('Remaining Days'))
                    ->badge()
                    ->formatStateUsing(fn (?int $state): ?string => $state !== null ? __(':days days', ['days' => $state]) : null)
                    ->color(fn (TenantInvoice $record): string => match (true) {
                        $record->remaining_days === null => 'gray',
                        $record->remaining_days <= $record->warning_days => 'warning',
                        default => 'info',
                    })
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => TenantInvoice::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => TenantInvoice::getStatusOptions()[$state] ?? ucfirst($state))
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label(__('Paid At'))
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(TenantInvoice::getStatusOptions()),

                SelectFilter::make('tenant_id')
                    ->label(__('Tenant'))
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('overdue')
                    ->label(__('Overdue'))
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('markAsPaid')
                        ->label(__('Mark as Paid'))
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (TenantInvoice $record): bool => $record->isUnpaid())
                        ->authorize('markAsPaid')
                        ->modalHeading(__('Mark Invoice as Paid'))
                        ->modalDescription(fn (TenantInvoice $record): string => __('Confirm payment for invoice :number.', ['number' => $record->invoice_number]))
                        ->modalSubmitActionLabel(__('Confirm Payment'))
                        ->modalWidth('md')
                        ->schema([
                            DatePicker::make('paid_at')
                                ->label(__('Payment Date'))
                                ->default(today())
                                ->required(),

                            Textarea::make('notes')
                                ->label(__('Notes'))
                                ->rows(3)
                                ->nullable(),
                        ])
                        ->action(function (TenantInvoice $record, array $data): void {
                            $record->markAsPaid(
                                paidAt: $data['paid_at'] ?? now(),
                                notes: $data['notes'] ?? null,
                            );

                            Notification::make()
                                ->success()
                                ->title(__('Invoice marked as paid'))
                                ->send();
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
