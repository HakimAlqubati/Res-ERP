<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\MissingCheckoutReportResource\Pages\ListMissingCheckoutReports;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MissingCheckoutReportResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $slug  = 'missing-checkout-reports';
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::QuestionMarkCircle;

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?int $navigationSort = 6;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return __('lang.missing_checkout_report');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.missing_checkout_report');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.missing_checkout_report');
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

                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label(__('lang.start_date'))
                            ->default(Carbon::today()->startOfMonth())
                            ->required(),
                        DatePicker::make('date_to')
                            ->label(__('lang.end_date'))
                            ->default(Carbon::today()->endOfMonth())
                            ->required(),
                    ]),

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
            'index' => ListMissingCheckoutReports::route('/'),
        ];
    }
}
