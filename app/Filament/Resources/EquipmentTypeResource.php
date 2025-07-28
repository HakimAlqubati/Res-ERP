<?php
namespace App\Filament\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Resources\EquipmentTypeResource\Pages;
use App\Models\EquipmentType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EquipmentTypeResource extends Resource
{
    protected static ?string $model = EquipmentType::class;

    protected static ?string $cluster                             = HRServiceRequestCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 3;

    protected static ?string $label       = 'Equipment Type';
    protected static ?string $pluralLabel = 'Equipment Types';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(4)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)->live(onBlur:true)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('code', strtoupper(Str::slug($state, '-')));
                        })
                        ,

                    Forms\Components\TextInput::make('code')
                        ->label('Code')
                        ->required()
                        // ->maxLength(40)
                        ->unique()->readOnly()
                        ->helperText('This will be used as the Asset Tag prefix.')
                        ,

                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->required()
                        ->preload(),

                    Forms\Components\Toggle::make('active')
                        ->label('Active')->inline(false)
                        ->default(true),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)->columnSpanFull(),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')->toggleable()
                    ->limit(50),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')->alignCenter(true)->toggleable()
                    ->boolean(),
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
            'index'  => Pages\ListEquipmentTypes::route('/'),
            'create' => Pages\CreateEquipmentType::route('/create'),
            'edit'   => Pages\EditEquipmentType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }
}