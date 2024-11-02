<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;
use App\Models\Attendance;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
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

class EmployeeAttednaceReportResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Attendance by employee';
    public static function getModelLabel(): string
    {
        return isStuff() ? 'My records' : 'Attendance by employee';
    }
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
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
            ->columns([

            ])
            ->filters([
                SelectFilter::make('employee_id')->label('Employee')->options(Employee::where('active', 1)
                        ->select('name', 'id')

                        ->get()->pluck('name', 'id'))
                
                   ->hidden(fn()=> isStuff())
                    ->searchable(),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date')->default(\Carbon\Carbon::now()->startOfMonth()->toDateString()),
                        DatePicker::make('end_date')
                            ->default(\Carbon\Carbon::now()->endOfMonth()->toDateString())
                            ->label('End Date'),
                    ]),

            ], FiltersLayout::AboveContent)
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
 
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeAttednaceReports::route('/'),
        ];
    }


}
