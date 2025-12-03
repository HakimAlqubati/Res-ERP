<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\CategoryResource\Pages\ManageCategories;
use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Squares2x2;
    // protected static ?string $navigationGroup = 'Categories';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $cluster = ProductUnitCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function getNavigationLabel(): string
    {
        return __('lang.categories');
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->required()->label(__('lang.name')),
                        // Forms\Components\TextInput::make('code')
                        //     ->unique(ignoreRecord: true)
                        //     ->required()->label(__("lang.code")),
                        TextInput::make('code_starts_with')
                            ->label('Code Starts With')
                            ->maxLength(5)
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(2)
                            ->minLength(2)
                            ->rule('regex:/^[0-9]{2}$/')
                            ->placeholder(function () {
                                $lastCode = Category::query()
                                    ->whereRaw('code_starts_with REGEXP "^[0-9]{2}$"') // فقط الأرقام
                                    ->orderByDesc('code_starts_with')
                                    ->value('code_starts_with');

                                $nextCode = str_pad((intval($lastCode) + 1), 2, '0', STR_PAD_LEFT);
                                return $nextCode;
                            })

                            ->helperText('Code must be exactly 2 digits (e.g., 01, 25, 99)'),
                        // Forms\Components\TextInput::make('waste_stock_percentage')
                        //     ->label('Waste %')
                        //     ->numeric()
                        //     ->default(0)
                        //     ->minValue(0)
                        //     ->maxValue(100)
                        //     ->helperText('Expected stock waste percentage for this category.'),
                    ]),
                    Grid::make()->columnSpanFull()->columns(3)->schema([
                        Toggle::make('active')
                            ->inline(false)->default(true)
                            ->label(__("lang.active")),
                        Toggle::make('is_manafacturing')
                            ->inline(false)
                            ->label('Manafacturing')->default(false),
                        Toggle::make('has_description')
                            ->label('Has Description')->inline(false)->live(),
                    ]),
                    Textarea::make('description')
                        ->visible(fn($get): bool => $get('has_description'))
                        ->label(__("lang.description"))->columnSpanFull()
                        ->rows(10)
                        ->cols(20),
                ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')->striped()
            ->columns([
                TextColumn::make('id')
                    ->sortable()->label(__('lang.id'))
                    ->searchable(isIndividual: true, isGlobal: false)->searchable(),
                TextColumn::make('name')->label(__('lang.name'))
                    ->searchable(isIndividual: true, isGlobal: false),
                // Tables\Columns\TextColumn::make('code')->label(__('lang.code'))
                //     ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('code_starts_with')
                    ->label('Prefix Code')->sortable()
                    ->searchable()
                    ->tooltip('Used to auto-generate product codes')
                    ->alignCenter(true)->toggleable(),
                TextColumn::make('branch_names')
                    ->label('Customized for Branches')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('waste_stock_percentage')
                //     ->label('Waste %')
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->alignCenter(true),

                TextColumn::make('description')->label(__('lang.description'))->toggleable()->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('products')->label('Number of products'),
            ])
            ->filters([
                SelectFilter::make('active')
                    ->options([
                        1 => __('lang.active'),
                        0 => __('lang.status_unactive'),
                    ]),

                SelectFilter::make('is_manafacturing')
                    ->options([
                        1 => __('lang.category.is_manafacturing'),
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::notForPos()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->notForPos()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
