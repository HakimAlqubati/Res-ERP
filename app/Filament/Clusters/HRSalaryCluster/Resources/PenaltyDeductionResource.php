<?php
namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Throwable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages\ListPenaltyDeductions;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages\CreatePenaltyDeduction;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages\EditPenaltyDeduction;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\PenaltyDeduction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PenaltyDeductionResource extends Resource
{
    protected static ?string $model = PenaltyDeduction::class;

    protected static string | \BackedEnum | null $navigationIcon                      = Heroicon::MinusCircle;

    protected static ?string $cluster = HRSalaryCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 9;
 
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                Fieldset::make()->label('')->columns(4)->schema([
                    Select::make('year')
                        ->options([
                            date('Y') - 1 => date('Y') - 1,
                            date('Y')     => date('Y'),
                            date('Y') + 1 => date('Y') + 1,
                        ])
                        ->default(date('Y'))
                        ->required(),

                    Select::make('month')
                        ->options(getMonthArrayWithKeys())
                        ->default(date('m'))
                        ->required(),

                    Forms\Components\Select::make('employee_id')
                        ->options(function ($search = null) {
                            return Employee::query()
                                ->where('active', 1)
                            // ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                        })
                        ->getSearchResultsUsing(function ($search = null) {
                            return Employee::query()
                                ->where('active', 1)
                                ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                        })
                        ->searchable()
                        ->preload()->live()
                        ->required(),
                    Select::make('deduction_id')->label('Deduction')
                        ->live()->afterStateUpdated(function ($get, $set, $state) {
                        $deduction     = Deduction::find($state);
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
                Fieldset::make()->label('')->columns(4)->schema([

                    DatePicker::make('date')->label('Date')->default(now()->toDateString())->maxDate(now()->toDateString()),
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
                        ->visible(fn($get): bool => $get('deduction_type') == PenaltyDeduction::DEDUCTION_TYPE_SPECIFIC_PERCENTAGE)
                        ->numeric()->minValue(0.5)
                        ->maxValue(100)->required()->live()->afterStateUpdated(function ($get, $set, $state) {
                        $employee = Employee::find($get('employee_id'));
                        if ($employee) {
                            $salary           = $employee->salary;
                            $percentageAmount = ($salary * $state) / 100;
                            $set('penalty_amount', $percentageAmount);
                        }
                    }),
                    Forms\Components\TextInput::make('penalty_amount')

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
                TextColumn::make('id')
                    ->alignCenter(true)->label('ID#')->searchable()->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()->toggleable()
                    ->sortable(),
                TextColumn::make('deduction.name')
                    ->label('Deduction')
                    ->searchable()->toggleable()

                    ->sortable(),
                TextColumn::make('penalty_amount')
                    ->label('Amount')->toggleable()
                    // ->money('MY')
                    ->alignCenter(true)
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->getStateUsing(function ($record) {
                        return getMonthArrayWithKeys()[$record->month];
                    })->toggleable()
                    ->alignCenter(true)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()->toggleable()
                    ->alignCenter(true)
                    ->color(fn(string $state): string => match ($state) {
                        'approved'                        => 'success',
                        'rejected'                        => 'danger',
                        'pending'                         => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('date')->toggleable()
                    ->date()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($record) => $record->created_by ? User::find($record->created_by)?->name : '-')
                    ->sortable(),
                TextColumn::make('created_at')->toggleable()
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->options(Employee::all()->pluck('name', 'id')),
                SelectFilter::make('deduction_id')
                    ->options(Deduction::penalty()->get()->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make()->visible(fn($record): bool => $record->status == PenaltyDeduction::STATUS_PENDING),
                Action::make('approve')
                    ->requiresConfirmation()->button()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->status === 'pending')
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
                    ->visible(fn($record) => $record->status === 'pending')
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
            'index'  => Pages\ListPenaltyDeductions::route('/'),
            'create' => Pages\CreatePenaltyDeduction::route('/create'),
            'edit'   => Pages\EditPenaltyDeduction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}