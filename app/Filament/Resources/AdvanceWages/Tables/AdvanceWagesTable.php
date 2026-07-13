<?php

namespace App\Filament\Resources\AdvanceWages\Tables;

use App\Models\AdvanceWage;
use App\Services\HR\Payroll\PayrollLockGuard;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AdvanceWagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('lang.employee'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('date')
                    ->label(__('Date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money(fn($record) => $record->employee?->branch?->currency ?? 'MYR')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(40)
                    ->tooltip(fn($record) => $record->reason)
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        AdvanceWage::STATUS_PENDING   => 'warning',
                        AdvanceWage::STATUS_SETTLED   => 'success',
                        AdvanceWage::STATUS_CANCELLED => 'danger',
                        default                       => 'gray',
                    })
                    ->formatStateUsing(fn($state) => AdvanceWage::statuses()[$state] ?? $state),

                TextColumn::make('settledPayroll.name')
                    ->label(__('Settled In'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('settled_at')
                    ->label(__('Settled At'))
                    ->dateTime('d-m-Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(AdvanceWage::statuses()),

                SelectFilter::make('year')
                    ->label(__('Year'))
                    ->options(collect(range(now()->year - 2, now()->year))->mapWithKeys(fn($y) => [$y => $y])),

                SelectFilter::make('month')
                    ->label(__('Month'))
                    ->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => now()->setMonth($m)->translatedFormat('F')])),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    )),
                Action::make('cancel')
                    ->label(__('Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(AdvanceWage $record) => in_array(
                        $record->status,
                        [AdvanceWage::STATUS_PENDING, AdvanceWage::STATUS_SETTLED]
                    ))
                    ->action(function (AdvanceWage $record): void {
                        $record->cancel();
                        Notification::make()->success()->title(__('Advance wage cancelled.'))->send();
                    })
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    )),
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(AdvanceWage $record) => in_array(
                        $record->status,
                        [AdvanceWage::STATUS_PENDING, AdvanceWage::STATUS_CANCELLED]
                    ))
                    ->action(function (AdvanceWage $record): void {
                        $record->update(['status' => AdvanceWage::STATUS_SETTLED]);
                        Notification::make()->success()->title(__('Advance wage approved.'))->send();
                    })
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    )),
                DeleteAction::make()
                    ->disabled(fn (AdvanceWage $record) => app(PayrollLockGuard::class)->isLocked(
                        (int) $record->employee_id,
                        (int) $record->year,
                        (int) $record->month
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
