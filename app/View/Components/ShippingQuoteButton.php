<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * ShippingQuoteButton Component
 *
 * DATA FLOW POLICY: All processing in component class, view is display only.
 */
class ShippingQuoteButton extends Component
{
    public ?int $merchantId;
    public ?int $branchId;
    public ?float $weight;
    public string $itemName;
    public string $class;
    public bool $canRender;

    /**
     * Create a new component instance.
     *
     * @param mixed $mp MerchantItem model (optional)
     * @param int|null $merchantId Explicit merchant ID
     * @param int|null $branchId Explicit branch ID
     * @param float|null $weight Explicit weight
     * @param string $itemName Item name for display
     * @param string $class Additional CSS classes
     */
    public function __construct(
        $mp = null,
        ?int $merchantId = null,
        ?int $branchId = null,
        ?float $weight = null,
        string $itemName = '',
        string $class = ''
    ) {
        // Resolve merchant ID from explicit value or MerchantItem
        $this->merchantId = $merchantId ?? ($mp->user_id ?? null);

        // Resolve branch ID from explicit value or MerchantItem
        $this->branchId = $branchId ?? ($mp->merchant_branch_id ?? null);

        // Resolve weight from explicit value or MerchantItem's catalogItem
        $this->weight = $weight ?? ($mp && $mp->relationLoaded('catalogItem') ? $mp->catalogItem?->weight : null);

        // Resolve item name from explicit value or MerchantItem's catalogItem
        $this->itemName = $itemName ?: ($mp && $mp->relationLoaded('catalogItem') ? getLocalizedCatalogItemName($mp->catalogItem) : '');

        $this->class = $class;

        // Determine if component can be rendered
        $this->canRender = $this->merchantId && $this->branchId && $this->weight && $this->weight > 0;
    }

    /**
     * Determine if the component should be rendered.
     */
    public function shouldRender(): bool
    {
        return $this->canRender;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.shipping-quote-button');
    }
}
