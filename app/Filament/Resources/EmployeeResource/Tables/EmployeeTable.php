<?php

namespace App\Filament\Resources\EmployeeResource\Tables;

use App\Filament\Resources\EmployeeResource;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeFileType;
use App\Models\User;
use App\Models\UserType;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Throwable;

class EmployeeTable
{
    public static function configure(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])

            ->defaultSort('id', 'desc')
            ->recordUrl(fn (Employee $record): string => EmployeeResource::getUrl('view', ['record' => $record]))
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')
                    ->sortable()
                    ->label(__('lang.id'))->alignCenter()->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('avatar')->label('Avatar')
                    ->circular()
                    ->disk('s3')
                    ->toggleable()
                    ->defaultImageUrl(asset('imgs/avatar.png')),
                TextColumn::make('employee_no')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.employee_no'))->alignCenter()
                    ->sortable()->searchable()
                    ->searchable(isIndividual: false, isGlobal: true),

                TextColumn::make('name')
                    ->sortable()->searchable()
                    ->label(__('lang.full_name'))->wrap(false)
                    // ->color(fn($record): string => $record->active ? 'primary' : 'warning')
                    ->color(function ($record) {
                        if ($record->pendingTerminationRequest) {
                            return 'warning';
                        }
                        if (! $record->active) {
                            return 'danger';
                        }

                        return 'primary';
                    })
                    // ->words(3)
                    ->limit(20)
                    ->weight(FontWeight::Medium)->tooltip(fn ($state) => $state)
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('branch.name')
                    ->label(__('lang.branch'))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('manager.name')
                    ->label(__('lang.manager'))
                    ->toggleable(isToggledHiddenByDefault: true)
                // ->searchable()
                ,

                TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    // ->copyable()
                    ->sortable()->searchable()
                    ->limit(20)

                    ->default('-')
                    // ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: false, isGlobal: true)
                    // ->copyable()
                    ->copyMessage(__('lang.email_address_copied'))
                    ->copyMessageDuration(1500)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('phone_number')->label(__('lang.phone_number'))
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->searchable(isIndividual: false)
                    ->default('_')
                    ->toggleable(isToggledHiddenByDefault: false)
                    // ->copyable()
                    ->copyMessage(__('lang.phone_number_copied'))
                    ->copyMessageDuration(1500)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('period_names')
                    ->label(__('lang.shift'))
                    ->badge()
                    ->tooltip(fn ($record) => $record->full_period_names)
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('average_daily_supposed_hours')
                    ->label(__('lang.currently_shift_hours'))
                    ->badge()
                    ->color('info')->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(false),
                TextColumn::make('join_date')->sortable()->label(__('lang.start_date'))
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false),
                TextColumn::make('serviceTermination.termination_date')
                    ->label(__('lang.termination_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('serviceTermination.termination_reason')
                    ->label(__('lang.termination_reason'))
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salary')->sortable()->label(__('lang.salary'))
                    ->sortable()->searchable()
                    // ->money(fn(): string => getDefaultCurrency())
                    ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true),

                TextColumn::make('working_hours')->label(__('lang.working_hours'))->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true)
                    ->action(function ($record) {

                        $hoursCount = abs($record->hours_count);
                        $record->update([
                            'working_hours' => $hoursCount,
                        ]);
                    }),
                TextColumn::make('working_days')->label(__('lang.working_days'))->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false)->alignCenter(true),

                TextColumn::make('job_title')
                    ->label(__('lang.job_title'))
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: false),
                TextColumn::make('employeeType.name')
                    ->label(__('lang.role_type'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('unrequired_documents_count')->label(__('lang.unrequired_docs'))->alignCenter(true)
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->formatStateUsing(function ($state) {

                //         return '('.$state.') docs of '.EmployeeFileType::getCountByRequirement()['unrequired_count'];
                //     }),
                // TextColumn::make('required_documents_count')->label(__('lang.required_docs'))->alignCenter(true)
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->formatStateUsing(function ($state) {

                //         return '('.$state.') docs of '.EmployeeFileType::getCountByRequirement()['required_count'];
                //     }),
                IconColumn::make('active')
                    ->label(__('lang.active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nationality')->sortable()->searchable()
                    ->label(__('lang.nationality'))
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),

                IconColumn::make('has_auto_weekly_leave')
                    ->label(__('lang.has_auto_weekly_leave'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter(true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_indexed_in_aws')
                    ->label(__('lang.is_indexed_in_aws'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->alignCenter(true)
                    ->sortable()
                    ->visible(fn () => isHakimOrAdel())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])->deferFilters(true)
            ->filters([

                TrashedFilter::make()
                    ->visible(fn (): bool => (isSystemManager() || isSuperAdmin() || isBranchManager())),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options(Branch::active()->forBranchManager('id')->get()->pluck('name', 'id')->toArray()),
                SelectFilter::make('nationality')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->label(__('lang.nationality'))
                    ->options(getNationalities()),
                SelectFilter::make('active')
                    ->options([
                        1 => __('lang.active'),
                        0 => __('lang.terminated'),
                        'pending_termination' => __('lang.termination_requests'),
                    ])
                    ->default(1)
                    ->label(__('lang.active'))
                    ->query(function ($query, array $data) {
                        if ($data['value'] === '1') {
                            $query->where('active', 1);
                        } elseif ($data['value'] === '0') {
                            $query->where('active', 0);
                        } elseif ($data['value'] === 'pending_termination') {
                            $query->whereHas('pendingTerminationRequest');
                        }
                    }),
                SelectFilter::make('employee_type')
                    ->label(__('lang.role_type'))
                    ->options(UserType::where('active', 1)->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),
                // SelectFilter::make('manager_id')
                //     ->label(__('lang.manager'))
                     
                //     ->options(Employee::whereIn('employee_type', [1, 2])
                //         ->pluck('name', 'id')->toArray())
                //     ->searchable()
                //     ->multiple(),
                Filter::make('me')
                    ->label(__('lang.me'))
                    ->toggle()
                    ->query(fn ($query) => $query->where('id', auth()->user()?->employee?->id)),
                Filter::make('has_employee_pass')
                    ->label(__('lang.has_employee_pass'))
                    ->toggle()
                    ->query(fn ($query) => $query->where('has_employee_pass', 1)),
                Filter::make('my_employees')
                    ->label(__('lang.my_employees'))
                    ->toggle()
                    ->query(fn ($query) => $query->where('manager_id', auth()->user()?->employee?->id)),

            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->headerActions(HeaderActions::actions())
            ->recordActions([
                RecordActions::actionGroup(),

            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                // ExportBulkAction::make(),
                RestoreBulkAction::make(),
                BulkAction::make('activate')
                    ->label(__('lang.activate'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $activatedCount = 0;

                        foreach ($records as $record) {
                            try {
                                // Skip already active employees
                                if ($record->active == 1) {
                                    continue;
                                }

                                // activate employee
                                $record->update(['active' => 1]);
                                $activatedCount++;

                                // if employee has linked user, restore (if trashed) and activate user
                                if ($record->user_id) {
                                    $user = User::withTrashed()->find($record->user_id);
                                    if ($user) {
                                        if (method_exists($user, 'trashed') && $user->trashed()) {
                                            $user->restore();
                                        }
                                        $user->update(['active' => 1]);
                                    }
                                }
                            } catch (Throwable $e) {
                                report($e);
                            }
                        }

                        showSuccessNotifiMessage("{$activatedCount} employees activated.");
                    }),
                BulkAction::make('createUser')
                    ->label(__('lang.create_user'))
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $createdCount = 0;
                        $skippedCount = 0;

                        foreach ($records as $record) {
                            try {
                                if (! $record->has_user && empty($record->user_id)) {
                                    $user = $record->createLinkedUser([]);
                                    if ($user) {
                                        $createdCount++;
                                    }
                                } else {
                                    $skippedCount++;
                                }
                            } catch (Throwable $e) {
                                report($e);
                            }
                        }

                        if ($createdCount > 0) {
                            showSuccessNotifiMessage("{$createdCount} users created successfully.".($skippedCount > 0 ? " ({$skippedCount} skipped)" : ''));
                        } else {
                            showWarningNotifiMessage('No users were created. Selected employees might already have accounts.');
                        }
                    })
                    ->visible(fn () => isHakimOrAdel()),
                ForceDeleteBulkAction::make()->visible(fn () => EmployeeResource::canForceDeleteAny()),
            ]);
    }
}
