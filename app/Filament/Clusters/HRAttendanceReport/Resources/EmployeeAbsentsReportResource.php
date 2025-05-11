<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Absence Report';
    // public static function getModelLabel(): string
    // {
    //     return isStuff() ? 'My attendance' : 'Attendance by employee';
    // }
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No data')
            ->columns([])
            ->filters([
                SelectFilter::make('branch_id')->label('Branch')->options(Branch::withAccess()->active()
                    ->select('name', 'id')->get()->pluck('name', 'id'))

                    ->default(function () {
                        if (isBranchManager()) {
                            return auth()->user()?->branch_id;
                        }
                    })
                    ->searchable(),
                Filter::make('filter_date')->label('')->form([
                    DatePicker::make('date')
                        ->label('Date')->default(date('Y-m-d')),
                    TimePicker::make('current_time')
                        ->label('Current time')
                        ->default(now()->timezone('Asia/Kuala_Lumpur')->format('H:i'))
                        ->withoutSeconds(),
                ]),

            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListEmployeeAbsentReports::route('/'),
        ];
    }
}
