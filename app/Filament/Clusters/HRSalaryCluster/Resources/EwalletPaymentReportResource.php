<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Models\EwalletPaymentReport;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\Pages;
use Filament\Actions\Action;

class EwalletPaymentReportResource extends Resource
{
    protected static ?string $model = EwalletPaymentReport::class;
    
    protected static ?string $slug = 'ewallet-payment-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?string $label = 'E-Wallet Payment Report';
    protected static ?string $pluralLabel = 'E-Wallet Payment Reports';

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
            ->columns([
                TextColumn::make('month')
                    ->label('Month')
                    ->sortable()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::create()->month($state)->format('F')),
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Amount (RM)')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('employees_count')
                    ->label('Employees Count')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'exported' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (EwalletPaymentReport $record) {
                        $monthName = \Carbon\Carbon::create()->month($record->month)->format('F');
                        $fileName = "TnG_Payment_Report_{$monthName}_{$record->year}.xlsx";

                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Modules\HR\PayrollReports\Exports\EwalletPaymentExport($record), 
                            $fileName
                        );
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // \App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEwalletPaymentReports::route('/'),
            'view' => Pages\ViewEwalletPaymentReport::route('/{record}'),
        ];
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
