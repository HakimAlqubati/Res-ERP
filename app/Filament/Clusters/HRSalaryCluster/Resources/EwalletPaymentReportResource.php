<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\Pages;
use App\Filament\Tables\Actions\RefreshAction;
use App\Models\EwalletPaymentReport;
use App\Modules\HR\PayrollReports\Exports\EwalletPaymentExport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;

class EwalletPaymentReportResource extends Resource
{
    protected static ?string $model = EwalletPaymentReport::class;

    protected static ?string $slug = 'ewallet-payment-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?string $label = "Payment Sheet";

    protected static ?string $pluralLabel = "Payment Sheet";
    protected static ?string $pluralModelLabel = 'Payment Sheet';

    // Disable Filament's default title casing (which applies ucwords())
    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?string $navigationLabel = 'Payment Sheet';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // We typically don't edit the header manually, so this can be read-only or empty.
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->headerActions([RefreshAction::make()])
            ->columns([
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->searchable()
                    ,
                TextColumn::make('month')
                    ->label('Month')
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => Carbon::create()->month($state)->format('F')),
                
                TextColumn::make('total_amount')
                    ->label('Total Amount') 
                    ->color('primary')
                    ->sortable()
                    ->alignCenter()
                    ->summarize(Sum::make()->formatStateUsing(fn($state) => formatMoneyWithCurrency($state)))
                    ->formatStateUsing(fn($state)=>formatMoneyWithCurrency($state))
                    ,
                TextColumn::make('employees_count')
                    ->label('Employees Count')
                    ->sortable()
                    ->alignCenter()
                    ,
                TextColumn::make('payment_type')
                    ->label('Payment Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'bank' ? 'Bank' : 'eWallet')
                    ->color(fn ($state) => $state === 'bank' ? 'success' : 'info')
                    ->icon(fn ($state) => $state === 'bank' ? 'heroicon-o-building-library' : 'heroicon-o-device-phone-mobile')
                    ->sortable()
                    ->alignCenter(),
            
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->label('Generated At')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([

                TrashedFilter::make()],FiltersLayout::Modal)
            ->filtersFormColumns(4)

            ->recordActions([
                self::getExportExcelAction(Action::class),
                self::getExportPdfAction(Action::class),
                // DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getExportExcelAction(string $actionClass)
    {
        return $actionClass::make('export_excel')
            ->label('Export Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function (EwalletPaymentReport $record) {
                $monthName = Carbon::create()->month($record->month)->format('F');
                $isBank = $record->payment_type === EwalletPaymentReport::TYPE_BANK;
                $prefix = $isBank ? 'Bank_Payment_Report' : 'TnG_Payment_Report';
                $fileName = "{$prefix}_{$monthName}_{$record->year}.xlsx";

                return Excel::download(
                    new EwalletPaymentExport($record),
                    $fileName
                );
            });
    }

    public static function getExportPdfAction(string $actionClass)
    {
        return $actionClass::make('export_pdf')
            ->label('Export PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('danger')
            ->action(function (EwalletPaymentReport $record) {
                $record->load('items');
                $isBank = $record->payment_type === EwalletPaymentReport::TYPE_BANK;
                $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('reports.hr.ewallet-payment-report-pdf', [
                    'report' => $record,
                    'paymentType' => $record->payment_type,
                    'isBank' => $isBank,
                ]);
                
                $monthName = Carbon::create()->month($record->month)->format('F');
                $prefix = $isBank ? 'Bank_Sheet' : 'eWallet_Sheet';
                $fileName = "{$prefix}_{$monthName}_{$record->year}.pdf";

                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $fileName);
            });
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEwalletPaymentReports::route('/'),
            'view' => Pages\ViewEwalletPaymentReport::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isFinanceManager();
    }
}
