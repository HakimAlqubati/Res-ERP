<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages\ListEmployeeAbsentReports;
use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeAbsentsReportResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::DocumentMinus;

    protected static ?string $cluster = HRAttendanceReport::class;

    public static function getModelLabel(): string
    {
        return __('lang.absence_report');
    }
    // public static function getModelLabel(): string
    // {
    //     return isStuff() ? 'My attendance' : 'Attendance by employee';
    // }
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('lang.no_data'))
            ->deferFilters(false)
            ->columns([])
            ->filters([
                SelectFilter::make('branch_id')
                    ->placeholder('Select Branch')
                    ->label(__('lang.branch'))->options(Branch::where('active', 1)
                        ->select('name', 'id')
                        ->get()->pluck('name', 'id'))
                    ->default(function () {
                        if (isBranchManager()) {
                            return auth()->user()?->branch_id;
                        }
                    })
                    ->searchable(),
                Filter::make('date_range')
                    ->schema([
                        \Filament\Forms\Components\Select::make('type')
                            ->label(__('lang.type'))
                            ->options([
                                'single' => __('lang.date'),
                                'range'  => __('lang.date_range'),
                            ])
                            ->default('single')
                            ->live(),

                        DatePicker::make('date')
                            ->label(__('lang.date'))
                            ->default(now()->format('Y-m-d'))
                            ->hidden(fn($get) => $get('type') !== 'single')
                            ->live()
                            ,

                        DatePicker::make('start_date')
                            ->label(__('lang.start_date'))
                            ->default(now()->startOfMonth()->format('Y-m-d'))
                            ->hidden(fn($get) => $get('type') !== 'range')
                            ->live(),

                        DatePicker::make('end_date')
                            ->label(__('lang.end_date'))
                            ->default(now()->format('Y-m-d'))
                            ->hidden(fn($get) => $get('type') !== 'range')
                            ->live(),
                    ]),
            ], FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
            ])
        ;
    }

    public static function canCreate(): bool
    {
        return false;
    }


    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager()) {
            return true;
        }
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeAbsentReports::route('/'),
        ];
    }

    protected static bool $shouldRegisterNavigation = true;
}
