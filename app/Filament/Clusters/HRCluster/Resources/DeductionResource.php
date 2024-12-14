<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages;
use App\Filament\Clusters\HRSalaryCluster;
use App\Models\Deduction;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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
                Fieldset::make()->columns(4)
                    ->label('')->schema([
                        Forms\Components\TextInput::make('name')->required()
                            ->columnSpan(fn($get): int => ($get('is_penalty') || $get('is_specific')) ? 4 : 1),
                        Select::make('condition_applied_v2')->live()
                            ->label('Condition applied')
                            ->options(Deduction::getConditionAppliedV2Options())
                            ->default(Deduction::CONDITION_APPLIED_V2_ALL)
                            ->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific'))),
                        Select::make('applied_by')->live()->label('Borne by')->options(
                            Deduction::getAppliedByOptions()

                        )->default(Deduction::APPLIED_BY_EMPLOYEE)->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific'))),
                        TextInput::make('less_salary_to_apply')
                            ->label('Less salary to apply')->numeric()
                            // ->visible(fn($get): bool => $get('condition_applied_v2') != Deduction::CONDITION_ALL)
                            ->hidden()
                            ->required(),
                        Forms\Components\TextInput::make('description')->columnSpan(4),
                    ]),
                Fieldset::make()->label('')->columns(6)->schema([
                    Forms\Components\Toggle::make('is_penalty')
                        ->live()->hidden(fn($get): bool => ($get('is_specific')))
                        ->default(false),

                    Forms\Components\Toggle::make('is_specific')
                        ->label('Custom')
                        ->default(false)
                        ->helperText('This means for specific employee or for general')
                        ->hidden(fn($get): bool => ($get('is_penalty')))->live(),
                    Forms\Components\Toggle::make('active')->default(true),
                    Forms\Components\Toggle::make('has_brackets')->default(false)->live()
                        ->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific'))),
                    Radio::make('is_percentage')->label('')->live()
                        ->helperText('Set deduction as a salary percentage or fixed amount')->options([
                            'is_percentage' => 'Is percentage',
                            'is_amount' => 'Is amount',
                        ])->default('is_amount')
                        ->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific'))),

                    Grid::make()
                        ->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific')))
                        ->schema([

                            TextInput::make('amount')->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_amount'))->numeric()
                                ->suffixIcon('heroicon-o-calculator')
                                ->suffixIconColor('success'),
                            TextInput::make('percentage')->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage'))->numeric()
                                ->suffixIcon('heroicon-o-percent-badge')
                                ->suffixIconColor('success'),
                            TextInput::make('employer_amount')
                                ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_amount') && (in_array($get('applied_by'), [Deduction::APPLIED_BY_BOTH, Deduction::APPLIED_BY_EMPLOYER])))
                                ->numeric()
                                ->suffixIcon('heroicon-o-calculator')
                                ->suffixIconColor('success'),
                            TextInput::make('employer_percentage')
                                ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage') && (in_array($get('applied_by'), [Deduction::APPLIED_BY_BOTH, Deduction::APPLIED_BY_EMPLOYER])))
                                ->numeric()
                                ->suffixIcon('heroicon-o-percent-badge')
                                ->suffixIconColor('success'),
                        ]),
                    // Tax Brackets Repeater
                    Repeater::make('brackets')  // The relationship field for Deduction Brackets
                        ->label('Tax Brackets')
                        ->relationship('brackets')
                        ->visible(fn($get): bool => $get('has_brackets'))->columnSpanFull()->columns(3)
                        ->schema([
                            TextInput::make('min_amount')
                                ->minValue(0)->maxValue(10000000000000)
                                ->label('Minimum Amount')->required()->numeric(),
                            TextInput::make('max_amount')
                                ->minValue(0)
                                ->maxValue(
                                    10000000000000
                                )->label('Maximum Amount')->required()->numeric()->minValue(0),
                            TextInput::make('percentage')->label('Percentage')->required()->numeric()->suffix(' %')->maxValue(100),
                        ])
                        ->createItemButtonLabel('Add Tax Bracket')
                        ->defaultItems(1),  // Default number of tax brackets
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // dd(
        //     Deduction::find(11)->calculateTax(5000),
        //     Deduction::calculateTax2(5000)
        // );
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable()->wrap(),
                Tables\Columns\TextColumn::make('description')->wrap()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_penalty')->alignCenter(true)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                IconColumn::make('is_specific')->label('Custom')->alignCenter(true)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->hidden(false),

                IconColumn::make('is_percentage')->alignCenter(true)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                TextColumn::make('amount')->hidden(),
                TextColumn::make('percentage')->suffix(' % ')->hidden(),
                TextColumn::make('amount_percentage')
                    ->label('Amount/Percentage')->alignCenter(true)
                    ->getStateUsing(function ($record) {
                        if ($record->is_percentage) {
                            return ($record->percentage ?? 0) . ' %';
                        }
                        return $record->amount ?? 0;
                    }),
                Tables\Columns\ToggleColumn::make('active')->disabled(fn(): bool => isBranchManager()),
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
            'view' => Pages\ViewDeduction::route('/{record}'),
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

    public static function canEdit(Model $record): bool
    {
        if (isSuperAdmin() ||  isSystemManager()) {
            return true;
        }
        return false;
    }


    public static function canCreate(): bool
    {

        if (isSystemManager()  || isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
