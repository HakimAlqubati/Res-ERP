<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\DataTransferObjects;

final readonly class CreatePurchaseReturnDTO
{
    /** @var array<int, PurchaseReturnItemDTO> */
    public array $items;

    public function __construct(
        public ?int $purchaseInvoiceId,
        public int $supplierId,
        public int $storeId,
        public string $returnDate,
        public int $userId,
        array $items,
        public ?string $reason = null,
        public ?string $notes = null,
        public ?string $attachment = null,
        public ?int $paymentMethodId = null,
        public ?int $returnId = null,
    ) {
        $this->items = array_map(
            fn($item) => $item instanceof PurchaseReturnItemDTO ? $item : PurchaseReturnItemDTO::fromArray((array) $item),
            $items
        );
    }

    public static function fromRequest(array $data, int $userId, ?int $returnId = null): self
    {
        $attachment = $data['attachment'] ?? null;
        if (is_array($attachment)) {
            $attachment = ! empty($attachment) ? (string) array_values($attachment)[0] : null;
        } elseif (is_string($attachment) && trim($attachment) === '') {
            $attachment = null;
        }

        return new self(
            purchaseInvoiceId: isset($data['purchase_invoice_id']) && ! empty($data['purchase_invoice_id']) ? (int) $data['purchase_invoice_id'] : null,
            supplierId: (int) $data['supplier_id'],
            storeId: (int) $data['store_id'],
            returnDate: (string) ($data['return_date'] ?? date('Y-m-d')),
            userId: $userId,
            items: $data['items'] ?? $data['units'] ?? $data['details'] ?? [],
            reason: $data['reason'] ?? null,
            notes: $data['notes'] ?? null,
            attachment: $attachment,
            paymentMethodId: isset($data['payment_method_id']) && ! empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            returnId: $returnId,
        );
    }
}
