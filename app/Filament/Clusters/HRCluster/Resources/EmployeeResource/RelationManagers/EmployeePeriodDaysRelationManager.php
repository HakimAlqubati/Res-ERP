<?php
namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Models\EmployeePeriodDay;
use App\Models\WorkPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeePeriodDaysRelationManager extends RelationManager
{
    protected static string $relationship          = 'periodDays';
    protected static ?string $title                = 'Shift Days (Weekly)';
    protected static ?string $recordTitleAttribute = 'day_of_week';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period_id')
                ->label('Work Period')
                ->options(function () {
                    $employee = $this->getOwnerRecord();

                    return WorkPeriod::query()
                        ->when($employee?->branch_id, fn($q) => $q->where('branch_id', $employee->branch_id))
                        ->pluck('name', 'id');
                })->preload()
                ->searchable()
                ->required(),

            Select::make('day_of_week')
                ->label('Day of Week')
                ->options([
                    'sun' => 'Sunday',
                    'mon' => 'Monday',
                    'tue' => 'Tuesday',
                    'wed' => 'Wednesday',
                    'thu' => 'Thursday',
                    'fri' => 'Friday',
                    'sat' => 'Saturday',
                ])
                ->required(),

            DatePicker::make('start_date')->label('Start date')->nullable(),
            DatePicker::make('end_date')->label('End date')->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period.name')->label('Work Period'),
                TextColumn::make('day_of_week')->label('Day'),
                TextColumn::make('start_date')->label('Start date')->date(),
                TextColumn::make('end_date')->label('End date')->date(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Assign Day')
                    ->mutateDataUsing(function (array $data, $livewire): array {
                        $data['employee_id'] = $livewire->ownerRecord->id;
                        return $data;
                    })
                    ->after(function (array $data) use ($table) {
                        $exists = EmployeePeriodDay::where('employee_id', $data['employee_id'])
                            ->where('period_id', $data['period_id'])
                            ->where('day_of_week', $data['day_of_week'])
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Duplicate Entry')
                                ->body('This day is already assigned to this period.')
                                ->danger()
                                ->send();
 ;
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Day')
                    ->modalDescription('Are you sure you want to unassign this day from this period?'),
            ]);
    }

    public function canCreate(): bool
    {
        return isSuperAdmin() || isBranchManager();
    }
}