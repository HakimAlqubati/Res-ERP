<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Exports\SalariesExport;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\Pages;
use App\Filament\Clusters\HRSalaryCluster\Resources\MonthSalaryResource\RelationManagers\DetailsRelationManager;
use App\Models\Allowance;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\MonthSalary;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

class MonthSalaryResource extends Resource
{
    protected static ?string $model = MonthSalary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Fieldset::make()->label('Set Branch, Month and payment date')->columns(3)->schema([
                    TextInput::make('note_that')->label('Note that!')->columnSpan(3)->hiddenOn('edit')
                        ->disabled()
                    // ->extraAttributes(['class' => 'text-red-600'])
                        ->suffixIcon('heroicon-o-exclamation-triangle')
                        ->suffixIconColor('warning')
                    // ->color(Color::Red)
                        ->default('Employees who have not had their work periods added, will not appear on the payroll.'),
                    Select::make('branch_id')->label('Choose branch')
                        ->disabledOn('edit')
                        ->options(Branch::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id'))
                        ->required()
                        ->helperText('Please, choose a branch'),
                    Select::make('name')->label('Month')->hiddenOn('edit')
                        ->required()
                        ->options(function () {
                            // Get the array of months
                            $months = getMonthsArray();

                            // Map the months to a key-value pair with month names
                            return collect($months)->mapWithKeys(function ($month, $key) {
                                return [$key => $month['name']]; // Using month key as the option key
                            });
                        })
                        ->searchable()
                        ->default(now()->format('F'))
                    ,
                    TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                    Forms\Components\DatePicker::make('payment_date')->required()
                        ->default(date('Y-m-d'))
                    ,
                ]),
                Forms\Components\Textarea::make('notes')->label('Notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('notes'),
                Tables\Columns\TextColumn::make('branch.name')->label('Branch')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Created by')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('payment_date')->date(),
                Tables\Columns\ToggleColumn::make('approved'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('excel_download')->action(function ($record) {
                    return static::exportExcel($record);
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthSalaries::route('/'),
            'create' => Pages\CreateMonthSalary::route('/create'),
            // 'edit' => Pages\EditMonthSalary::route('/{record}/edit'),
            'view' => Pages\ViewMonthSalary::route('/{record}'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    private static function exportExcel($record)
    {

        $branch = $record?->branch?->name;
        $fileName = ('Salaries of' . '-(' . $branch . ')');
        $details = $record?->details;
        $deducationTypes = Deduction::where('is_specific', 0)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();
        $allowanceTypes = Allowance::where('is_specific', 0)->where('active', 1)->select('name', 'id')->pluck('name', 'id')->toArray();

        $data = [];
        foreach ($details as $key => $value) {
            $data[] = [
                'employee_id' => $value->employee_id,
                'employee_no' => $value->employee_no,
                'employee_name' => $value?->employee?->name,
                'job_title' => $value?->employee?->job_title,
                'branch' => $branch,
                'basic_salary' => $value?->basic_salary,
                'net_salary' => $value?->net_salary,
                'overtime_hours' => $value?->overtime_hours,
                'total_incentives' => $value?->total_incentives,
                'total_allowances' => $value?->total_allowances,
                'total_deductions' => $value?->total_deductions,

            ];
        }

        return Excel::download(new SalariesExport($data, $deducationTypes, $allowanceTypes), $fileName . '.xlsx');
    }
}
