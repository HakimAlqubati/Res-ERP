<?php

namespace App\DTOs\Inventory\StockInventory;

class StockInventoryDto
{
    public function __construct(
        public readonly string $inventory_date,
        public readonly int $store_id,
        public readonly int $responsible_user_id,
        public readonly bool $finalized = false,
        public readonly array $details = [],
        public readonly ?int $id = null,
    ) {}

    /**
     * Create from request data
     *
     * @param array $data
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            inventory_date: $data['inventory_date'],
            store_id: $data['store_id'],
            responsible_user_id: $data['responsible_user_id'],
            finalized: $data['finalized'] ?? false,
            details: $data['details'] ?? [],
            id: $data['id'] ?? null,
        );
    }

    /**
     * Convert to array for model creation/update
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'inventory_date' => $this->inventory_date,
            'store_id' => $this->store_id,
            'responsible_user_id' => $this->responsible_user_id,
            'finalized' => $this->finalized,
        ];
    }

    /**
     * Get details array
     *
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Check if has details
     *
     * @return bool
     */
    public function hasDetails(): bool
    {
        return !empty($this->details);
    }
}
