<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Models\Branch;
use App\Models\EmployeeAdvanceInstallment;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvanceInstallmentResource extends Resource
{
    protected static ?string $model = EmployeeAdvanceInstallment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?string $label = 'Advance Installments';

    protected static ?string $pluralLabel = 'Advance Installments';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('lang.installments') ?? 'Advance Installments';
    }

    public static function getModelLabel(): string
    {
        return __('lang.installment') ?? 'Installment';
    }

    public static function getPluralModelLabel(): string
    {
        return __('lang.installments') ?? 'Advance Installments';
    }

    protected static ?string $recordTitleAttribute = 'id';

    protected static bool $isGloballySearchable = true;

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'advanceRequest.code',
            'employee.name',
            'employee.employee_no',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var EmployeeAdvanceInstallment $record */
        $code = $record->advanceRequest?->code ?? ('#' . $record->id);
        $employeeName = $record->employee?->name ?? '';

        return $employeeName ? "{$code} — {$employeeName} (#{$record->sequence})" : $code;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var EmployeeAdvanceInstallment $record */
        $details = [];

        if ($record->employee?->name) {
            $details[__('lang.employee')] = $record->employee->name;
        }

        if ($record->installment_amount) {
            $details[__('lang.installment_amount')] = formatMoneyWithCurrency($record->installment_amount);
        }

        if ($record->due_date) {
            $details[__('lang.due_date')] = $record->due_date->format('Y-m-d');
        }

        if ($record->status) {
            $details[__('lang.status')] = ucfirst($record->status);
        }

        return $details;
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return static::getUrl('index', [
            'tableSearch' => $record->advanceRequest?->code ?? $record->employee?->name,
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading(__('lang.no_data'))
            ->striped()
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('lang.id'))
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
               TextColumn::make('advanceRequest.code')
                    ->label(__('lang.code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('employee.employee_no')
                    ->label(__('lang.employee_no'))
                    ->searchable()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employee.name')
                    ->label(__('lang.employee'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('employee.branch.name')
                    ->label(__('lang.branch'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

              
                TextColumn::make('sequence')
                    ->label('#')
                    ->alignCenter()
                    ->sortable()
                    ->badge()->hidden()
                    ->color('gray'),

                TextColumn::make('installment_amount')
                    ->label(__('lang.amount'))
                    ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state))
                    ->sortable()
                    ->alignEnd()
                    ->weight(FontWeight::Bold),

                TextColumn::make('original_amount')
                    ->label(__('lang.original_amount'))
                    ->formatStateUsing(fn ($state) => formatMoneyWithCurrency($state))
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('due_date')
                    ->label(__('lang.due_date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->color(fn (EmployeeAdvanceInstallment $record) => $record->isOverdue() ? 'danger' : null)
                    ->description(fn (EmployeeAdvanceInstallment $record) => $record->isOverdue() ? 'Overdue' : null),

                TextColumn::make('year')
                    ->label(__('lang.year'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('month')
                    ->label('Month')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('lang.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeAdvanceInstallment::STATUS_PAID => 'success',
                        EmployeeAdvanceInstallment::STATUS_SCHEDULED => 'warning',
                        EmployeeAdvanceInstallment::STATUS_SKIPPED => 'info',
                        EmployeeAdvanceInstallment::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        EmployeeAdvanceInstallment::STATUS_PAID => __('lang.paid'),
                        EmployeeAdvanceInstallment::STATUS_SCHEDULED => 'Scheduled',
                        EmployeeAdvanceInstallment::STATUS_SKIPPED => 'Skipped',
                        EmployeeAdvanceInstallment::STATUS_CANCELLED => 'Cancelled',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                IconColumn::make('is_paid')
                    ->label(__('lang.is_paid'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_method')
                    ->label(__('lang.payment_method'))
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => EmployeeAdvanceInstallment::$paymentMethods[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paid_date')
                    ->label(__('lang.paid_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paidByUser.name')
                    ->label(__('lang.paid_by'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payroll')
                    ->label(__('lang.payroll'))
                    ->formatStateUsing(fn (EmployeeAdvanceInstallment $record) => $record->payroll ? "{$record->payroll->year}-{$record->payroll->month}" : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->label(__('lang.notes'))
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('skipped_reason')
                    ->label('Skipped Reason')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cancelled_reason')
                    ->label('Cancelled Reason')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferFilters(true)
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->options(fn () => Branch::where('active', 1)->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereHas(
                                'employee',
                                fn (Builder $q) => $q->where('branch_id', $value)
                            )
                        );
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('employee_id')
                    ->label(__('lang.employee'))
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('lang.status'))
                    ->options([
                        EmployeeAdvanceInstallment::STATUS_SCHEDULED => 'Scheduled',
                        EmployeeAdvanceInstallment::STATUS_PAID      => __('lang.paid'),
                        EmployeeAdvanceInstallment::STATUS_SKIPPED   => 'Skipped',
                        EmployeeAdvanceInstallment::STATUS_CANCELLED => 'Cancelled',
                    ]),

                SelectFilter::make('payment_method')
                    ->label(__('lang.payment_method'))
                    ->options(EmployeeAdvanceInstallment::$paymentMethods),

                SelectFilter::make('is_paid')
                    ->label(__('lang.is_paid'))
                    ->options([
                        '1' => __('lang.paid'),
                        '0' => __('lang.unpaid'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === '1') {
                            return $query->where('is_paid', true);
                        } elseif ($data['value'] === '0') {
                            return $query->where('is_paid', false);
                        }

                        return $query;
                    }),

                Filter::make('due_date_range')
                    ->form([
                        DatePicker::make('due_from')->label('Due From'),
                        DatePicker::make('due_to')->label('Due To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['due_from'], fn (Builder $q, $date) => $q->whereDate('due_date', '>=', $date))
                            ->when($data['due_to'], fn (Builder $q, $date) => $q->whereDate('due_date', '<=', $date));
                    })
                    ->columns(2),

                SelectFilter::make('year')
                    ->label(__('lang.year'))
                    ->options(fn () => EmployeeAdvanceInstallment::distinct()->whereNotNull('year')->orderByDesc('year')->pluck('year', 'year')->toArray()),

                SelectFilter::make('month')
                    ->label(__('lang.month'))
                    ->options([
                        1 => '01 - January',
                        2 => '02 - February',
                        3 => '03 - March',
                        4 => '04 - April',
                        5 => '05 - May',
                        6 => '06 - June',
                        7 => '07 - July',
                        8 => '08 - August',
                        9 => '09 - September',
                        10 => '10 - October',
                        11 => '11 - November',
                        12 => '12 - December',
                    ]),

                Filter::make('overdue')
                    ->label('Overdue Only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->overdue()),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(2);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager() || isHR();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeAdvanceInstallments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'employee:id,name,employee_no,branch_id',
                'employee.branch:id,name',
                'advanceRequest:id,code,advance_amount',
                'payroll:id,year,month',
                'paidByUser:id,name',
            ])
            ->when(function_exists('isBranchManager') && isBranchManager() && !isSuperAdmin() && !isSystemManager(), function ($query) {
                $query->whereHas('employee', fn ($q) => $q->where('branch_id', auth()->user()?->branch_id));
            });
    }
}
