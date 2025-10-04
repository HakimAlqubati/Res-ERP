<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Exception;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Throwable;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\PurchaseInvoiceResource\Pages\CreatePurchaseInvoice;
use App\Filament\Resources\PurchaseInvoiceResource\Pages\EditPurchaseInvoice;
use App\Filament\Resources\PurchaseInvoiceResource\Pages\ViewPurchaseInvoice;
use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseInvoiceResource\RelationManagers\DetailsRelationManager;
use App\Filament\Resources\PurchaseInvoiceResource\Pages\ListPurchaseInvoices;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers\PurchaseInvoiceDetailsRelationManager;
use App\Filament\Resources\PurchaseInvoiceResource\Schemas\PurchaseInvoiceForm;
use App\Filament\Resources\PurchaseInvoiceResource\Tables\PurchaseInvoiceTable;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice; 
use Filament\Pages\Page;
use Filament\Resources\Resource; 
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table; 
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope; 

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ArchiveBoxArrowDown;
    protected static ?string $cluster = SupplierCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'invoice_no';
    protected static bool $isGloballySearchable = true;

    public static function getPluralLabel(): ?string
    {
        return __('lang.purchase_invoice');
    }


    public static function getLabel(): ?string
    {
        return __('lang.purchase_invoice');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.purchase_invoice');
    }
    public static function form(Schema $schema): Schema
    {
        return PurchaseInvoiceForm::configure($schema);
    }


    public static function table(Table $table): Table
    {
       return PurchaseInvoiceTable::configure($table);
    }


    public static function getRelations(): array
    {
        return [
            // PurchaseInvoiceDetailsRelationManager::class,
            DetailsRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseInvoices::route('/'),
            'create' => CreatePurchaseInvoice::route('/create'),
            'edit' => EditPurchaseInvoice::route('/{record}/edit'),
            'view' => ViewPurchaseInvoice::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListPurchaseInvoices::class,
            CreatePurchaseInvoice::class,
            EditPurchaseInvoice::class,
            ViewPurchaseInvoice::class,
        ]);
    }

    public static function canDeleteAny(): bool
    {
        return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutTrashed()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        // $query->withDetails();
        return $query;
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function canCreate(): bool
    {
        // if (settingWithDefault('purchase_invoice_from_grn_only', false)) {
        //     return false;
        // }
        if (isSuperAdmin() || isFinanceManager()) {
            return true;
        }
        if (isSuperVisor() || isStoreManager()) {
            return false;
        }

        return static::can('create');
    }


    public static function canEdit(Model $record): bool
    {
        if (isSuperVisor()) {
            return false;
        }
        return static::can('update', $record);
    }
}
