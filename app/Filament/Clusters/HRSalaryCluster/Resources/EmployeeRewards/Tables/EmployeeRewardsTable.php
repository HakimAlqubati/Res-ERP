<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Tables;

use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\EmployeeReward;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmployeeRewardsTable
{
    public static function configure(Table $table): Table
    {
        return $table->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employee.name')
                    ->label(__('lang.employee'))
                    ->searchable()
                    ->sortable()->toggleable(),
                TextColumn::make('branch.name')
                    ->label(__('lang.branch'))
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('rewardType.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reward_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('month')
                    ->label('Month')
                    ->getStateUsing(function ($record) {
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        $monthName = $months[(int)$record->month] ?? $record->month;
                        return $record->year ? "{$monthName}-{$record->year}" : $monthName;
                    })
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending'  => 'warning',
                        default    => 'gray',
                    })
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('creator.name')
                    ->label('Added By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('employee_id')
                    ->relationship('employee', 'name')->label(__('lang.employee'))
                    ->searchable()
                    ->preload(),
                ],FiltersLayout::Modal)
                ->filtersFormColumns(4)
            ->recordActions([
                EditAction::make()
                    ->visible(fn($record) => $record->status === EmployeeReward::STATUS_PENDING),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === EmployeeReward::STATUS_PENDING)
                    ->action(function ($record) {
                        try {
                            DB::beginTransaction();
                            $record->approve(auth()->id());
                            
                            // Helper function if available, else standard notification
                            if (function_exists('showSuccessNotifiMessage')) {
                                showSuccessNotifiMessage('Reward Approved successfully');
                            }
                            
                            DB::commit();
                        } catch (Throwable $th) {
                            DB::rollBack();
                            throw $th;
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === EmployeeReward::STATUS_PENDING)
                    ->form([
                        Textarea::make('rejected_reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            DB::beginTransaction();
                            $record->reject(auth()->id(), $data['rejected_reason']);
                            
                            if (function_exists('showSuccessNotifiMessage')) {
                                showSuccessNotifiMessage('Reward Rejected');
                            }
                            
                            DB::commit();
                        } catch (Throwable $th) {
                            DB::rollBack();
                            throw $th;
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
