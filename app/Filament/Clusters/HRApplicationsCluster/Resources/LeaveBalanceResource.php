<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources;

use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRApplicationsCluster\Resources\LeaveBalanceResource\Pages;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRApplicationsCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                [

                    Fieldset::make()
                        ->columns(3)
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
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $employees = Employee::where('branch_id', $state)->where('active', 1)->select('name', 'id as employee_id')->get()->toArray();
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
                                2026 => 2026,
                                2027 => 2027,
                            ]),
                        ]),

                    Repeater::make('employees')
                        ->label('')
                        ->columnSpanFull()
                        ->schema(
                            [
                                Grid::make()->columns(2)->schema([
                                    Select::make('employee_id')
                                        ->relationship('employee', 'name')
                                        ->required(),

                                    TextInput::make('balance')->label('Balance')->default(0)->numeric()
                                    // ->maxValue(0)
                                        ->required(),
                                ]),

                            ])

                    ,
                ]
            )
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('year')->alignCenter(true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')->alignCenter(true)
                    ->numeric()
                    ->sortable(),

            ])->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options([Branch::get()->pluck('name', 'id')->toArray()]),
                SelectFilter::make('leave_type_id')
                    ->searchable()
                    ->multiple()
                    ->label('Leave type')->options([LeaveType::get()->pluck('name', 'id')->toArray()]),
            ])
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
}
