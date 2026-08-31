<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\DataTransferObjects;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Support\Collection;

final class PurchaseReturnPipelineContext
{
    /** @var Collection<int, PurchaseReturnItemDTO> */
    public Collection $items;
    public ?PurchaseInvoice $purchaseInvoice = null;
    public ?Supplier $supplier = null;
    public ?Store $store = null;
    public ?PurchaseReturn $purchaseReturn = null;

    public function __construct(
        public readonly ?int $purchaseInvoiceId,
        public readonly int $supplierId,
        public readonly int $storeId,
        public readonly string $returnDate,
        public readonly int $userId,
        array $items,
        public readonly ?string $reason = null,
        public readonly ?string $notes = null,
        public readonly ?string $attachment = null,
        public readonly ?int $paymentMethodId = null,
        ?PurchaseReturn $existingReturn = null,
    ) {
        $this->items = collect($items)->map(
            fn($item) => $item instanceof PurchaseReturnItemDTO ? $item : PurchaseReturnItemDTO::fromArray((array) $item)
        );
        $this->purchaseReturn = $existingReturn;
    }

    public function calculateTotalAmount(): float
    {
        return (float) $this->items->sum(fn(PurchaseReturnItemDTO $item) => $item->getTotalPrice());
    }
}
