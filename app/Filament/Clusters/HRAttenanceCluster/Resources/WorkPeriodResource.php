<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages;
use App\Models\Branch;
use App\Models\WorkPeriod;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Symfony\Component\Yaml\Inline;

class WorkPeriodResource extends Resource
{
    protected static ?string $model = WorkPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static ?string $label = 'Work shifts';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    protected static function getFormSchema(): array
    {
        return [

            Fieldset::make()->schema([
                Grid::make()->columns(3)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
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
                    Forms\Components\Select::make('branch_id')
                        ->options(Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                        ->label('Branch')->required()
                        ->searchable(),
                    Toggle::make('active')
                        ->label('Active')
                        ->columnSpan(1)
                        ->inline(false)
                        ->default(true),

                ]),

                Textarea::make('description')->columnSpanFull()
                    ->label('Description'),
                Grid::make()->columns(2)->schema([
                    Forms\Components\TimePicker::make('start_at')
                        ->label('Start time')
                        ->columnSpan(1)
                        ->required()
                        ->prefixIcon('heroicon-m-check-circle')
                        ->prefixIconColor('success')
                        ->default('08:00:00'),

                    Forms\Components\TimePicker::make('end_at')
                        ->label('End time')
                        ->columnSpan(1)
                        ->required()
                        ->prefixIcon('heroicon-m-check-circle')
                        ->prefixIconColor('success')
                        ->default('12:00:00'),
                ]),

                Grid::make()->columns(2)->schema([
                    Forms\Components\Select::make('days')
                        ->label('Days')
                        ->multiple()
                        ->options([
                            'Monday' => 'Monday',
                            'Tuesday' => 'Tuesday',
                            'Wednesday' => 'Wednesday',
                            'Thursday' => 'Thursday',
                            'Friday' => 'Friday',
                            'Saturday' => 'Saturday',
                            'Sunday' => 'Sunday',
                        ])->default(['Sunday'])
                        ->columnSpan(1)
                        ->required(),

                    Forms\Components\TextInput::make('allowed_count_minutes_late')
                        ->label('Allowed Delay (Minutes)')->required()->default(0)
                        ->columnSpan(1)
                        ->numeric(),
                ]),

            ]),
        ];
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')->searchable()->sortable()
                    ,

                Tables\Columns\BooleanColumn::make('active')->alignCenter(true)
                    ->label('Active'),
                Tables\Columns\BooleanColumn::make('day_and_night')->alignCenter(true)->sortable()
                    ->label('Day and Night'),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Start Time')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('End Time')
                    ->sortable(),

                // Tables\Columns\TextColumn::make('allowed_count_minutes_late')->alignCenter(true)
                //     ->label('Late Minutes Allowed'),

            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')->options(Branch::select('id', 'name')->where('active', 1)->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('copy')
                ->label('Copy')
                ->hidden(fn():bool=>isBranchManager())
                ->button()
                ->icon('heroicon-o-clipboard-document-list')
                ->form(function ($record) {
                    return [

                        Fieldset::make()->label('')->columnSpan(3)->schema([
                            Forms\Components\TextInput::make('name')->unique()
                                ->label('Name')
                                ->required()
                                ->default($record->name . ' - Copy'), // Appending " - Copy" for distinction
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->default($record->description),
                            Forms\Components\Toggle::make('active')
                                ->label('Active')->inline()
                                ->default($record->active),
                                
                                Forms\Components\TimePicker::make('start_at')
                                    ->label('Start Time')
                                    ->required()
                                    ->default($record->start_at),
                                Forms\Components\TimePicker::make('end_at')
                                    ->label('End Time')
                                    ->required()
                                    ->default($record->end_at),
                                Forms\Components\Select::make('branch_id')
                                    ->options(Branch::where('active', 1)->pluck('name', 'id'))
                                    ->label('Branch')
                                    ->default($record->branch_id),
                                Forms\Components\TextInput::make('allowed_count_minutes_late')
                                    ->label('Allowed Delay (Minutes)')
                                    ->default($record->allowed_count_minutes_late),
                        ]),
                    ];
                })
                ->action(function ($record, array $data) {
                    // Duplicate the record
                    $newRecord = $record->replicate();
                    $newRecord->name = $record->name . ' - Copy'; // Modify as needed to differentiate
                    // $newRecord->name = $data['name'];
                    $newRecord->description = $data['description'];
                    $newRecord->active = $data['active'];
                    $newRecord->start_at = $data['start_at'];
                    $newRecord->end_at = $data['end_at'];
                    $newRecord->branch_id = $data['branch_id'];
                    $newRecord->allowed_count_minutes_late = $data['allowed_count_minutes_late'];
        
                    $newRecord->save();
                })
                
                ->color('warning')
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
            'index' => Pages\ListWorkPeriods::route('/'),
            'create' => Pages\CreateWorkPeriod::route('/create'),
            'edit' => Pages\EditWorkPeriod::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isBranchManager() ) {
            return true;
        }
        return false;
    }

    public static function calculateDayAndNight($startAt, $endAt): bool
    {
        // Logic to set default based on time fields
        return $startAt > $endAt;
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);

    //     // Check if the user is a branch manager
    //     if (isBranchManager()) {
    //         $query->where('branch_id', auth()->user()->branch_id);
    //     }

    //     return $query;
    // }
}
