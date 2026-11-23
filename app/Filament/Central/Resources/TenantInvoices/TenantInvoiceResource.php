<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantInvoices;

use App\Filament\Central\Resources\TenantInvoices\Pages\CreateTenantInvoice;
use App\Filament\Central\Resources\TenantInvoices\Pages\EditTenantInvoice;
use App\Filament\Central\Resources\TenantInvoices\Pages\ListTenantInvoices;
use App\Filament\Central\Resources\TenantInvoices\Schemas\TenantInvoiceForm;
use App\Filament\Central\Resources\TenantInvoices\Tables\TenantInvoicesTable;
use App\Models\TenantInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TenantInvoiceResource extends Resource
{
    protected static ?string $model = TenantInvoice::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | UnitEnum | null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getNavigationLabel(): string
    {
        return __('Tenant Invoices');
    }

    public static function getModelLabel(): string
    {
        return __('Tenant Invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tenant Invoices');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['tenant']);
    }

    public static function form(Schema $schema): Schema
    {
        return TenantInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantInvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenantInvoices::route('/'),
            'create' => CreateTenantInvoice::route('/create'),
            'edit' => EditTenantInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $unpaidCount = static::getModel()::unpaid()->count();

        return $unpaidCount > 0 ? (string) $unpaidCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
