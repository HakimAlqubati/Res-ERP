<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Filament\Resources\UnitResource\Pages\CreateUnit;
use App\Filament\Resources\UnitResource\Pages\EditUnit;
use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Resources\UnitResource\Pages;
use App\Filament\Resources\UnitResource\RelationManagers;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;
    protected static ?string $cluster = ProductUnitCluster::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'name';

    // protected static ?string $navigationGroup = 'Products - units';

    protected static string | \BackedEnum | null $activeNavigationIcon = 'heroicon-s-document-text';
    // protected static bool $shouldRegisterNavigation = false;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Units';
    public static function getNavigationLabel(): string
    {
        return __('lang.units');
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columns(3)->schema([
                    TextInput::make('name')->required()
                        ->live(onBlur: true)
                        ->unique(ignoreRecord: true)
                        ->afterStateUpdated(fn($set, $state): string => $set('code', str_replace(' ', '-', $state)))
                        ->columnSpan(1),
                    TextInput::make('code')
                        ->unique(ignoreRecord: true)
                        ->required(),
                    Toggle::make('active')->inline(false)->default(true),
                    Toggle::make('is_fractional')
                        ->label('Can be fractional?')
                        ->helperText('Enable this if this unit allows decimal quantities.')
                        ->default(true)
                        ->inline(false),
                    Textarea::make('description')->label('Description')->columnSpanFull(),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->alignCenter(true)->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('name')
                    ->searchable(isIndividual: false, isGlobal: false),
                TextColumn::make('code')->alignCenter(true)
                    ->searchable(isIndividual: false, isGlobal: false),
                IconColumn::make('is_fractional')
                    ->label('Fractional?')
                    ->boolean()
                    ->alignCenter(true)
                    ->sortable(),

                IconColumn::make('active')->label('Active?')
                ->boolean()
                ->sortable()->alignCenter(true),
            ])
            ->filters([
                SelectFilter::make('active')
                    ->options([
                        1 => __('lang.status_active'),
                        0 => __('lang.status_unactive'),
                    ]),
                TrashedFilter::make(),

            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    // Tables\Actions\ForceDeleteAction::make(),
                    RestoreAction::make(),
                ])
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
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
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getActiveNavigationIcon(): string
    {
        return 'heroicon-s-document-text';
    }
}