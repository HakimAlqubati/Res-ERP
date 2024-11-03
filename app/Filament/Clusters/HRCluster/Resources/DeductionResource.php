<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages;
use App\Filament\Clusters\HRSalaryCluster;
use App\Models\Deduction;
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

class DeductionResource extends Resource
{
    protected static ?string $model = Deduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRSalaryCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columns(3)->label('')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('description')->columnSpan(2),
                ]),
                Fieldset::make()->label('')->columns(5)->schema([
                    Forms\Components\Toggle::make('is_penalty')->default(false),
                    Forms\Components\Toggle::make('is_specific')->default(false)
                        ->helperText('This means for specific employee or for general')
                    ,
                    Forms\Components\Toggle::make('active')->default(true), 
                    Radio::make('is_percentage')->label('')->live()
                        ->helperText('Set allowance as a salary percentage or fixed amount')  ->options([
                            'is_percentage' => 'Is percentage',
                            'is_amount' => 'Is amount',
                        ])->default('is_amount') 
                    ,
                    
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
                Tables\Columns\ToggleColumn::make('is_penalty'),
                Tables\Columns\ToggleColumn::make('is_specific')->label('Custom'),
                // IconColumn::make('is_percentage')
                //     ->color(fn(string $state): string => match ($state) {

                //         0 => 'warning',
                //         1 => 'success',
                //         default => 'gray',
                //     }),
                ToggleColumn::make('is_percentage')->disabled(),
                TextColumn::make('amount')
                    ->hidden(fn($record) => $record?->is_percentage)
                ,
                TextColumn::make('percentage'),
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
            'index' => Pages\ListDeductions::route('/'),
            'create' => Pages\CreateDeduction::route('/create'),
            'edit' => Pages\EditDeduction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager()) {
            return true;
        }
        return false;
    }
}
