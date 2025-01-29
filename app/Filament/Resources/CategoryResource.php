<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $navigationGroup = 'Categories';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $cluster = ProductUnitCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function getNavigationLabel(): string
    {
        return __('lang.categories');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Forms\Components\TextInput::make('name')->required()->label(__('lang.name')),
                    Forms\Components\TextInput::make('code')->required()->label(__("lang.code")),
                    Toggle::make('active')
                        ->inline(false)->default(true)
                        ->label(__("lang.active")),
                    Toggle::make('is_manafacturing')
                        ->inline(false)
                        ->label('Manafacturing')->default(false),

                    Forms\Components\Textarea::make('description')->label(__("lang.description"))->columnSpanFull()
                        ->rows(10)
                        ->cols(20),
                ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()->label(__('lang.id'))
                    ->searchable(isIndividual: true, isGlobal: false)->searchable(),
                Tables\Columns\TextColumn::make('name')->label(__('lang.name'))
                    ->searchable(isIndividual: true, isGlobal: false),
                Tables\Columns\TextColumn::make('code')->label(__('lang.code'))
                    ->searchable(isIndividual: true, isGlobal: false),
                Tables\Columns\TextColumn::make('description')->label(__('lang.description')),
                // Tables\Columns\TextColumn::make('products')->label('Number of products'),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')->label(__('lang.active'))
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('active')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
            'index' => Pages\ManageCategories::route('/'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
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
