<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserTypeResource\Pages;
use App\Filament\Resources\UserTypeResource\RelationManagers;
use App\Models\Role;
use App\Models\UserType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
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
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('can_access_all_branches')
                    ->label('Can access all branches')
                    ->default(false),

                Forms\Components\Toggle::make('can_access_all_stores')
                    ->label('Can access all stores')
                    ->default(false),

                Forms\Components\Toggle::make('can_access_non_branch_data')
                    ->label('Can access non-branch data')
                    ->helperText('Allow viewing data not tied to a specific branch (e.g. users, global reports)')
                    ->default(false),
                Forms\Components\Textarea::make('description'),

                Select::make('role_ids')
                    ->label('Roles')
                    ->options(\Spatie\Permission\Models\Role::get()->pluck('name', 'id'))->multiple()->required(),


                Forms\Components\Toggle::make('active')
                    ->label('Is Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('role_names')

                    ->label('Roles'),
                Tables\Columns\IconColumn::make('can_access_all_branches')->boolean()->alignCenter(true),
                Tables\Columns\IconColumn::make('can_access_all_stores')->boolean()->alignCenter(true),
                Tables\Columns\IconColumn::make('can_access_non_branch_data')->boolean()->alignCenter(true),
                Tables\Columns\IconColumn::make('active')->boolean()->alignCenter(true),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
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
