<?php

namespace App\Filament\Resources;

use App\Enums\UserTypeScope;
use App\Filament\Resources\UserTypeResource\Pages;
use App\Filament\Resources\UserTypeResource\RelationManagers;

use App\Models\UserType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
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
                    ->columns(2)
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

                        Select::make('scope')
                            ->options(collect(UserTypeScope::cases())
                                ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                                ->toArray()),
                        Select::make('parent_type_id')
                            ->label('Parent Type (optional)')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->nullable(),
                        Grid::make()->columnSpanFull()->columns(3)->schema([
                            Toggle::make('active')->inline(false)
                                ->default(true),
                            Toggle::make('can_access_all_branches')
                                ->label('Can Access All Branches')
                                ->inline(false)
                                ->default(false),

                            Toggle::make('can_access_all_stores')
                                ->label('Can Access All Stores')
                                ->inline(false)
                                ->default(false),
                        ]),
                        Select::make('default_roles')
                            ->label('Default Roles')

                            ->multiple()

                            ->options(\Spatie\Permission\Models\Role::pluck('name', 'id')) // أو إذا لم تكن هناك علاقة مباشرة
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),


                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('code')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('getLevel')->sortable()->alignCenter(true)
                    ->label('Level')
                    ->getStateUsing(fn(UserType $record) => $record->getLevel())->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scope')->sortable(),
                TextColumn::make('parent.name')->label('Parent Type')->toggleable(),
                IconColumn::make('active')->label('Active')->sortable()->boolean()->alignCenter(true),
                TextColumn::make('default_roles')
                    ->label('Default Roles')
                    ->getStateUsing(function (UserType $record) {
                        $defaultRoles = $record->default_roles ?? [];
                        if (empty($defaultRoles)) {
                            return '-';
                        }
                        return \Spatie\Permission\Models\Role::whereIn('id', $defaultRoles)->pluck('name')->implode(', ');
                    })
                    ->limit(50)->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('can_access_all_branches')
                    ->label('All Branches')
                    ->boolean()
                    ->alignCenter(true)
                    ->toggleable(),

                IconColumn::make('can_access_all_stores')
                    ->label('All Stores')
                    ->boolean()
                    ->alignCenter(true)
                    ->toggleable(),
                // TextColumn::make('description')->limit(30)->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assignRoles')
                    ->label('Assign Roles')->button()
                    ->icon('heroicon-o-key')
                    ->form([
                        Select::make('default_roles')
                            ->label('Default Roles')
                            ->multiple()
                            ->options(\Spatie\Permission\Models\Role::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (\Filament\Tables\Actions\Action $action, array $data, UserType $record) {
                        $record->update([
                            'default_roles' => $data['default_roles'],
                        ]);

                        $action->successNotificationTitle('Roles updated successfully.');
                    })
                    ->modalHeading('Assign Default Roles')
                    ->modalSubmitActionLabel('Save')
                    ->color('primary'),

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
