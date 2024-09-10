<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\RelationManagers;
use App\Models\Branch;
use App\Models\WorkPeriod;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Symfony\Component\Yaml\Inline;
use Filament\Forms\Get;

class WorkPeriodResource extends Resource
{
    protected static ?string $model = WorkPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Fieldset::make()->schema([
                    Grid::make()->columns(3)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->columnSpan(1)
                            ->unique(ignoreRecord: true),

                        Toggle::make('all_branches')
                            ->default(1)
                            ->label('For all branches?')
                            ->helperText('This period will be for all branches')
                            ->live()
                            ->columnSpan(1)
                            ->inline(false)
                            ->default(true),
                        Toggle::make('active')
                            ->label('Active')
                            ->columnSpan(1)
                            ->inline(false)
                            ->default(true),
                        Fieldset::make()->label('Choose branch that will period will be for')->schema([
                            Forms\Components\Select::make('branch_id')
                                ->hidden(fn(Get $get): bool => $get('all_branches'))
                                ->options(Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                                ->label('')

                                ->searchable()
                                ->columnSpanFull(),
                        ])->columnSpanFull()->hidden(fn(Get $get): bool => $get('all_branches')),
                    ]),

                    Forms\Components\RichEditor::make('description')->columnSpanFull()
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
                            ->label('Number of minutes allowed for delay')->required()->default(0)
                            ->columnSpan(1)
                            ->numeric(),
                    ]),


                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->default(function ($record){
                        if($record->all_branches){
                            return 'All branches';
                        }
                    })
                    ,

                Tables\Columns\BooleanColumn::make('active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Start Time')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('End Time')
                    ->sortable(),

                Tables\Columns\TextColumn::make('allowed_count_minutes_late')->alignCenter(true)
                    ->label('Late Minutes Allowed'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
}
