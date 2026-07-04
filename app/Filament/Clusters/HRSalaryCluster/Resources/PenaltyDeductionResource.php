<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages\CreatePenaltyDeduction;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages\EditPenaltyDeduction;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages\ListPenaltyDeductions;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\PenaltyDeduction;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Throwable;

class PenaltyDeductionResource extends Resource
{
    protected static ?string $model = PenaltyDeduction::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Fieldset::make()->columnSpanFull()->label('')->columns(4)->schema([
                DatePicker::make('date')
                    ->label('Date')
                    ->default(now()->toDateString())
                    ->maxDate(now()->toDateString())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $state) {
                        if ($state) {
                            $date = Carbon::parse($state);
                            $set('year', $date->year);
                            $set('month', $date->month);
                        }
                    }),

                Select::make('employee_id')
                    ->label(__('lang.employee'))
                    ->options(function ($get) {
                        $id = $get('employee_id');

                        return Employee::query()
                            ->where(function ($query) use ($id) {
                                $query->where('active', 1);
                                if ($id) {
                                    $query->orWhere('id', $id);
                                }
                            })
                            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$id])
                            ->limit(5)
                            ->get()
                            ->mapWithKeys(fn ($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                    })
                    ->getSearchResultsUsing(function ($get, $search = null) {
                        $id = $get('employee_id');

                        return Employee::query()
                            ->where(function ($query) use ($id) {
                                $query->where('active', 1);
                                if ($id) {
                                    $query->orWhere('id', $id);
                                }
                            })
                            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$id])
                            ->limit(5)
                            ->get()
                            ->mapWithKeys(fn ($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                    })
                    ->searchable()
                    ->preload()->live()
                    ->required(),
                Select::make('deduction_id')->label('Deduction')
                    ->live()->afterStateUpdated(function ($get, $set, $state) {
                        $deduction = Deduction::find($state);
                        $defaultAmount = 0;
                        if ($deduction->is_percentage) {
                            $defaultAmount = $deduction->percentage;
                        } else {
                            $defaultAmount = $deduction->amount;
                        }
                        // $set('penalty_amount', $defaultAmount);
                        $set('penalty_amount', 0);
                        $set('deduction_type', PenaltyDeduction::DEDUCTION_TYPE_FIXED_AMOUNT);
                    })
                    ->options(Deduction::penalty()->get()->pluck('name', 'id'))
                    ->required(),
            ]),
            Fieldset::make()->label('')->columnSpanFull()->columns(4)->schema([

                Select::make('deduction_type')
                    ->options(PenaltyDeduction::getDeductionTypeOptions())->default(PenaltyDeduction::DEDUCTION_TYPE_FIXED_AMOUNT)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $get, $state) {
                        if (in_array($state, [PenaltyDeduction::DEDUCTION_TYPE_FIXED_AMOUNT, PenaltyDeduction::DEDUCTION_TYPE_SPECIFIC_PERCENTAGE])) {
                            $set('penalty_amount', 0);
                        }
                    }),

                TextInput::make('percentage')->label('Specify percentage')
                    ->helperText('Percentage of employee basic salary')
                    ->visible(fn ($get): bool => $get('deduction_type') == PenaltyDeduction::DEDUCTION_TYPE_SPECIFIC_PERCENTAGE)
                    ->numeric()->minValue(0.5)
                    ->maxValue(100)->required()->live()->afterStateUpdated(function ($get, $set, $state) {
                        $employee = Employee::find($get('employee_id'));
                        if ($employee) {
                            $salary = $employee->salary;
                            $percentageAmount = ($salary * $state) / 100;
                            $set('penalty_amount', $percentageAmount);
                        }
                    }),
                TextInput::make('penalty_amount')

                    ->numeric()
                    ->required(),

                Textarea::make('description')
                    ->label('Reason')->columnSpanFull()
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->striped()
            ->recordUrl(null)
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')
                    ->alignCenter(true)->label('ID#')->searchable()->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()->toggleable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()->toggleable()
                    ->sortable(),
                TextColumn::make('deduction.name')
                    ->label('Deduction')
                    ->searchable()->toggleable(isToggledHiddenByDefault:true)

                    ->sortable(),
                TextColumn::make('penalty_amount')
                    ->label('Amount')->toggleable()
                    // ->money('MY')
                    ->alignCenter(true)
                    ->formatStateUsing(fn($state)=>formatMoneyWithCurrency($state))
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->getStateUsing(function ($record) {
                        $months = getMonthArrayWithKeys();
                        $monthKey = str_pad((string) $record->month, 2, '0', STR_PAD_LEFT);

                        return $months[$monthKey] ?? $record->month;
                    })->toggleable()
                    ->alignCenter(true)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()->toggleable()
                    ->alignCenter(true)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('date')->toggleable()
                    ->date()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($record) => $record->created_by ? User::find($record->created_by)?->name : '-')
                    ->sortable(),
                TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault:true)
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('employee_id')
                    ->label(__('Employee'))
                    ->searchable()
                    ->options(Employee::pluck('name', 'id')),
                SelectFilter::make('deduction_id')
                    ->label(__('Deduction'))
                    ->searchable()
                    ->options(Deduction::penalty()->pluck('name', 'id')),
                SelectFilter::make('year')
                    ->label(__('Year'))
                    ->options(array_combine(
                        range(date('Y') - 3, date('Y') + 1),
                        range(date('Y') - 3, date('Y') + 1)
                    )),
                SelectFilter::make('month')
                    ->label(__('Month'))
                    ->options(getMonthArrayWithIntKeys()),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(PenaltyDeduction::getStatusOptions()),
                ],FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordActions([
                EditAction::make()->visible(fn ($record): bool => $record->status == PenaltyDeduction::STATUS_PENDING),
                Action::make('approve')
                    ->requiresConfirmation()->button()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {

                        try {
                            DB::beginTransaction();
                            $record->approvePenalty(auth()->id(), now());
                            showSuccessNotifiMessage('Done');
                            DB::commit();
                        } catch (Throwable $th) {
                            DB::rollBack();
                            showWarningNotifiMessage('Faild', $th->getMessage());
                            throw $th;
                        }
                    }),
                Action::make('reject')->button()
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->schema([
                        DateTimePicker::make('rejected_at')
                            ->label('Rejected At')
                            ->default(now())
                            ->required(),
                        Textarea::make('rejected_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            DB::beginTransaction();
                            $record->rejectPenalty(auth()->id(), $data['rejected_reason'], $data['rejected_at']);
                            showSuccessNotifiMessage('Done');
                            DB::commit();
                        } catch (Throwable $th) {
                            DB::rollBack();
                            showWarningNotifiMessage('Failed', $th->getMessage());
                            throw $th;
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenaltyDeductions::route('/'),
            'create' => CreatePenaltyDeduction::route('/create'),
            'edit' => EditPenaltyDeduction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
}
