<?php

namespace App\Modules\Stock\PriceValidation\Services;

use App\Models\Product;
use App\Models\Unit;
use App\Modules\Stock\PriceValidation\Contracts\PriceChangeValidatorInterface;
use App\Modules\Stock\PriceValidation\DTOs\PriceCheckItem;
use App\Modules\Stock\PriceValidation\DTOs\PriceCheckResult;
use Filament\Notifications\Notification;

/**
 * UI helper that wraps the validator and sends Filament notifications.
 *
 * This class bridges the gap between the pure business-logic
 * validator and the Filament form layer. It is intentionally
 * a separate class so the validator stays framework-agnostic.
 *
 * Usage in any Filament afterStateUpdated callback:
 *
 *   PriceChangeNotifier::check($productId, $unitId, $price, $packageSize);
 */
class PriceChangeNotifier
{
    /**
     * Validate a price and send a Filament warning if it exceeds the threshold.
     *
     * Returns the result so callers can take additional action if needed.
     */
    public static function check(
        int   $productId,
        int   $unitId,
        float $price,
        float $packageSize = 1,
    ): PriceCheckResult {
        /** @var PriceChangeValidatorInterface $validator */
        $validator = app(PriceChangeValidatorInterface::class);

        $item = new PriceCheckItem(
            productId:   $productId,
            unitId:      $unitId,
            newPrice:    $price,
            packageSize: $packageSize,
        );

        $result = $validator->validate($item);

        if ($result->requiresWarning()) {
            self::sendWarning($result, $productId, $unitId);
        }

        return $result;
    }

    /**
     * Send a Filament warning notification with price change details.
     */
    private static function sendWarning(PriceCheckResult $result, int $productId, int $unitId): void
    {
        $productName = Product::find($productId)?->display_name ?? "Product #{$productId}";
        $unitName    = Unit::find($unitId)?->name ?? "Unit #{$unitId}";

        $direction  = $result->changePercent > 0 ? '📈' : '📉';
        $absPercent = abs($result->changePercent);

        Notification::make()
            ->title("⚠️ Price Change Alert — {$productName}")
            ->body(implode("\n", [
                "{$direction} Change: {$absPercent}% (max allowed: {$result->maxAllowedPercent}%)",
                "Unit: {$unitName}",
                "Last price (normalized): " . number_format($result->normalizedLastPrice, 2),
                "New price (normalized): " . number_format($result->normalizedNewPrice, 2),
            ]))
            ->warning()
            ->persistent()
            ->send();
    }
}
