<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Tables;

use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use function Laravel\Prompts\select;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Unique;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages\ListEmployeeOvertimes;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages\CreateEmployeeOvertime;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeTable
{
    public static function configure($table)
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('')
                    ->sortable()
                    ->wrap()
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type_value')
                    ->label('Type')
                    ->sortable()->hidden()
                    ->wrap()
                    ->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->wrap()
                    ->searchable()->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('date')
                    ->label('Date')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('start_time')
                    ->label('Checkin')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('end_time')
                    ->label('Checkout')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('hours')
                    ->label('Hours')
                    ->sortable()->toggleable(isToggledHiddenByDefault: false)->alignCenter()
                    ->summarize(Sum::make()
                        ->label('')
                        ->query(function (\Illuminate\Database\Query\Builder $query) {
                            return $query->select('hours');
                        }))
                    // ->icon(Heroicon::Clock)
                    ->iconPosition(IconPosition::After),

                IconColumn::make('approved')->toggleable(isToggledHiddenByDefault: false)
                    ->boolean()->alignCenter(true)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                TextColumn::make('approvedBy.name')
                    ->label('Approved by')
                    ->wrap()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('approved_at')->wrap()
                    ->label('Approved at')->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')->wrap()
                    ->label('Created at')->toggleable(isToggledHiddenByDefault: true),

            ])
            ->selectable()
            ->filters([
                TrashedFilter::make()->visible(fn(): bool => isSuperAdmin()),
                SelectFilter::make('branch_id')
                    ->label('Branch')->multiple()
                    ->options(Branch::where('active', 1)->forBranchManager('id')->get()->pluck('name', 'id')),
                SelectFilter::make('type')->label('Type')->options(EmployeeOvertime::getTypes()),
                SelectFilter::make('approved')
                    ->label('Status')->multiple()
                    ->options(
                        [
                            1 => 'Approved',
                        ]
                    ),

                Filter::make('date')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('From')->default(null)
                            ->placeholder('From'),
                        DatePicker::make('created_until')
                            ->label('To')
                            ->placeholder('To')->default(null),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('hr_employee_overtime.date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('hr_employee_overtime.date', '<=', $date),
                            );
                    }),
                SelectFilter::make('employee_id')->label('Employee')
                    ->preload()
                    ->getSearchResultsUsing(function ($search = null) {
                        return Employee::query()
                            ->where('active', 1)
                            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->limit(20)

                            ->get()
                            ->mapWithKeys(fn($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                    })

                    ->hidden(fn() => isStuff() || isMaintenanceManager())
                    ->searchable(),
            ], FiltersLayout::Modal)
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Action::make('Edit')->visible(fn(): bool => (isSuperAdmin() || isBranchManager()))
                //     ->schema(function ($record) {
                //         return [
                //             TextInput::make('hours')->default($record->hours),
                //         ];
                //     })->action(function ($record, $data) {
                //         // dd($data['hours'],$data,$record);
                //         return $record->update(['hours' => $data['hours']]);
                //     }),
                Action::make('Approve')
                    ->databaseTransaction()
                    ->label(function ($record) {
                        if ($record->approved == 1) {
                            return 'Rollback approved';
                        } else {
                            return 'Approve';
                        }
                    })
                    ->icon(function ($record) {
                        if ($record->approved == 1) {
                            return 'heroicon-o-x-mark';
                        } else {
                            return 'heroicon-o-check-badge';
                        }
                    })->color(function ($record) {
                        if ($record->approved == 1) {
                            return 'gray';
                        } else {
                            return 'info';
                        }
                    })
                    ->button()
                    ->requiresConfirmation()
                    ->size(Size::Small)
                    ->hidden(function ($record) {
                        // if ($record->approved == 1) {
                        //     return true;
                        // }
                        if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
                            return false;
                        }
                        return true;
                    })
                    ->action(function (Model $record) {
                        if ($record->approved == 1) {
                            $record->update(['approved' => 0, 'approved_by' => null, 'approved_at' => null]);
                        } else {
                            $record->update(['approved' => 1, 'approved_by' => auth()->user()->id, 'approved_at' => now()]);
                        }
                    }),

                ActionGroup::make([
                    DeleteAction::make(),
                    ForceDeleteAction::make()->visible(fn(): bool => (isSuperAdmin())),
                    RestoreAction::make()->visible(fn(): bool => (isSuperAdmin())),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make()->visible(fn(): bool => (isSuperAdmin())),
                    RestoreBulkAction::make()->visible(fn(): bool => (isSuperAdmin())),
                    BulkAction::make('Approve')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-badge')
                        ->action(fn(Collection $records) => $records->each->update(['approved' => 1, 'approved_by' => auth()->user()->id, 'approved_at' => now()]))
                        ->hidden(function () {
                            if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
                                return false;
                            }
                            return true;
                        }),
                    BulkAction::make('Rollback approved')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-mark')
                        ->action(fn(Collection $records) => $records->each->update(['approved' => 0, 'approved_by' => null, 'approved_at' => null]))
                        ->hidden(function () {
                            if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
                                return false;
                            }
                            return true;
                        }),
                ]),
            ]);
    }
}
