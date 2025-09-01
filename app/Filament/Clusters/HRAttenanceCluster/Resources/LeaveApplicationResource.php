<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages\ListLeaveApplications;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages\CreateLeaveApplication;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages\EditLeaveApplication;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveApplicationResource\Pages;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveApplicationResource extends Resource
{
    protected static ?string $model = LeaveApplication::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $cluster = HRAttenanceCluster::class;

    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    // protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->label('')->schema([
                    Grid::make()->columns(3)->schema([
                        DatePicker::make('from_date')
                            ->label('From Date')
                            ->reactive()
                            ->default(date('Y-m-d'))
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $fromDate = $get('from_date');
                                $toDate = $get('to_date');

                                if ($fromDate && $toDate) {
                                    $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                                    $set('days_count', $daysDiff); // Set the days_count automatically
                                } else {
                                    $set('days_count', 0); // Reset if no valid dates are selected
                                }
                            }),

                        DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(Carbon::tomorrow()->addDays(1)->format('Y-m-d'))
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $fromDate = $get('from_date');
                                $toDate = $get('to_date');

                                if ($fromDate && $toDate) {
                                    $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                                    $set('days_count', $daysDiff); // Set the days_count automatically
                                } else {
                                    $set('days_count', 0); // Reset if no valid dates are selected
                                }
                            }),

                        TextInput::make('days_count')->disabled()
                            ->label('Number of Days')
                            ->helperText('Type how many days this leave will be ?')
                            ->numeric()
                            ->default(2)
                            ->required(),

                    ]),

                    Fieldset::make()->label('')->schema([
                        Grid::make()->label('')->columns(3)->schema([
                            Select::make('employee_id')
                                ->label('Employee')
                                ->searchable()
                                ->disabled(function () {
                                    if (isStuff()) {
                                        return true;
                                    }
                                    return false;
                                })
                                ->default(function () {
                                    if (isStuff()) {
                                        return auth()->user()->employee->id;
                                    }
                                })
                                ->options(Employee::select('name', 'id')

                                        ->get()->plucK('name', 'id'))
                            ,
                            Select::make('status')->options(LeaveApplication::getStatus())
                                ->default(LeaveApplication::STATUS_PENDING)->disabledOn('create')
                                ->hidden(function () {
                                    if (isStuff()) {
                                        return true;
                                    }
                                    return false;
                                })
                            ,
                            Select::make('leave_type_id')->options(LeaveType::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                                ->label('Leave type')->required()
                            ,
                        ]),
                    ]),
                    Fieldset::make()->label('')->schema([
                        Textarea::make('leave_reason')->label('Notes')->required()->columnSpanFull(),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('employee.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('from_date')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('to_date')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('leaveType.name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                // ->formatStateUsing(fn ($state) => LeaveApplication::getStatusLabelAttribute($state))
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListLeaveApplications::route('/'),
            'create' => CreateLeaveApplication::route('/create'),
            'edit' => EditLeaveApplication::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        return true;
        if (isStuff()) {
            return false;
        }
        return true;
    }
}
