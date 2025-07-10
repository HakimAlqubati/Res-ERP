<?php
namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Models\EmployeePeriodDay;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('day_of_week')
                ->options([
                    'Monday'    => 'Monday',
                    'Tuesday'   => 'Tuesday',
                    'Wednesday' => 'Wednesday',
                    'Thursday'  => 'Thursday',
                    'Friday'    => 'Friday',
                    'Saturday'  => 'Saturday',
                    'Sunday'    => 'Sunday',
                ])
                ->required()
                ->label('Day of Week'),

            DatePicker::make('start_date')
                ->label('Start Date')
                ->nullable(),

            DatePicker::make('end_date')
                ->label('End Date')
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Day'),

                TextColumn::make('start_date')
                    ->date()
                    ->label('Start Date'),

                TextColumn::make('end_date')
                    ->date()
                    ->label('End Date'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Assign Day')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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