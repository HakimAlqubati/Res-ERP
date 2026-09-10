<?php

namespace App\Filament\Dev\Actions;

use App\Models\Order;
use App\Models\OrderDetails;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
 
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class AdjustOrderQuantityFifoAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'adjustOrderQuantityFifo';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Adjust Order Qty (FIFO)')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color(Color::Amber)
            ->modalHeading('Adjust Order Item Quantity (FIFO Allocation)')
            ->modalDescription('The difference between current and new quantity will be calculated and deducted from inventory via FIFO batches, automatically updating inventory transactions, order details, and financial transactions.')
            ->modalSubmitActionLabel('Confirm & Allocate FIFO')
            ->modalWidth('lg')
            ->schema([
                TextInput::make('order_id')
                    ->label('Order ID')
                    ->placeholder('Enter order ID, e.g. 105')
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set) => $set('product_id', null))
                    ->helperText('Enter the order ID to load its items below'),

                Select::make('product_id')
                    ->label('Product')
                    ->placeholder('Select product from order')
                    ->required()
                    ->searchable()
                    ->options(function (Get $get) {
                        $orderId = $get('order_id');
                        if (! $orderId) {
                            return [];
                        }

                        return OrderDetails::where('order_id', $orderId)
                            ->with(['product', 'unit'])
                            ->get()
                            ->mapWithKeys(function ($d) {
                                $productName = $d->product?->name ?? "Product #{$d->product_id}";
                                $unitName = $d->unit?->name ?? '';
                                return [$d->product_id => "{$productName} (Current: {$d->available_quantity} {$unitName})"];
                            })
                            ->toArray();
                    })
                    ->live()
                    ->helperText(function (Get $get) {
                        $orderId = $get('order_id');
                        $productId = $get('product_id');
                        if ($orderId && $productId) {
                            $detail = OrderDetails::where('order_id', $orderId)
                                ->where('product_id', $productId)
                                ->with(['unit', 'product'])
                                ->first();
                            if ($detail) {
                                $unitName = $detail->unit?->name ?? '';
                                $price = function_exists('formatMoneyWithCurrency')
                                    ? formatMoneyWithCurrency($detail->price)
                                    : (string) $detail->price;
                                return "Current Quantity: {$detail->available_quantity} {$unitName} | Unit Price: {$price}";
                            }
                        }
                        return 'Select the product to adjust quantity';
                    }),

                TextInput::make('new_qty')
                    ->label('New Quantity')
                    ->placeholder('e.g. 2.5')
                    ->required()
                    ->numeric()
                    ->minValue(0.0001)
                    ->helperText('Enter the new total quantity (e.g. 2.5). The difference will be allocated via FIFO automatically.'),
            ])
            ->action(function (array $data) {
                $orderId   = (int) $data['order_id'];
                $productId = (int) $data['product_id'];
                $newQty    = (float) $data['new_qty'];

                try {
                    $exitCode = Artisan::call('order:adjust-quantity-fifo', [
                        'orderId'   => $orderId,
                        'productId' => $productId,
                        'newQty'    => $newQty,
                        '--force'   => true,
                    ]);

                    $output = trim(Artisan::output());

                    if ($exitCode === 0) {
                        Notification::make()
                            ->title('Order Quantity Adjusted Successfully via FIFO')
                            ->body("Order #{$orderId} item #{$productId} updated to {$newQty} and difference allocated via FIFO.")
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to Adjust Order Quantity')
                            ->body($output ?: 'An error occurred while executing FIFO allocation.')
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Unexpected Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}
