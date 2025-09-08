<?php
namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\EquipmentTypeResource\Pages\ListEquipmentTypes;
use App\Filament\Resources\EquipmentTypeResource\Pages\CreateEquipmentType;
use App\Filament\Resources\EquipmentTypeResource\Pages\EditEquipmentType;
use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Resources\EquipmentTypeResource\Pages;
use App\Models\EquipmentType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EquipmentTypeResource extends Resource
{
    protected static ?string $model = EquipmentType::class;

    protected static ?string $cluster                             = HRServiceRequestCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 3;

    protected static ?string $label       = 'Equipment Type';
    protected static ?string $pluralLabel = 'Equipment Types';

    protected static string | \BackedEnum | null $navigationIcon =  Heroicon::PuzzlePiece;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->columns(4)->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)->live(onBlur:true)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('code', strtoupper(Str::slug($state, '-')));
                        })
                        ,

                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        // ->maxLength(40)
                        ->unique()
                        ->helperText('This will be used as the Asset Tag prefix.')
                        ,

                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->required()
                        ->preload(),

                    Toggle::make('active')
                        ->label('Active')->inline(false)
                        ->default(true),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)->columnSpanFull(),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()->toggleable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()->toggleable(),
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()->toggleable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')->toggleable()
                    ->limit(50),

                IconColumn::make('active')
                    ->label('Active')->alignCenter(true)->toggleable()
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index'  => ListEquipmentTypes::route('/'),
            'create' => CreateEquipmentType::route('/create'),
            'edit'   => EditEquipmentType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }
}