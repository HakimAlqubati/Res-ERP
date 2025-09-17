<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages\ListDeductions;
use App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages\CreateDeduction;
use App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages\EditDeduction;
use App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages\ViewDeduction;
use App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalarySettingCluster;
use App\Models\Deduction;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DeductionResource extends Resource
{
    protected static ?string $model = Deduction::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::MinusCircle;

    protected static ?string $cluster = HRSalarySettingCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;
    public static function form(Schema $schema): Schema
    {

        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->columns(4)
                    ->label('')->schema([
                        TextInput::make('name')->required()
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
                        TextInput::make('description')->columnSpan(4),
                    ]),
                Fieldset::make()->columnSpanFull()->label('')->columns(6)->schema([
                    Toggle::make('is_penalty')
                        ->live()->hidden(fn($get): bool => ($get('is_specific')))
                        ->default(false),

                    Toggle::make('is_specific')
                        ->label('Custom')
                        ->default(false)
                        ->helperText('This means for specific employee or for general')
                        ->hidden(fn($get): bool => ($get('is_penalty')))->live(),
                    Toggle::make('active')->default(true),
                    Toggle::make('has_brackets')->default(false)->live()
                        ->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific'))),
                    Radio::make('is_percentage')->label('')->live()
                        ->helperText('Set deduction as a salary percentage or fixed amount')->options([
                            'is_percentage' => 'Is percentage',
                            'is_amount' => 'Is amount',
                        ])->default('is_amount')
                        ->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific'))),

                    Grid::make()->columnSpanFull()
                        ->hidden(fn($get): bool => ($get('is_penalty') || $get('is_specific')))
                        ->schema([

                            TextInput::make('amount')->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_amount'))->numeric()
                                ->suffixIcon('heroicon-o-calculator')
                                ->suffixIconColor('success'),
                            // TextInput::make('percentage')
                            //     ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage'))
                            //     ->numeric()
                            //     ->suffixIcon('heroicon-o-percent-badge')
                            //     ->suffixIconColor('success'),


                            Slider::make('percentage')->hintIcon(Heroicon::PercentBadge)
                                ->label('Percentage')
                                // ->rangePadding([10, 20])
                                // ->tooltips()
                                ->tooltips(RawJs::make(<<<'JS'
                                    `%${$value.toFixed(0)}`
                                JS))
                                ->pips()
                                ->pipsFilter(RawJs::make(<<<'JS'
                                    ($value % 50) === 0
                                        ? 1
                                        : ($value % 10) === 0
                                            ? 2
                                            : ($value % 25) === 0
                                                ? 0
                                                : -1
                                JS))

                                ->fillTrack()
                                ->required()
                                ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage'))
                                ->minValue(0)
                                ->step(1)
                                ->maxValue(100)
                                ->default(0)
                                ->rtl(),
                            TextInput::make('employer_amount')
                                ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_amount') && (in_array($get('applied_by'), [Deduction::APPLIED_BY_BOTH, Deduction::APPLIED_BY_EMPLOYER])))
                                ->numeric()
                                ->suffixIcon('heroicon-o-calculator')
                                ->suffixIconColor('success'),

                            Slider::make('employer_percentage')->hintIcon(Heroicon::PercentBadge)
                                ->label('Employeer Percentage')
                                // ->rangePadding([10, 20])
                                // ->tooltips()
                                ->tooltips(RawJs::make(<<<'JS'
                                    `%${$value.toFixed(0)}`
                                JS))
                                ->pips()
                                ->pipsFilter(RawJs::make(<<<'JS'
                                    ($value % 50) === 0
                                        ? 1
                                        : ($value % 10) === 0
                                            ? 2
                                            : ($value % 25) === 0
                                                ? 0
                                                : -1
                                JS))

                                ->fillTrack()
                                ->required()
                                ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage') && (in_array($get('applied_by'), [Deduction::APPLIED_BY_BOTH, Deduction::APPLIED_BY_EMPLOYER])))
                                ->minValue(0)
                                ->step(1)
                                ->maxValue(100)
                                ->default(0)
                                ->rtl(),

                            // TextInput::make('employer_percentage')
                            //     ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage') && (in_array($get('applied_by'), [Deduction::APPLIED_BY_BOTH, Deduction::APPLIED_BY_EMPLOYER])))
                            //     ->numeric()
                            //     ->suffixIcon('heroicon-o-percent-badge')
                            //     ->suffixIconColor('success'),
                        ]),
                    // Tax Brackets Repeater
                    Repeater::make('brackets')  // The relationship field for Deduction Brackets
                        ->label('Tax Brackets')->columnSpanFull()
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
                            TextInput::make('percentage')->label('Percentage')
                                ->required()->numeric()->suffix(' %')->maxValue(100),
                        ])
                        ->createItemButtonLabel('Add Tax Bracket')
                        ->defaultItems(1),  // Default number of tax brackets
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')->sortable()->searchable()->wrap(),
                TextColumn::make('description')->wrap()->toggleable(isToggledHiddenByDefault: true),
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
                ToggleColumn::make('active')->disabled(fn(): bool => isBranchManager()),
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
            'index' => ListDeductions::route('/'),
            'create' => CreateDeduction::route('/create'),
            'edit' => EditDeduction::route('/{record}/edit'),
            'view' => ViewDeduction::route('/{record}'),
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
