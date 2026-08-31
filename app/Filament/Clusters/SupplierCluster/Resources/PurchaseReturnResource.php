<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources;

use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\CreatePurchaseReturn;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\EditPurchaseReturn;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\ListPurchaseReturns;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages\ViewPurchaseReturn;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Schemas\PurchaseReturnForm;
use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Tables\PurchaseReturnTable;
use App\Models\PurchaseReturn;
use App\Modules\Stock\PurchaseReturns\Actions\ApprovePurchaseReturnAction;
use App\Modules\Stock\PurchaseReturns\Actions\CancelPurchaseReturnAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Throwable;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowUturnLeft;

    protected static ?string $cluster = SupplierCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'return_no';

    public static function getNavigationLabel(): string
    {
        return 'Purchase Returns';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Purchase Returns';
    }

    public static function getLabel(): ?string
    {
        return 'Purchase Return';
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseReturnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseReturnTable::configure($table);
    }

    /**
     * Centralized Reusable Approve Action for Table, View, and Edit pages.
     */
    public static function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve Return')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn(PurchaseReturn $record): bool => $record->status === PurchaseReturn::STATUS_DRAFT && ! $record->cancelled)
            ->requiresConfirmation()
            ->modalHeading('Approve Purchase Return')
            ->modalDescription('Approving this return will deduct items from inventory and create a supplier debit note. Are you sure you want to proceed?')
            ->action(function (PurchaseReturn $record, ApprovePurchaseReturnAction $action) {
                try {
                    $action->execute($record, (int) auth()->id());

                    Notification::make()
                        ->title('Approved')
                        ->body('Purchase return has been approved successfully.')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Approval Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Centralized Reusable Cancel Action for Table, View, and Edit pages.
     */
    public static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel Return')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn(PurchaseReturn $record): bool => ! $record->cancelled)
            ->form([
                Textarea::make('cancel_reason')
                    ->label('Cancellation Reason')
                    ->required(),
            ])
            ->action(function (PurchaseReturn $record, array $data, CancelPurchaseReturnAction $action) {
                try {
                    $action->execute($record, $data['cancel_reason'], (int) auth()->id());

                    Notification::make()
                        ->title('Cancelled')
                        ->body('Purchase return has been cancelled.')
                        ->warning()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Cancellation Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPurchaseReturns::route('/'),
            'create' => CreatePurchaseReturn::route('/create'),
            'edit'   => EditPurchaseReturn::route('/{record}/edit'),
            'view'   => ViewPurchaseReturn::route('/{record}'),
        ];
    }
}
