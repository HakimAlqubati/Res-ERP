<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Imports\WorkPeriodImport;
use Throwable;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages\ListWorkPeriods;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages\CreateWorkPeriod;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages\EditWorkPeriod;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\WorkPeriod;
use Filament\Forms;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Yaml\Inline;

class WorkPeriodResource extends Resource
{
    protected static ?string $model = WorkPeriod::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Clock;

    protected static ?string $cluster = HRAttenanceCluster::class;

    public static function getModelLabel(): string
    {
        return __('lang.work_period');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.work_shifts');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.work_shifts');
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    protected static function getFormSchema(): array
    {
        return [

            Fieldset::make()->columnSpanFull()->schema([
                Grid::make()->columns(3)->columnSpanFull()->schema([
                    TextInput::make('name')
                        ->label(__('lang.name'))
                        ->required()
                        ->columnSpan(1)
                        ->unique(ignoreRecord: true),

                    // Toggle::make('all_branches')
                    //     ->default(1)
                    //     ->label('For all branches?')
                    //     ->helperText('This period will be for all branches')
                    //     ->live()
                    //     ->columnSpan(1)
                    //     // ->disabled()
                    //     ->inline(false)
                    //     ->default(true),
                    Select::make('branch_id')
                        ->options(Branch::where('active', 1)
                            ->whereIn('type', [
                                Branch::TYPE_BRANCH,
                                Branch::TYPE_HQ
                            ])->select('name', 'id')->get()->pluck('name', 'id'))
                        ->label(__('lang.branch'))->required()
                        ->searchable(),
                    Toggle::make('active')
                        ->label(__('lang.active'))
                        ->columnSpan(1)
                        ->inline(false)
                        ->default(true),

                ]),

                Textarea::make('description')->columnSpanFull()
                    ->label(__('lang.description')),
                Grid::make()->columns(2)->schema([
                    TimePicker::make('start_at')
                        ->label(__('lang.start_time'))
                        ->columnSpan(1)
                        ->required()
                        ->prefixIcon('heroicon-m-check-circle')
                        ->prefixIconColor('success')
                        ->default('08:00:00'),

                    TimePicker::make('end_at')
                        ->label(__('lang.end_time'))
                        ->columnSpan(1)
                        ->required()
                        ->prefixIcon('heroicon-m-check-circle')
                        ->prefixIconColor('success')
                        ->default('12:00:00'),
                ]),

                // Grid::make()->columns(2)->schema([
                //     Forms\Components\Select::make('days')
                //         ->label('Days')
                //         ->multiple()
                //         ->options([
                //             'Monday' => 'Monday',
                //             'Tuesday' => 'Tuesday',
                //             'Wednesday' => 'Wednesday',
                //             'Thursday' => 'Thursday',
                //             'Friday' => 'Friday',
                //             'Saturday' => 'Saturday',
                //             'Sunday' => 'Sunday',
                //         ])->default(['Sunday'])
                //         ->columnSpan(1)
                //         ->required(),

                //     // Forms\Components\TextInput::make('allowed_count_minutes_late')
                //     //     ->label('Allowed Delay (Minutes)')->required()->default(0)
                //     //     ->columnSpan(1)
                //     //     ->numeric(),
                // ]),

            ]),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('id')
                    ->sortable()
                    ->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')->searchable()->sortable()->toggleable(),

                Tables\Columns\BooleanColumn::make('active')->alignCenter(true)
                    ->label('Active')->toggleable(),
                Tables\Columns\BooleanColumn::make('day_and_night')->alignCenter(true)->sortable()
                    ->label('Day and Night')->toggleable(),

                TextColumn::make('start_at')
                    ->label('Start Time')
                    ->sortable()->toggleable(),

                TextColumn::make('end_at')
                    ->label('End Time')
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('supposed_duration')
                    ->label('Duration')->toggleable()->alignCenter(),

                // Tables\Columns\TextColumn::make('allowed_count_minutes_late')->alignCenter(true)
                //     ->label('Late Minutes Allowed'),

            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('branch_id')->searchable()->label('Branch')->options(Branch::select('id', 'name')->where('active', 1)->selectable()->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('copy')
                    ->label('Copy')
                    ->hidden(fn(): bool => isBranchManager())
                    ->button()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema(function ($record) {
                        return [

                            Fieldset::make()->columnSpanFull()->label('')->columnSpan(3)->schema([
                                TextInput::make('name')->unique()
                                    ->label('Name')
                                    ->required()
                                    ->default($record->name . ' - Copy'), // Appending " - Copy" for distinction
                                Textarea::make('description')
                                    ->label('Description')
                                    ->default($record->description),
                                Toggle::make('active')
                                    ->label('Active')->inline()
                                    ->default($record->active),

                                TimePicker::make('start_at')
                                    ->label('Start Time')
                                    ->required()
                                    ->default($record->start_at),
                                TimePicker::make('end_at')
                                    ->label('End Time')
                                    ->required()
                                    ->default($record->end_at),
                                Forms\Components\Select::make('branch_id')
                                    ->options(Branch::active()
                                        ->selectable()->pluck('name', 'id'))
                                    ->label('Branch')
                                    ->default($record->branch_id),
                                // Forms\Components\TextInput::make('allowed_count_minutes_late')
                                //     ->label('Allowed Delay (Minutes)')
                                //     ->default($record->allowed_count_minutes_late),
                            ]),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        // Duplicate the record
                        $newRecord = $record->replicate();
                        // $newRecord->name = $record->name . ' - Copy'; // Modify as needed to differentiate
                        $newRecord->name = $data['name'];
                        $newRecord->description = $data['description'];
                        $newRecord->active = $data['active'];
                        $newRecord->start_at = $data['start_at'];
                        $newRecord->end_at = $data['end_at'];
                        $newRecord->branch_id = $data['branch_id'];
                        // $newRecord->allowed_count_minutes_late = $data['allowed_count_minutes_late'];

                        $newRecord->save();
                    })

                    ->color('warning')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make()
                    BulkAction::make('delete')
                        ->label('Bulk Delete')
                        ->icon('heroicon-o-trash')
                        ->action(function (Collection $records) {
                            $employeePeriodsExistingCount = DB::table('hr_employee_periods')->whereIn('period_id', $records->pluck('id'))->count();

                            $periodInAttendanceCount = Attendance::where('period_id')->whereIn('period_id', $records->pluck('id'))->count();

                            $countValidate = $periodInAttendanceCount + $employeePeriodsExistingCount;
                            if ($countValidate > 0) {
                                showWarningNotifiMessage('Cannot delete: shifts assigned.');
                                return;
                            }
                            $records->each->delete();
                            showSuccessNotifiMessage('Deleted');
                        })
                        // ->action(fn(Collection $records) => $records->each->delete())
                        ->deselectRecordsAfterCompletion(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('import_work_peirods')
                    ->label('Import from Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Select Excel file'),
                    ])
                    ->color('success')
                    ->action(function ($data) {

                        $file = 'public/' . $data['file'];
                        try {
                            // Create an instance of the import class
                            $import = new WorkPeriodImport;

                            // Import the file
                            Excel::import($import, $file);

                            // Check the result and show the appropriate notification
                            if ($import->getSuccessfulImportsCount() > 0) {
                                showSuccessNotifiMessage("Shifts imported successfully. {$import->getSuccessfulImportsCount()} rows added.");
                            } else {
                                showWarningNotifiMessage('No shifts were added. Please check your file.');
                            }
                        } catch (Throwable $th) {
                            throw $th;
                            showWarningNotifiMessage('Error importing shifts');
                        }
                    })
            ])
        ;
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
            'index' => ListWorkPeriods::route('/'),
            'create' => CreateWorkPeriod::route('/create'),
            'edit' => EditWorkPeriod::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::forBranchManager()->count();
    }
    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isBranchManager()) {
            return true;
        }
        return false;
    }



    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        return $query->forBranchManager();
    }
}
