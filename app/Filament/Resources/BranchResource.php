<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Branches';
    public static function getNavigationLabel(): string
    {
        return __('lang.branches');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->label(__('lang.name')),
                Textarea::make('address')->label(__('lang.address')),
                Checkbox::make('active')->label(__('lang.active')),
                Select::make('manager_id')
                    ->label(__('lang.branch_manager'))
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('lang.branch_id')),
                TextColumn::make('name')->label(__('lang.name'))->searchable(),
                TextColumn::make('address')->label(__('lang.address'))
                // ->limit(100)
                    ->words(5),
                TextColumn::make('user.name')->label(__('lang.branch_manager')),
                TextColumn::make('total_quantity')->label(__('lang.quantity'))
                    ->action(function ($record) {
                        redirect('admin/branch-store-report?tableFilters[branch_id][value]=' . $record->id);
                    }),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('add_area')
                    ->modalHeading('')
                    ->modalWidth('lg') // Adjust modal size
                    ->button()
                    ->icon('heroicon-o-plus')
                    ->label('Add area')->form([
                    Repeater::make('branch_areas')->schema([
                        TextInput::make('name')->label('Area name')->required()->helperText('Type the name of area'),
                        Textarea::make('description')->label('Description')->helperText('More information about the area, like floor, location ...etc'),
                    ])
                        ->afterStateUpdated(function ($state, $record) {
                            
                            // Custom logic to handle saving without deleting existing records
                            $branch = $record; // Get the branch being updated
                            $existingAreas = $branch->areas->pluck('id')->toArray(); // Existing area IDs

                            foreach ($state as $areaData) {
                                if (!isset($areaData['id'])) {
                                    // If it's a new area, create it
                                    $branch->areas()->create($areaData);
                                }else{

                                }
                            }
                        }),
                ]),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBranches::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
