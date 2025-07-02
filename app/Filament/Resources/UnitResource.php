<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ProductUnitCluster;
use App\Filament\Resources\UnitResource\Pages;
use App\Filament\Resources\UnitResource\RelationManagers;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;
    protected static ?string $cluster = ProductUnitCluster::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'name';

    // protected static ?string $navigationGroup = 'Products - units';

    protected static ?string $activeNavigationIcon = 'heroicon-s-document-text';
    // protected static bool $shouldRegisterNavigation = false;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Units';
    public static function getNavigationLabel(): string
    {
        return __('lang.units');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(3)->schema([
                    Forms\Components\TextInput::make('name')->required()
                        ->live(onBlur: true)
                        ->unique(ignoreRecord: true)
                        ->afterStateUpdated(fn($set, $state): string => $set('code', str_replace(' ', '-', $state)))
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('code')
                        ->unique(ignoreRecord: true)
                        ->required(),
                    Toggle::make('active')->inline(false)->default(true),
                    Toggle::make('is_fractional')
                        ->label('Can be fractional?')
                        ->helperText('Enable this if this unit allows decimal quantities.')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\Textarea::make('description')->label('Description')->columnSpanFull(),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->alignCenter(true)->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: true, isGlobal: false),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(isIndividual: false, isGlobal: false),
                Tables\Columns\TextColumn::make('code')->alignCenter(true)
                    ->searchable(isIndividual: false, isGlobal: false),
                Tables\Columns\IconColumn::make('is_fractional')
                    ->label('Fractional?')
                    ->boolean()
                    ->alignCenter(true)
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')->label('Active?')
                ->boolean()
                ->sortable()->alignCenter(true),
            ])
            ->filters([
                SelectFilter::make('active')
                    ->options([
                        1 => __('lang.status_active'),
                        0 => __('lang.status_unactive'),
                    ]),
                Tables\Filters\TrashedFilter::make(),

            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    // Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                // Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
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