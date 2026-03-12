<?php

namespace App\Filament\Resources\BranchSalesReports\Tables;

use App\Models\BranchSalesReport;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Enums\FinancialCategoryCode;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class BranchSalesReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('net_sale')
                    ->label('Net Sale')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),
                TextColumn::make('service_charge')
                    ->label('Service Charge')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        BranchSalesReport::STATUS_APPROVED => 'success',
                        BranchSalesReport::STATUS_PENDING => 'warning',
                        BranchSalesReport::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BranchSalesReport::getStatusOptions()),
                Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn($record): bool => $record->status == BranchSalesReport::STATUS_PENDING),
                ActionGroup::make([
                    Action::make('Approve')
                        ->label('Approve')
                        ->color('success')
                        ->icon('heroicon-o-check-badge')
                        ->requiresConfirmation()
                        ->visible(fn($record): bool => $record->status == BranchSalesReport::STATUS_PENDING)
                        ->action(function (BranchSalesReport $record) {
                            DB::beginTransaction();
                            try {
                                $salesCategory = FinancialCategory::findByCode(FinancialCategoryCode::SALES);

                                if (!$salesCategory) {
                                    throw new \Exception('Sales financial category not found. Please ensure the financial categories are seeded properly.');
                                }

                                $record->update([
                                    'status' => BranchSalesReport::STATUS_APPROVED,
                                    'approved_by' => auth()->id(),
                                    'approved_at' => now(),
                                ]);

                                if ($record->total_amount > 0) {
                                    $transaction = FinancialTransaction::create([
                                        'branch_id' => $record->branch_id,
                                        'category_id' => $salesCategory->id,
                                        'amount' => $record->total_amount,
                                        'type' => FinancialTransaction::TYPE_INCOME,
                                        'transaction_date' => $record->date,
                                        'status' => FinancialTransaction::STATUS_PAID,
                                        'description' => "Sales transaction imported from Branch Sales Report for date: " . $record->date->format('Y-m-d'),
                                        'created_by' => auth()->id(),
                                        'month' => $record->date->month,
                                        'year' => $record->date->year,
                                    ]);

                                    // Set polymorphic relationship
                                    $transaction->reference()->associate($record);
                                    $transaction->save();
                                }

                                DB::commit();
                                showSuccessNotifiMessage('Report Approved and Financial Transaction Created.');
                            } catch (Throwable $e) {
                                DB::rollBack();
                                showWarningNotifiMessage('Error: ' . $e->getMessage());
                                report($e);
                            }
                        }),
                    Action::make('Reject')
                        ->label('Reject')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->visible(fn($record): bool => $record->status == BranchSalesReport::STATUS_PENDING)
                        ->form([
                            Textarea::make('notes')
                                ->label('Rejection Reason')
                                ->required(),
                        ])
                        ->action(function (array $data, BranchSalesReport $record) {
                            $record->update([
                                'status' => BranchSalesReport::STATUS_REJECTED,
                                'notes' => $data['notes'],
                            ]);
                            showWarningNotifiMessage('Report has been rejected.');
                        }),
                    Action::make('download')
                        ->label('Download Attachment')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($record) {
                            if (strlen($record->attachment) > 0) {
                                if (env('APP_ENV') == 'local') {
                                    $file_link = url('storage/' . $record->attachment);
                                } else if (env('APP_ENV') == 'production') {
                                    $file_link = url('New-Res-System/public/storage/' . $record->attachment);
                                }
                                return redirect(url($file_link));
                            }
                        })
                        ->hidden(fn($record) => !(strlen($record->attachment) > 0))
                        ->color('gray'),
                ])
            ]);
    }
}
