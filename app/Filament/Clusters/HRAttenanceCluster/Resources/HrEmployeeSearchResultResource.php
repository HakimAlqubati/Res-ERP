<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource\RelationManagers;
use App\Models\HrEmployeeSearchResult;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HrEmployeeSearchResultResource extends Resource
{
    protected static ?string $model = HrEmployeeSearchResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;

    // protected static ?string $cluster = HRAttenanceCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('image')
                ->label('Uploaded Image Path')
                ->required()
                ,
            // Forms\Components\BelongsToSelect::make('employee_id')
            //     ->label('Employee')
            //     ->relationship('employee', 'name')
            //     ->searchable()
            //     ->disabled(),
            // Forms\Components\TextInput::make('similarity')
            //     ->label('Similarity Score')
            //     ->suffix('%')
            //     ->numeric()
            //     ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                ->label('Uploaded Image')
                ->disk('public') // Specify the storage disk if needed
                // ->pathPrefix('uploads/')
                , // Path prefix if images are in a specific folder
            TextColumn::make('employee.name')
                ->label('Employee Name')
                ->searchable(),
            TextColumn::make('similarity')
                ->label('Similarity (%)')
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime()
                ->sortable(),
            ])
            ->filters([
                //
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
            'index' => Pages\ListHrEmployeeSearchResults::route('/'),
            'create' => Pages\CreateHrEmployeeSearchResult::route('/create'),
            // 'edit' => Pages\EditHrEmployeeSearchResult::route('/{record}/edit'),
        ];
    }
}
