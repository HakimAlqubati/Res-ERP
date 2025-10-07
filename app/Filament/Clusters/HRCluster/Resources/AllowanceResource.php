<?php

namespace App\Filament\Clusters\HRCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages\ListAllowances;
use App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages\CreateAllowance;
use App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages\EditAllowance;
use App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages\ViewAllowance;
use App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalarySettingCluster;
use App\Models\Allowance;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AllowanceResource extends Resource
{
    protected static ?string $model = Allowance::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::CurrencyDollar;

    protected static ?string $cluster = HRSalarySettingCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->columns(3)->label('')->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('description')->columnSpan(2),
                ]),
                Fieldset::make()->columnSpanFull()->label('')->columns(4)->schema([
                    Toggle::make('is_specific')->default(false)->label('Custom')
                        ->helperText('This means for specific employee or for general'),
                    Toggle::make('active')->default(true),
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
                        ->suffixIconColor('success'),

                    Slider::make('percentage')
                        ->hintIcon(Heroicon::PercentBadge)
                        ->label('Percentage')
                        ->tooltips(RawJs::make(<<<'JS'
                            `%${$value.toFixed(1)}`
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
                        ->step(0.1) // ✅ يسمح بالقيم الكسرية مثل 0.1 أو 25.5
                        ->maxValue(100)
                        ->default(0)
                        ->rtl(),
                    // TextInput::make('percentage')
                    // ->visible(fn(Get $get): bool => ($get('is_percentage') == 'is_percentage'))->numeric()
                    //     ->suffixIcon('heroicon-o-percent-badge')
                    //     ->suffixIconColor('success'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('description'),
                ToggleColumn::make('is_specific')->label('Custom')->disabled()->hidden(),
                ToggleColumn::make('is_percentage')->disabled(),
                TextColumn::make('amount'),
                TextColumn::make('percentage')->suffix(' % '),
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
            'index' => ListAllowances::route('/'),
            'create' => CreateAllowance::route('/create'),
            'edit' => EditAllowance::route('/{record}/edit'),
            'view' => ViewAllowance::route('/{record}'),
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
