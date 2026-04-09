<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\ShiftReportResource\Pages\ListShiftReports;
use App\Models\Branch;
use App\Models\WorkPeriod;
use Filament\Actions\BulkActionGroup;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShiftReportResource extends Resource
{
    protected static ?string $model = \App\Models\EmployeePeriod::class;
    protected static ?string $slug  = 'shift-reports';
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?int $navigationSort = 7;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return __('lang.shift_report');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.shift_report');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.shift_report');
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->emptyStateHeading(__('lang.no_data'))
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->placeholder(__('lang.choose'))
                    ->options(Branch::active()->forBranchManager('id')->get()->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('period_id')
                    ->label(__('lang.shift'))
                    ->placeholder(__('lang.choose'))
                    ->options(WorkPeriod::query()->get()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),

            ], FiltersLayout::AboveContent)
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShiftReports::route('/'),
        ];
    }
}
