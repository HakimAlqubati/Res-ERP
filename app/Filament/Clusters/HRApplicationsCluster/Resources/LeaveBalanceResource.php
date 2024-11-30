<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources;

use App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttendanceReport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Filament\Facades\Filament;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static ?string $modelLabel = 'Leave balance';
    protected static ?string $pluralLabel = 'Leave balance';

    // public static function getCluster(): ?string
    // {
    //     $user = Filament::auth()->user();
    //     dd($user);
    //     dd(auth()->user());
    //     if (isStuff()) {
    //         return HRAttendanceReport::class;
    //     }
    //     return HRAttenanceCluster::class;
    // }
    public static function getModelLabel(): string
    {
        return isStuff() ? 'My leaves': static::$modelLabel;
    }
    public static function getPluralLabel(): ?string
    {
        return isStuff() ? 'My leaves': static::$modelLabel;static::$pluralLabel;
    }
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                [

                    Fieldset::make('basic')
                        ->columns(4)
                        ->label('Set branch employees, the Leave type and Year')
                        ->schema([

                            Select::make('branch_id')->label('Branch')
                                ->helperText('Select to populate the branch employees')
                                ->required()
                                ->live()
                                ->options(Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $employees = Employee::where('branch_id', $state)->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
                                    // Populate the Repeater with employees
                                    $set('employees', array_map(function ($employee) {
                                        return [
                                            'employee_id' => $employee['employee_id'],

                                        ];
                                    }, $employees));
                                })
                            ,
                            Select::make('leave_type_id')->label('Leave type')
                                ->required()
                                ->live()
                                ->options(LeaveType::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state, $livewire) {
                                    $employees = Employee::where('branch_id', $get('branch_id'))->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
                                    $leaveType = LeaveType::find($state);
                                    foreach ($employees as $index => $employee) {
                                        $employees[$index]['balance'] = $leaveType->count_days;
                                    }
                                    // Set the updated repeater data back to the 'employees' field
                                    $set('employees', $employees);
                                })
                            ,
                            Select::make('year')->options([
                                2024 => 2024,
                                2025 => 2025,
                                2026 => 2026,
                                2027 => 2027,
                            ])->required(),
                            Select::make('month')->options(getMonthArrayWithKeys())
                            // ->multiple()
                            // ->required()
                            ,
                        ]),

                    Repeater::make('employees')
                        ->label('')
                        ->columnSpanFull()
                        ->minItems(2)
                        ->schema(
                            [
                                Grid::make()->columns(2)->schema([
                                    Select::make('employee_id')
                                        ->relationship('employee', 'name')
                                        ->required()
                                        ->unique(
                                            ignoreRecord: true,
                                            modifyRuleUsing: function (Unique $rule, Get $get, $state) {
                                                return $rule->where('employee_id', $state)
                                                    ->where('leave_type_id', $get('../../leave_type_id'))
                                                    ->where('year', $get('../../year'))
                                                    ->where('month', $get('../../month'))
                                                ;
                                            }
                                        )->validationMessages([
                                        'unique' => 'Balance already created',
                                    ])
                                    ,

                                    TextInput::make('balance')->label('Balance')
                                        ->numeric()
                                        ->live()
                                        ->required()
                                    // ->maxValue(function (Get $get) {
                                    //     dd($get('leave_type_id'),$get('employee_id'),$get('basic.branch_id'));
                                    //     $max = LeaveType::find($get('leave_type_id'))?->count_days ?? 0;
                                    //     // dd($max);
                                    //     return $max;
                                    // })
                                    ,
                                ])

                                ,

                            ])

                    ,
                ]
            )
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_no')
                    ->label('Employee no')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->numeric()
                    ->alignCenter(true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->alignCenter(true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('month')
                    ->alignCenter(true)
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                       return getMonthArrayWithKeys()[$state]??'';
                    })
                    ,
                Tables\Columns\TextColumn::make('balance')->alignCenter(true)
                    ->numeric()
                    ->sortable(),

            ])->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make()->hidden(),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options([Branch::get()->pluck('name', 'id')->toArray()]),
                SelectFilter::make('leave_type_id')
                    ->searchable()
                    ->multiple()
                    ->label('Leave type')->options([LeaveType::get()->pluck('name', 'id')->toArray()]),
                SelectFilter::make('employee_id')
                    ->searchable()
                    ->multiple()
                    ->label('Employee')->options([Employee::get()->pluck('name', 'id')->toArray()]),
                SelectFilter::make('year')
                    ->searchable()
                    ->multiple()
                    ->label('Year')->options([2024=>2024,2025=>2025,2026=>2026]),
                SelectFilter::make('month')
                    ->searchable()
                    ->multiple()
                    ->label('Month')->options(getMonthArrayWithKeys()),
            ],FiltersLayout::AboveContent)
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListLeaveBalances::route('/'),
            'create' => Pages\CreateLeaveBalance::route('/create'),
            // 'edit' => Pages\EditLeaveBalance::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isBranchManager()) {
            return true;
        }
        return false;
        return static::can('create');
    }

    // public static function getLabel(): ?string
    // {
    //     // if (!in_array(getCurrentRole(), [1, 3])) {
    //     if (isStuff()) {
    //         return 'My leave balance';
    //     }
    //     return static::$label;
    // }

}
