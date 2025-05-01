<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserTypeResource\Pages;
use App\Filament\Resources\UserTypeResource\RelationManagers;
use App\Models\Role;
use App\Models\UserType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Support\Str;

class UserTypeResource extends Resource
{
    protected static ?string $model = UserType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $slug = 'user-types';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                $set('code', Str::slug($state));
                            }),
                        TextInput::make('code')->disabled()
                            ->dehydrated()
                            ->required()->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Toggle::make('active')->inline(false)
                            ->default(true),
                        Select::make('scope')
                            ->options([
                                'branch' => 'Branch',
                                'store' => 'Store',
                                'all' => 'All',
                            ]),
                        Select::make('parent_type_id')
                            ->label('Parent Type (optional)')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->nullable(),


                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('code')->sortable()->searchable(),
                TextColumn::make('getLevel')->sortable()->alignCenter(true)
                    ->label('Level')
                    ->getStateUsing(fn(UserType $record) => $record->getLevel()),
                TextColumn::make('scope')->sortable(),
                TextColumn::make('parent.name')->label('Parent Type')->toggleable(),
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
