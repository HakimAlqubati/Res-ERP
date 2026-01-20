<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Actions\Action;
use App\Filament\Clusters\HRTaskReport;
use App\Models\AdvanceRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeApplicationV2;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeAdvanceReportResource extends Resource
{
    protected static ?string $model = AdvanceRequest::class;
    // protected static ?string $slug = 'employee-advance-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Employee Advances';
    protected static ?string $pluralLabel = 'Employee Advances';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading(__('lang.no_data'))
            ->striped()
            ->columns([
                TextColumn::make('code')
                    ->label(__('lang.code'))
                    ->searchable()->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->copyable(),

                TextColumn::make('employee.employee_no')
                    ->label(__('lang.employee_no'))
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('employee.name')
                    ->label(__('lang.employee'))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(20),

                TextColumn::make('advance_amount')
                    ->label(__('lang.advance_amount'))
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),

                TextColumn::make('number_of_months_of_deduction')
                    ->label(__('lang.months'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('monthly_deduction_amount')
                    ->label(__('lang.monthly_deduction'))
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state)),

                TextColumn::make('paid_installments')
                    ->label(__('lang.paid_installments'))
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('remaining_total')
                    ->label(__('lang.remaining'))
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->alignEnd()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('deduction_starts_from')
                    ->label(__('lang.deduction_starts'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deduction_ends_at')
                    ->label(__('lang.deduction_ends'))
                    ->date()
                    ->sortable(),

                TextColumn::make('application.status')
                    ->label(__('lang.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferFilters(true)
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->options(fn() => Branch::where('active', 1)->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->whereHas(
                                'employee',
                                fn(Builder $q) => $q->where('branch_id', $value)
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

                SelectFilter::make('payment_status')
                    ->label(__('lang.payment_status'))
                    ->options(AdvanceRequest::getPaymentStatusOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value) => $query->paymentStatus($value)
                        );
                    }),

                \Filament\Tables\Filters\Filter::make('deduction_period')
                    ->form([
                        DatePicker::make('deduction_from')
                            ->label(__('lang.deduction_starts')),
                        DatePicker::make('deduction_to')
                            ->label(__('lang.deduction_ends')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['deduction_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('deduction_starts_from', '>=', $date)
                            )
                            ->when(
                                $data['deduction_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('deduction_ends_at', '<=', $date)
                            );  
                    })
                    ->columns(2),

                \Filament\Tables\Filters\Filter::make('amount_range')
                    ->form([
                        TextInput::make('min_amount')
                            ->label(__('lang.min_amount'))
                            ->numeric(),
                        TextInput::make('max_amount')
                            ->label(__('lang.max_amount'))
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('advance_amount', '>=', $amount)
                            )
                            ->when(
                                $data['max_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('advance_amount', '<=', $amount)
                            );
                    })
                    ->columns(2),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('details')
                    ->label(__('lang.installments'))
                    ->button()
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->schema(function ($record) {
                        $installments = $record->installments()->orderBy('sequence')->get();

                        return [
                            Repeater::make('installments')
                                ->label('')
                                ->table([
                                    TableColumn::make(__('lang.sequence'))
                                        ->width('8%'),
                                    TableColumn::make(__('lang.amount'))
                                        ->width('18%'),
                                    TableColumn::make(__('lang.due_date'))
                                        ->width('18%'),
                                    TableColumn::make(__('lang.paid'))
                                        ->width('10%'),
                                    TableColumn::make(__('lang.paid_date'))
                                        ->width('18%'),
                                    TableColumn::make(__('lang.status'))
                                        ->width('15%'),
                                ])
                                ->schema([
                                    TextInput::make('sequence')
                                        ->label('#')
                                        ->extraInputAttributes(['class' => 'text-center'])
                                        ->disabled(),
                                    TextInput::make('installment_amount')
                                        ->label(__('lang.amount'))
                                        ->disabled(),
                                    DatePicker::make('due_date')
                                        ->label(__('lang.due_date'))
                                        ->disabled(),
                                    TextInput::make('is_paid')
                                        ->label(__('lang.paid'))
                                        ->disabled()
                                        ->extraInputAttributes(['class' => 'text-center']),
                                    DatePicker::make('paid_date')
                                        ->label(__('lang.paid_date'))
                                        ->extraInputAttributes(['class' => 'text-center'])
                                        ->disabled(),
                                    TextInput::make('status')
                                        ->label(__('lang.status'))
                                        ->extraInputAttributes(['class' => 'text-center'])
                                        ->disabled(),
                                ])
                                ->defaultItems(count($installments))
                                ->columns(6)
                                ->default($installments->map(fn($inst) => [
                                    'sequence' => $inst->sequence,
                                    'installment_amount' => number_format($inst->installment_amount, 2),
                                    'due_date' => $inst->due_date?->format('Y-m-d'),
                                    'is_paid' => $inst->is_paid ? '✓' : '✗',
                                    'paid_date' => $inst->paid_date?->format('Y-m-d'),
                                    'status' => $inst->status,
                                ])->toArray()),
                        ];
                    })
                    ->modalHeading(__('lang.installment_details'))
                    ->disabledForm()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                // ✅ تأجيل قسط (Skip & Reschedule)
                Action::make('defer_installment')
                    ->label(__('lang.defer_installment'))
                    ->button()
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->schema(function ($record) {
                        $unpaidInstallments = $record->installments()
                            ->where('is_paid', false)
                            ->where('status', EmployeeAdvanceInstallment::STATUS_SCHEDULED)
                            ->orderBy('sequence')
                            ->get();

                        return [
                            Select::make('installment_id')
                                ->label(__('lang.select_installment'))
                                ->options(
                                    $unpaidInstallments->mapWithKeys(fn($inst) => [
                                        $inst->id => "#{$inst->sequence} - {$inst->due_date->format('Y-m')} (" . number_format($inst->installment_amount, 2) . ")"
                                    ])
                                )
                                ->required()
                                ->native(false),
                            Textarea::make('reason')
                                ->label(__('lang.reason'))
                                ->rows(2)
                                ->placeholder(__('lang.defer_reason_placeholder')),
                        ];
                    })
                    ->action(function (array $data, $record): void {
                        $installment = EmployeeAdvanceInstallment::find($data['installment_id']);

                        if (!$installment) {
                            Notification::make()
                                ->title(__('lang.error'))
                                ->body(__('lang.installment_not_found'))
                                ->danger()
                                ->send();
                            return;
                        }

                        $newInstallment = $installment->skipAndReschedule($data['reason'] ?? null);

                        Notification::make()
                            ->title(__('lang.success'))
                            ->body(__('lang.installment_deferred_success', [
                                'old_date' => $installment->due_date->format('Y-m'),
                                'new_date' => $newInstallment->due_date->format('Y-m'),
                            ]))
                            ->success()
                            ->send();
                    })
                    ->modalHeading(__('lang.defer_installment'))
                    ->modalDescription(__('lang.defer_installment_desc'))
                    ->modalSubmitActionLabel(__('lang.defer'))
                    ->visible(fn($record) => $record->remaining_total > 0),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeAdvanceReport::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return AdvanceRequest::query()
            ->with(['employee:id,name,employee_no,branch_id', 'employee.branch:id,name', 'application:id,status'])
            ->whereHas('application', function ($query) {
                $query->where('status', EmployeeApplicationV2::STATUS_APPROVED);
            });
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager();
    }
}
