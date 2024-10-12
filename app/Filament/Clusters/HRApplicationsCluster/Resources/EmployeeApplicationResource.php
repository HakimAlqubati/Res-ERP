<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources;

use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;
use App\Filament\Pages\AttendanecEmployee;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EmployeeApplicationResource extends Resource
{
    protected static ?string $model = EmployeeApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRApplicationsCluster::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->label('')->columns(3)->schema([
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

                    DatePicker::make('application_date')
                        ->label('Application date')
                        ->default('Y-m-d')
                        ->required(),
                    Select::make('application_type')
                        ->label('Application type')
                        ->hiddenOn('edit')
                        ->searchable()
                        ->live()
                        ->options(EmployeeApplication::APPLICATION_TYPES)
                    ,
                ]),
                Fieldset::make('')
                    ->label(fn(Get $get): string => EmployeeApplication::APPLICATION_TYPES[$get('application_type')])

                    ->columns(2)
                    ->visible(fn(Get $get): bool => in_array($get('application_type')

                        , [
                            EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
                            EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
                        ]))
                // ->visibleOn('edit')
                    ->schema([
                        DatePicker::make('detail_date')
                            ->label('date')
                            ->default('Y-m-d'),
                        TimePicker::make('detail_time')
                            ->label('Time'),
                    ]),
                Fieldset::make()->label('')->schema([
                    Textarea::make('notes') // Add the new details field
                        ->label('Notes')
                        ->placeholder('Enter application notes...')
                    // ->rows(5)
                        ->columnSpanFull()
                    ,
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
                TextColumn::make('createdBy.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('detail_date')->label('Date'),
                TextColumn::make('detail_time')->label('Time'),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->icon('heroicon-m-check-badge')
                    ->color(fn(string $state): string => match ($state) {
                        EmployeeApplication::STATUS_PENDING => 'warning',
                        EmployeeApplication::STATUS_REJECTED => 'danger',
                        EmployeeApplication::STATUS_APPROVED => 'success',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                // TextColumn::make('rejectedBy.name')->label('Rejected by'),
                // TextColumn::make('rejected_at')->label('Rejected at'),
                // TextColumn::make('rejected_reason')->label('Rejected reason'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Action::make('approveDepatureRequest')->label('Approve')->button()
                    ->visible(fn($record): bool => $record->status == EmployeeApplication::STATUS_PENDING)
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function ($record, $data) {
                        // dd($record,$data);

                        (new AttendanecEmployee())->createAttendance($record->employee, $data['period'], $data['request_check_date'], $data['request_check_time'], 'd', Attendance::CHECKTYPE_CHECKOUT);
                        $record->update([
                            'status' => EmployeeApplication::STATUS_APPROVED,
                            'approved_by' => auth()->user()->id,
                            'approved_at' => now(),
                        ]);

                    })
                    ->disabledForm()
                    ->form(function ($record) {
                        $attendance = Attendance::where('employee_id', $record?->employee_id)
                            ->where('check_date', $record?->detail_date)
                            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                            ->first();

                        return [
                            Fieldset::make()->label('Attendance data')->columns(3)->schema([
                                TextInput::make('employee')->default($record?->employee?->name),
                                DatePicker::make('check_date')->default($attendance?->check_date),
                                TimePicker::make('check_time')->default($attendance?->check_time),
                                TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                                TextInput::make('start_at')->default($attendance?->period?->start_at),
                                TextInput::make('end_at')->default($attendance?->period?->end_at),
                                Hidden::make('period')->default($attendance?->period),
                            ]),
                            Fieldset::make()->label('Request data')->columns(2)->schema([
                                DatePicker::make('request_check_date')->default($record?->detail_date)->label('Date'),
                                TimePicker::make('request_check_time')->default($record?->detail_time)->label('Time'),
                            ]),
                        ];
                    }),
                Action::make('reject')->label('Reject')->button()
                    ->color('warning')
                    ->visible(fn($record): bool => $record->status == EmployeeApplication::STATUS_PENDING)
                    ->icon('heroicon-o-x-mark')
                    ->action(function ($record, $data) {
                        $record->update([
                            'status' => EmployeeApplication::STATUS_REJECTED,
                            'rejected_reason' => $data['rejected_reason'],
                            'rejected_by' => auth()->user()->id,
                            'rejected_at' => now(),
                        ]);
                    })

                // ->requiresConfirmation()
                // ->disabledForm()
                    ->form(function ($record) {
                        $attendance = Attendance::where('employee_id', $record?->employee_id)
                            ->where('check_date', $record?->detail_date)
                            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                            ->first();

                        return [
                            Fieldset::make()->disabled()->label('Attendance data')->columns(3)->schema([
                                TextInput::make('employee')->default($record?->employee?->name),
                                DatePicker::make('check_date')->default($attendance?->check_date),
                                TimePicker::make('check_time')->default($attendance?->check_time),
                                TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                                TextInput::make('start_at')->default($attendance?->period?->start_at),
                                TextInput::make('end_at')->default($attendance?->period?->end_at),
                                Hidden::make('period')->default($attendance?->period),
                            ]),
                            Fieldset::make()->disabled()->label('Request data')->columns(2)->schema([
                                DatePicker::make('request_check_date')->default($record?->detail_date)->label('Date'),
                                TimePicker::make('request_check_time')->default($record?->detail_time)->label('Time'),
                            ]),
                            Fieldset::make()->label('Rejected reason')->columns(2)->schema([
                                Textarea::make('rejected_reason')->label('')->columnSpanFull()->required()
                                    ->disabled(false)
                                    ->helperText('Please descripe reject reason')
                                ,
                            ]),

                        ];
                    })

                ,
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEmployeeApplications::route('/'),
            'create' => Pages\CreateEmployeeApplication::route('/create'),
            // 'edit' => Pages\EditEmployeeApplication::route('/{record}/edit'),
        ];
    }

    public static function getDetailsKeysAndValues(array $data)
    {
        // Use array_filter to get the keys starting with 'requr_pattern_'
        $filteredData = array_filter($data, function ($value, $key) {
            return Str::startsWith($key, 'detail_');
        }, ARRAY_FILTER_USE_BOTH);

        return $filteredData;
    }
}
