<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;
use App\Models\DailyTasksSettingUp;
use App\Models\TasksMenu;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DailyTasksSettingUpResource extends Resource
{
    protected static ?string $model = DailyTasksSettingUp::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRTasksSystem::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Daily Task Setup';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->columns(4)->schema([
                    TextInput::make('title')
                        ->required()
                        ->autofocus()
                        ->columnSpan(1)
                        ->maxLength(255),
                    Select::make('assigned_by')
                        ->label('Assign by')
                        ->required()
                        ->default(auth()->user()->id)
                        ->columnSpan(1)
                        ->options(User::select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                        ->selectablePlaceholder(false),
                    Select::make('assigned_to')
                        ->label('Assign to')
                        ->required()
                        ->columnSpan(1)
                        ->options(User::select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                        ->selectablePlaceholder(false),
                    Toggle::make('active')->default(1)->inline(false)->columnSpan(1),



                ]),

                Textarea::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Repeater::make('steps')
                    ->itemLabel('Steps')
                    ->columnSpanFull()
                    ->relationship('steps')
                    ->columns(1)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true),
                    ])
                    ->collapseAllAction(
                        fn(\Filament\Forms\Components\Actions\Action $action) => $action->label('Collapse all steps'),
                    )
                    ->orderColumn('order')
                    ->reorderable()
                    ->reorderableWithDragAndDrop()
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['name'] ?? null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->searchable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('description'),
                TextColumn::make('assignedto.name')->label('Assigned to'),
                TextColumn::make('assignedby.name')->label('Assigned by'),
                ToggleColumn::make('active')->label('Active?')->sortable()->disabled(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyTasksSettingUps::route('/'),
            'create' => Pages\CreateDailyTasksSettingUp::route('/create'),
            'edit' => Pages\EditDailyTasksSettingUp::route('/{record}/edit'),
        ];
    }
}
