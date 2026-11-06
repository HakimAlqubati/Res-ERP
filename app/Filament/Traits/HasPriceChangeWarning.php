<?php

namespace App\Filament\Traits;

use App\Models\Product;
use App\Models\Unit;
use App\Modules\Stock\PriceValidation\Services\PriceChecker;
use Filament\Actions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

trait HasPriceChangeWarning
{
    public bool $isPriceConfirmed = false;

    protected function checkPriceChanges(): void
    {
        if ($this->isPriceConfirmed) {
            return;
        }

        $warnings = $this->getPriceWarnings();
        if (! empty($warnings)) {
            $this->mountAction('confirmPriceChange');
            $this->halt();
        }
    }

    public function confirmPriceChangeAction(): Actions\Action
    {
        return Actions\Action::make('confirmPriceChange')
            ->requiresConfirmation()
            ->modalHeading('Price Change Warning')
            ->slideOver(true)
            ->color('warning')
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalIcon(Heroicon::ChartBarSquare)
            ->modalWidth(Width::SevenExtraLarge)
            ->modalDescription(__('lang.price_change_warning'))
            ->schema([
                Repeater::make('warnings')
                    ->hiddenLabel()
                    ->schema([
                        Hidden::make('product_id'),
                        Hidden::make('unit_id'),
                        TextInput::make('product_name')->disabled(true)->label('Product')->columnSpan(2),
                        TextInput::make('unit_name')->disabled(true)->label('Unit')->columnSpan(1),
                        TextInput::make('old_price')->disabled(true)->label('Old Price')->columnSpan(1),
                        TextInput::make('new_price')->numeric()->label(new HtmlString('<span style="color: red;">New Price</span>'))->columnSpan(1)->extraInputAttributes(['style' => 'color: red !important; -webkit-text-fill-color: red !important; font-weight: bold;']),
                        TextInput::make('change_percent')->disabled(true)->label(new HtmlString('<span style="color: red;">Change %</span>'))->columnSpan(1)->extraInputAttributes(['style' => 'color: red !important; -webkit-text-fill-color: red !important; font-weight: bold;']),
                    ])
                    ->columns(6)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->default(function () {
                        $warnings = $this->getPriceWarnings();
                        $data = [];
                        foreach ($warnings as $warning) {
                            $product = Product::find($warning->productId);
                            $unit = Unit::find($warning->unitId);
                            $sign = $warning->changePercent > 0 ? '+' : '';
                            $data[] = [
                                'product_id' => $warning->productId,
                                'unit_id' => $warning->unitId,
                                'product_name' => $product?->name ?? 'Unknown',
                                'unit_name' => $unit?->name ?? 'Unknown',
                                'old_price' => number_format($warning->normalizedLastPrice, 2),
                                'new_price' => round($warning->normalizedNewPrice, 4),
                                'change_percent' => $sign.$warning->changePercent.'%',
                            ];
                        }

                        return $data;
                    }),
            ])
            ->modalSubmitActionLabel('Confirm & Save')
            ->action(function (array $data) {
                if (!empty($data['warnings'])) {
                    $newPrices = [];
                    foreach ($data['warnings'] as $warningRow) {
                        if (isset($warningRow['product_id'], $warningRow['unit_id'], $warningRow['new_price'])) {
                            $key = $warningRow['product_id'] . '_' . $warningRow['unit_id'];
                            $newPrices[$key] = $warningRow['new_price'];
                        }
                    }

                    $totalInvoiceAmount = 0;
                    
                    $updatePricesInArray = function (&$itemsArray) use ($newPrices, &$totalInvoiceAmount) {
                        if (is_array($itemsArray)) {
                            foreach ($itemsArray as $key => $item) {
                                $pId = $item['product_id'] ?? null;
                                $uId = $item['unit_id'] ?? null;
                                if ($pId && $uId) {
                                    $lookup = $pId . '_' . $uId;
                                    if (isset($newPrices[$lookup])) {
                                        $newPrice = (float) $newPrices[$lookup];
                                        $itemsArray[$key]['price'] = $newPrice;
                                        
                                        // Recalculate row total
                                        $quantity = (float) ($item['quantity'] ?? 1);
                                        $rowTotal = round($newPrice * $quantity, 2);
                                        $itemsArray[$key]['total_price'] = $rowTotal;
                                    }
                                }
                                // Accumulate global total
                                $totalInvoiceAmount += (float) ($itemsArray[$key]['total_price'] ?? 0);
                            }
                        }
                    };

                    if (isset($this->data['grnDetails'])) {
                        $updatePricesInArray($this->data['grnDetails']);
                    } elseif (isset($this->data['units'])) {
                        $updatePricesInArray($this->data['units']);
                    } elseif (isset($this->data['purchaseInvoiceDetails'])) {
                        $updatePricesInArray($this->data['purchaseInvoiceDetails']);
                    }
                    
                    if (array_key_exists('total_amount', $this->data)) {
                        $this->data['total_amount'] = round($totalInvoiceAmount, 4);
                    }
                }

                $this->isPriceConfirmed = true;
                
                if (method_exists($this, 'create')) {
                    $this->create(); // Resume creation
                } elseif (method_exists($this, 'save')) {
                    $this->save(); // Resume saving
                }
            });
    }

    protected function getPriceWarnings(): array
    {
        $details = $this->data['grnDetails'] ?? $this->data['units'] ?? $this->data['purchaseInvoiceDetails'] ?? [];
        if (empty($details)) {
            return [];
        }

        $results = PriceChecker::checkMany(array_values($details));

        return array_filter($results, fn ($result) => $result->requiresWarning());
    }
}
