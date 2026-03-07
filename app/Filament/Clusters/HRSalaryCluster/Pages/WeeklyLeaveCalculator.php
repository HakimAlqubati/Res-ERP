<?php

namespace App\Filament\Clusters\HRSalaryCluster\Pages;

use App\Filament\Clusters\HRSalaryCluster;
use App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator as CalculatorService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WeeklyLeaveCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Calculator;

    protected   string $view = 'filament.clusters.hr-salary-cluster.pages.weekly-leave-calculator';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Weekly Leave Calculator';
    protected ?string $heading = 'Weekly Leave Calculator';

    // Form State
    public ?array $data = [];

    // Result State
    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill([
            'total_month_days' => 30,
            'absent_days' => 0,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Fieldset::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('total_month_days')
                                ->label('Total Month Days')
                                ->helperText('Enter total days of the month')
                                ->numeric()
                                ->required()
                                ->default(30),

                            TextInput::make('absent_days')
                                ->label('Absent Days')
                                ->helperText('Enter the number of absent days')
                                ->numeric()
                                ->step('0.1')
                                ->required()
                                ->default(0),
                        ]),
                    ])
            ])
            ->statePath('data');
    }

    public function calculate(): void
    {
        $data = $this->form->getState();

        $totalMonthDays = (int) $data['total_month_days'];
        $absentDays = (float) $data['absent_days'];

        $calculator = new CalculatorService();
        $this->result = $calculator->calculate($totalMonthDays, $absentDays);
    }
}
