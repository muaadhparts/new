<?php

namespace App\Domain\Commerce\DTOs;

/**
 * BranchCartDTO - Branch-scoped cart data
 *
 * Represents all cart data for a specific merchant branch.
 */
class BranchCartDTO
{
    public int $branchId;
    public string $branchName;
    public int $merchantId;
    public string $merchantName;

    /** @var CartItemDTO[] */
    public array $items = [];

    public CartTotalsDTO $totals;

    public bool $hasOtherBranches = false;
    public string $checkoutUrl = '';

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();

        $dto->branchId = (int) ($data['branch_id'] ?? 0);
        $dto->branchName = $data['branch_name'] ?? '';
        $dto->merchantId = (int) ($data['merchant_id'] ?? 0);
        $dto->merchantName = $data['merchant_name'] ?? '';
        $dto->hasOtherBranches = (bool) ($data['has_other_branches'] ?? false);
        $dto->checkoutUrl = $data['checkout_url'] ?? '';

        // Convert items to DTOs
        $dto->items = [];
        foreach ($data['items'] ?? [] as $key => $item) {
            $dto->items[$key] = CartItemDTO::fromArray($item);
        }

        // Convert totals to DTO
        $dto->totals = CartTotalsDTO::fromArray($data['totals'] ?? []);

        return $dto;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $items = [];
        foreach ($this->items as $key => $item) {
            $items[$key] = $item->toArray();
        }

        return [
            'branch_id' => $this->branchId,
            'branch_name' => $this->branchName,
            'merchant_id' => $this->merchantId,
            'merchant_name' => $this->merchantName,
            'items' => $items,
            'totals' => $this->totals->toArray(),
            'has_other_branches' => $this->hasOtherBranches,
            'checkout_url' => $this->checkoutUrl,
        ];
    }

    /**
     * Get item count
     */
    public function getItemCount(): int
    {
        return count($this->items);
    }

    /**
     * Check if branch cart is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get total quantity
     */
    public function getTotalQty(): int
    {
        return $this->totals->qty;
    }

    /**
     * Get total weight for shipping calculation
     */
    public function getTotalWeight(): float
    {
        $weight = 0.0;
        foreach ($this->items as $item) {
            $weight += $item->weight * $item->qty;
        }
        return $weight;
    }
}
