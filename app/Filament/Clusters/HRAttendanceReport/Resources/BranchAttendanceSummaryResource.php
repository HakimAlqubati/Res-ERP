<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages\ListBranchAttendanceSummary;
use App\Models\Attendance;
use App\Models\Branch;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchAttendanceSummaryResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $cluster = HRAttendanceReport::class;

    public static function getModelLabel(): string
    {
        return 'Attendance Summary';
        // return __('lang.branch_attendance_summary');
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
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
                    ->label(__('lang.branch'))
                    ->options(Branch::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))
                    ->default(function () {
                        if (isBranchManager()) {
                            return auth()->user()?->branch_id;
                        }
                    })
                    ->searchable(),
                SelectFilter::make('month')
                    // ->label(__('lang.month'))
                    ->placeholder('Select Month')

                    ->options(fn() => getMonthOptionsBasedOnSettings())
                    ->default(now()->subMonth()->format('F Y')),
            ], FiltersLayout::AboveContent)
            ->recordActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isHR()) {
            return true;
        }
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranchAttendanceSummary::route('/'),
        ];
    }

    protected static bool $shouldRegisterNavigation = true;
    // public static function shouldRegisterNavigation(): bool
    // {
    //     if (isHakimOrAdel()) {
    //         return true;
    //     }
    //     return false;
    // }
}
