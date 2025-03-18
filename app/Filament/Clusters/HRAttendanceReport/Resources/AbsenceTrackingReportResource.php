<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AbsenceTrackingReportResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Absence Tracking Report';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([ // Define form schema if needed
                // You can define form components here
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No data')
            ->columns([
                // Define table columns here
            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')->options(Branch::where('active', 1)
                    ->select('name', 'id')
                    ->get()->pluck('name', 'id'))
                    ->default(function () {
                        if (isBranchManager()) {
                            return auth()->user()?->branch_id;
                        }
                    })
                    ->searchable(),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $endNextMonth = Carbon::parse($state)->endOfMonth()->format('Y-m-d');
                                $set('end_date', $endNextMonth);
                            })
                            ->label('Start Date')->default(\Carbon\Carbon::now()->startOfMonth()->toDateString()),
                        DatePicker::make('end_date')
                            ->default(\Carbon\Carbon::now()->endOfMonth()->toDateString())
                            ->label('End Date'),

                    ]),

            ], FiltersLayout::AboveContent)
            ->actions([
                // Tables\Actions\EditAction::make(),
            ]);
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
            'index' => Pages\ListAbsenceTrackingReports::route('/'),
        ];
    }
}
