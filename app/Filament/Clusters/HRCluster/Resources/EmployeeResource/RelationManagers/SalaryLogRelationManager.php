<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Models\EmployeeSalaryLog;
use App\Services\HR\Payroll\PayrollLockGuard;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class SalaryLogRelationManager extends RelationManager
{
    protected static string $relationship = 'salaryLogs';

    protected static ?string $title = 'Salary History';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager() || isBranchManager() || isOwner();
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->salaryLogs()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->salaryLogs()->count() > 0 ? 'info' : 'gray';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('effective_from')
            ->striped()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderByDesc('effective_from')->orderByDesc('id'))
            ->columns([
                TextColumn::make('effective_from')
                    ->label(__('Effective Month'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('effective_date')
                    ->label(__('Effective Date'))
                    ->date('Y-m-d')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('old_salary')
                    ->label(__('Old Salary'))
                    ->formatStateUsing(fn ($state) => $state !== null ? formatMoneyWithCurrency($state) : '-')
                    ->alignCenter(),

                TextColumn::make('new_salary')
                    ->label(__('New Salary'))
                    ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state))
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('change_amount')
                    ->label(__('Change'))
                    ->formatStateUsing(function ($state): string {
                        if ($state === null) {
                            return '-';
                        }
                        $val = (float) $state;
                        $prefix = $val > 0 ? '+' : '';

                        return $prefix.formatMoneyWithCurrency($val);
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state > 0 => 'success',
                        (float) $state < 0 => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('source')
                    ->label(__('Source'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => EmployeeSalaryLog::getSourceLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        EmployeeSalaryLog::SOURCE_SALARY_INCREASE_REQUEST => 'success',
                        EmployeeSalaryLog::SOURCE_DIRECT_EDIT => 'warning',
                        EmployeeSalaryLog::SOURCE_REHIRE => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('notes')
                    ->label(__('lang.notes'))
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->placeholder('-'),

                TextColumn::make('createdBy.name')
                    ->label(__('lang.created_by'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label(__('Delete'))
                    ->requiresConfirmation()
                    ->visible(function (EmployeeSalaryLog $record): bool {
                        if (! (isSuperAdmin() || isSystemManager() || isFinanceManager())) {
                            return false;
                        }

                        if ($record->salary_increase_request_id !== null) {
                            return false;
                        }

                        if (! $record->effective_from) {
                            return false;
                        }

                        $eff = Carbon::parse($record->effective_from)->startOfMonth();
                        if (! $eff->gt(now()->startOfMonth())) {
                            return false;
                        }

                        $latestId = EmployeeSalaryLog::query()
                            ->where('employee_id', (int) $record->employee_id)
                            ->orderByDesc('effective_from')
                            ->orderByDesc('id')
                            ->value('id');

                        if ((int) $latestId !== (int) $record->id) {
                            return false;
                        }

                        return ! app(PayrollLockGuard::class)->isLockedFromPeriod((int) $record->employee_id, $eff->year, $eff->month);
                    })
                    ->action(function (EmployeeSalaryLog $record): void {
                        try {
                            $record->delete();
                            Notification::make()
                                ->title(__('lang.deleted_successfully'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(__('lang.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
