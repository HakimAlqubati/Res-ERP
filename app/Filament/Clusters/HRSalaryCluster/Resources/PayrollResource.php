<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\PayrollActions;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\PayrollForm;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\PayrollTable;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers\PayrollsRelationManager;
use App\Models\PayrollRun;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollResource extends Resource
{
    protected static ?string $model = PayrollRun::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::forBranchManager()->count();
    }

    public static function getNavigationLabel(): string
    {
        return 'Payroll';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Payroll';
    }

    public static function getLabel(): ?string
    {
        return 'Payroll';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(PayrollForm::getSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn(PayrollRun $record): string => PayrollResource::getUrl('view', ['record' => $record]))
            ->columns(PayrollTable::getColumns())
            ->filters(
                PayrollTable::getFilters(),
                FiltersLayout::Modal
            )
            ->recordActions([
                RestoreAction::make()->button()->color('success'),
                ViewAction::make(),
                PayrollActions::earlyInstallmentPaymentAction(),
                PayrollActions::approveAction(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make()
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PayrollsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            // 'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListPayrolls::class,
            Pages\CreatePayroll::class,
            Pages\ViewPayroll::class,
            // Pages\EditPayroll::class,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forBranchManager()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
