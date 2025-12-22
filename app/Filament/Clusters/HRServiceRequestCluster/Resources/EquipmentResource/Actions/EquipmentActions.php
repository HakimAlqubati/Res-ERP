<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Actions;

use App\Models\Equipment;
use App\Models\MaintenanceCost;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EquipmentActions
{
    /**
     * Action لإضافة تكلفة الشراء كقيد مالي
     * Sync Purchase Cost Action
     */
    public static function getSyncPurchaseCostAction(): Action
    {
        return Action::make('syncPurchaseCost')
            ->label(__('Sync Purchase Cost'))
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->button()
            ->visible(fn($record) => !$record->has_purchase_cost_synced && $record->purchase_price > 0)
            ->requiresConfirmation()
            ->modalHeading(__('Sync Purchase Cost'))
            ->modalDescription(fn($record) => __('Are you sure you want to create a financial transaction for :amount?', [
                'amount' => number_format($record->purchase_price, 2) . ' MYR'
            ]))
            ->action(function ($record) {
                static::createPurchaseCostTransaction($record);
            });
    }

    /**
     * إنشاء قيد مالي لتكلفة شراء المعدة
     * Create financial transaction for equipment purchase cost
     */
    public static function createPurchaseCostTransaction(Equipment $equipment): void
    {
        // تحقق من عدم وجود تكلفة شراء مسبقة
        if ($equipment->has_purchase_cost_synced) {
            Notification::make()
                ->warning()
                ->title(__('Already Synced'))
                ->body(__('This equipment already has a synced purchase cost.'))
                ->send();
            return;
        }

        // تحقق من وجود سعر الشراء
        if (!$equipment->purchase_price || $equipment->purchase_price <= 0) {
            Notification::make()
                ->danger()
                ->title(__('No Purchase Price'))
                ->body(__('This equipment has no purchase price set.'))
                ->send();
            return;
        }

        // إنشاء تكلفة الشراء
        $cost = $equipment->costs()->create([
            'amount' => $equipment->purchase_price,
            'description' => __('Equipment purchase cost'),
            'cost_type' => MaintenanceCost::TYPE_PURCHASE,
            'branch_id' => $equipment->branch_id,
            'cost_date' => $equipment->purchase_date ?? now(),
            'created_by' => auth()->id(),
        ]);

        // الـ Observer سيقوم بإنشاء الـ FinancialTransaction تلقائياً

        Notification::make()
            ->success()
            ->title(__('Purchase Cost Synced'))
            ->body(__('Financial transaction created for :amount', [
                'amount' => number_format($equipment->purchase_price, 2) . ' MYR'
            ]))
            ->send();
    }
}
