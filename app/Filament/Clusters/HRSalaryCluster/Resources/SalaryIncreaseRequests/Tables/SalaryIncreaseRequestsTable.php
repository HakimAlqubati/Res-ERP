<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources\SalaryIncreaseRequests\Tables;

use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\SalaryIncreaseRequest;
use App\Modules\HR\SalaryIncrease\Contracts\SalaryIncreaseServiceInterface;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class SalaryIncreaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
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
                    ->sortable(),

                TextColumn::make('branch.name')
                    ->label(__('lang.branch'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('old_salary')
                    ->label(__('Old Salary'))
                    ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('increase_amount')
                    ->label(__('Increase'))
                    ->formatStateUsing(fn ($state) => '+' . formatMoneyWithCurrency($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('new_salary')
                    ->label(__('New Salary'))
                    ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('effective_date')
                    ->label(__('Effective Date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('lang.status'))
                    ->badge()
                    ->icon(fn (string $state): string => SalaryIncreaseRequest::getStatusIcon($state))
                    ->formatStateUsing(fn (?string $state): string => SalaryIncreaseRequest::getStatusLabel($state))
                    ->color(fn (?string $state): string => SalaryIncreaseRequest::getStatusColor($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('createdBy.name')
                    ->label(__('Added By'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label(__('lang.status'))
                    ->options(SalaryIncreaseRequest::statusOptions()),
                SelectFilter::make('employee_id')
                    ->label(__('lang.employee'))
                    ->relationship('employee', 'name', fn (Builder $query) => $query->forBranchManager())
                    ->searchable()
                    ->preload(),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(3)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (SalaryIncreaseRequest $record): bool => $record->isPending()),

                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SalaryIncreaseRequest $record): bool => $record->isPending() && static::canDecide())
                    ->action(function (SalaryIncreaseRequest $record): void {
                        try {
                            app(SalaryIncreaseServiceInterface::class)->approve($record->id, auth()->id());

                            Notification::make()
                                ->title(__('Salary increase approved and employee salary updated.'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(__('Error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SalaryIncreaseRequest $record): bool => $record->isPending() && static::canDecide())
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label(__('Rejection Reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (SalaryIncreaseRequest $record, array $data): void {
                        try {
                            app(SalaryIncreaseServiceInterface::class)->reject(
                                $record->id,
                                (string) $data['rejection_reason'],
                                auth()->id()
                            );

                            Notification::make()
                                ->title(__('Salary increase request rejected.'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(__('Error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
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

    private static function canDecide(): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager() || isHR() || isOwner();
    }
}
