<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\DataTransferObjects;

final readonly class PurchaseReturnItemDTO
{
    public function __construct(
        public int $productId,
        public int $unitId,
        public float $quantity,
        public float $unitPrice,
        public ?int $purchaseInvoiceDetailId = null,
        public float $packageSize = 1.0,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            unitId: (int) $data['unit_id'],
            quantity: (float) $data['quantity'],
            unitPrice: (float) ($data['unit_price'] ?? $data['price'] ?? 0.0),
            purchaseInvoiceDetailId: isset($data['purchase_invoice_detail_id']) ? (int) $data['purchase_invoice_detail_id'] : null,
            packageSize: (float) ($data['package_size'] ?? 1.0),
            notes: $data['notes'] ?? null,
        );
    }

    public function getTotalPrice(): float
    {
        return round($this->quantity * $this->unitPrice, 4);
    }
}
