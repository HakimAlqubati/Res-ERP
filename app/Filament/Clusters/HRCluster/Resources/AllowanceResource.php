<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages;
use App\Filament\Clusters\HRSalaryCluster;
use App\Models\Allowance;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AllowanceResource extends Resource
{
    protected static ?string $model = Allowance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(3)->label('')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('description')->columnSpan(2),
                ]),
                Fieldset::make()->label('')->columns(4)->schema([
                    Forms\Components\Toggle::make('is_specific')->default(false)->label('Custom')
                    ->helperText('This means for specific employee or for general')
                    ,
                    Forms\Components\Toggle::make('active')->default(true),
                    // Forms\Components\Toggle::make('is_percentage')->live()->default(true)
                    //     ->helperText('Set allowance as a salary percentage or fixed amount')
                    // ,
                    Radio::make('is_percentage')->label('')->live()
                    
                    ->helperText('Set allowance as a salary percentage or fixed amount')
                    ->options([
                        'is_percentage' => 'Is percentage',
                        'is_amount' => 'Is amount',
                    ])->default('is_amount'),
                    TextInput::make('amount')->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_amount'))->numeric()
                        ->suffixIcon('heroicon-o-calculator')
                        ->suffixIconColor('success')
                    ,
                    TextInput::make('percentage')->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage'))->numeric()
                        ->suffixIcon('heroicon-o-percent-badge')
                        ->suffixIconColor('success'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\ToggleColumn::make('is_specific')->label('Custom')->disabled()->hidden(),
                ToggleColumn::make('is_percentage')->disabled(),
                TextColumn::make('amount'),
                TextColumn::make('percentage')->suffix(' % '),
                Tables\Columns\ToggleColumn::make('active'),
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
            'index' => Pages\ListAllowances::route('/'),
            'create' => Pages\CreateAllowance::route('/create'),
            'edit' => Pages\EditAllowance::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }
}
