<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Tables;

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
use Illuminate\Support\Facades\DB;
use Throwable;

class EmployeeRewardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rewardType.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reward_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('month')
                    ->label('Month')
                    ->getStateUsing(function ($record) {
                        $months = [
                            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
                            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
                        ];
                        $monthKey = str_pad((string) $record->month, 2, '0', STR_PAD_LEFT);
                        return $months[$monthKey] ?? $record->month;
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
                    ->date()
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
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
            ])
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
