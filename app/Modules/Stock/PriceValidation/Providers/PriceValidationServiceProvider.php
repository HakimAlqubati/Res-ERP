<?php

namespace App\Modules\Stock\PriceValidation\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Stock\PriceValidation\Contracts\LastPurchasePriceRepositoryInterface;
use App\Modules\Stock\PriceValidation\Contracts\PriceChangeValidatorInterface;
use App\Modules\Stock\PriceValidation\Repositories\ChainedPriceRepository;
use App\Modules\Stock\PriceValidation\Repositories\PurchaseInvoicePriceRepository;
use App\Modules\Stock\PriceValidation\Repositories\GrnPriceRepository;
use App\Modules\Stock\PriceValidation\Services\PriceChangeValidator;

/**
 * Registers the PriceValidation module bindings.
 *
 * To switch the price source (e.g. from PurchaseInvoice to GRN only),
 * simply change the binding below. No other code changes required.
 */
class PriceValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository: chained lookup (PurchaseInvoice first, then GRN).
        $this->app->bind(
            LastPurchasePriceRepositoryInterface::class,
            function () {
                return new ChainedPriceRepository([
                    new PurchaseInvoicePriceRepository(),
                    new GrnPriceRepository(),
                ]);
            },
        );

        // Validator service.
        $this->app->bind(
            PriceChangeValidatorInterface::class,
            PriceChangeValidator::class,
        );
    }
}
