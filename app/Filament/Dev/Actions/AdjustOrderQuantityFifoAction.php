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

        $this->label(__('📦 تعديل كمية الطلب (FIFO)'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->color(Color::Amber)
            ->modalHeading(__('تعديل كمية صنف في طلب مع تخصيص الفارق عبر FIFO'))
            ->modalDescription(__('سيتم حساب الفارق بين الكمية الحالية والجديدة وسحبه من المخزن عبر دفعات الـ FIFO وتحديث حركات المخزن وتفاصيل الطلب والقيد المالي تلقائياً.'))
            ->modalSubmitActionLabel(__('تأكيد التعديل والتخصيص'))
            ->modalWidth('lg')
            ->schema([
                TextInput::make('order_id')
                    ->label(__('رقم الطلب (Order ID)'))
                    ->placeholder(__('أدخل رقم الطلب مثلاً 105'))
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set) => $set('product_id', null))
                    ->helperText(__('أدخل رقم الطلب ثم اختر الصنف أدناه')),

                Select::make('product_id')
                    ->label(__('الصنف (Product)'))
                    ->placeholder(__('اختر الصنف من أصناف الطلب'))
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
                                return [$d->product_id => "{$productName} (الكمية الحالية: {$d->available_quantity} {$unitName})"];
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
                                return "الكمية الحالية: {$detail->available_quantity} {$unitName} | سعر الوحدة: {$price}";
                            }
                        }
                        return __('اختر الصنف المراد تعديل كميته');
                    }),

                TextInput::make('new_qty')
                    ->label(__('الكمية الجديدة المطلوبة (New Quantity)'))
                    ->placeholder(__('مثال: 2.5'))
                    ->required()
                    ->numeric()
                    ->minValue(0.0001)
                    ->helperText(__('أدخل الكمية الإجمالية الجديدة (مثلاً 2.5). سيتم حساب الفارق وإخراجه عبر FIFO تلقائياً.')),
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
                            ->title(__('تم تعديل كمية الطلب بنجاح عبر FIFO'))
                            ->body("تم تحديث الطلب #{$orderId} للصنف #{$productId} إلى الكمية {$newQty} وتخصيص الفارق عبر FIFO بنجاح.")
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('فشل تعديل كمية الطلب'))
                            ->body($output ?: __('حدث خطأ أثناء تنفيذ عملية التخصيص.'))
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(__('خطأ غير متوقع'))
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}
