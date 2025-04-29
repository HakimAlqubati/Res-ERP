<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserTypeResource\Pages;
use App\Filament\Resources\UserTypeResource\RelationManagers;
use App\Models\Role;
use App\Models\UserType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserTypeResource extends Resource
{
    protected static ?string $model = UserType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $slug = 'user-types';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('code')
                    ->required()
                    ->options([
                        'super_admin' => 'Super Admin',
                        'system_manager' => 'System Manager',
                        'branch_manager' => 'Branch Manager',
                        'store_manager' => 'Store Manager',
                        'finance_manager' => 'Finance Manager',
                        'maintenance_manager' => 'Maintenance Manager',
                        'super_visor' => 'Supervisor',
                        'attendance' => 'Attendance User',
                        'driver' => 'Driver',
                        'stuff' => 'Stuff',
                        'branch_user' => 'Branch User',
                        // Add more if needed
                    ])
                    ->searchable(),

                TextInput::make('level')
                    ->numeric()
                    ->required(),

                Select::make('scope')
                    ->required()
                    ->options([
                        'branch' => 'Branch',
                        'store' => 'Store',
                        'all' => 'All',
                    ]),

                Toggle::make('active')
                    ->default(true),

                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('code')->sortable()->searchable(),
                TextColumn::make('level')->sortable(),
                TextColumn::make('scope')->sortable(),
                IconColumn::make('active')->label('Active')->sortable()->boolean()->alignCenter(true),
                TextColumn::make('description')->limit(30)->searchable(),
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
            'index' => Pages\ListUserTypes::route('/'),
            'create' => Pages\CreateUserType::route('/create'),
            'edit' => Pages\EditUserType::route('/{record}/edit'),
        ];
    }
}
