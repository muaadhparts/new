<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * MerchantCartButton Component
 *
 * DATA FLOW POLICY: All processing in component class, view is display only.
 */
class MerchantCartButton extends Component
{
    // Core identifiers
    public int $mpId;
    public int $merchantUserId;
    public int $catalogItemId;

    // Pricing
    public float $price;
    public float $previousPrice;

    // Stock & Availability
    public int $stock;
    public bool $preordered;
    public bool $inStock;
    public int $minQty;
    public int $maxQty;

    // Sizes
    public array $sizes;
    public array $sizeQtys;
    public array $sizePrices;
    public bool $hasSizes;
    public array $sizeData; // Pre-computed for view loop

    // Colors
    public array $colors;
    public array $colorPrices;
    public bool $hasColors;
    public array $colorData; // Pre-computed for view loop

    // Dimensions
    public float $weight;

    // Display
    public string $catalogItemDisplayName;
    public string $uniqueId;
    public string $defaultSize;
    public string $defaultColor;
    public int $defaultQty;

    // Props
    public string $mode;
    public bool $showQty;
    public string $class;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $mp,
        string $mode = 'full',
        bool $showQty = true,
        string $class = ''
    ) {
        // STRICT: MerchantItem is REQUIRED
        if (!$mp) {
            throw new \LogicException('cart-button component requires $mp (MerchantItem) to be provided');
        }

        // Props
        $this->mode = $mode;
        $this->showQty = $showQty;
        $this->class = $class;

        // Core identifiers
        $this->mpId = $mp->id;
        $this->merchantUserId = $mp->user_id;
        $this->catalogItemId = $mp->catalog_item_id;

        // Pricing
        $this->price = (float) $mp->price;
        $this->previousPrice = (float) ($mp->previous_price ?? 0);

        // Stock & Availability
        $this->stock = (int) ($mp->stock ?? 0);
        $this->preordered = (bool) ($mp->preordered ?? false);
        $this->inStock = $this->stock > 0 || $this->preordered;

        // Quantity constraints
        $this->minQty = max(1, (int) ($mp->minimum_qty ?? 1));
        $this->maxQty = $this->preordered ? 9999 : max($this->minQty, $this->stock);

        // Parse sizes
        $this->parseSizes($mp);

        // Parse colors
        $this->parseColors($mp);

        // Dimensions
        $this->weight = (float) ($mp->weight ?? 0);

        // CatalogItem info (for display only)
        $catalogItem = $mp->catalogItem;
        $this->catalogItemDisplayName = $catalogItem 
            ? app(\App\Domain\Catalog\Services\CatalogItemDisplayService::class)->getDisplayName($catalogItem)
            : '';

        // Unique ID for this instance
        $this->uniqueId = 'cart_' . $this->mpId . '_' . uniqid();

        // Default selections
        $this->defaultQty = $this->minQty;
        $this->defaultSize = $this->hasSizes ? $this->sizes[0] : '';
        $this->defaultColor = $this->hasColors ? $this->colors[0] : '';

        // Find first available size with stock
        if ($this->hasSizes) {
            foreach ($this->sizes as $i => $sz) {
                if ($this->sizeQtys[$i] > 0) {
                    $this->defaultSize = $sz;
                    break;
                }
            }
        }

        // Pre-compute size data for view loop
        $this->sizeData = [];
        foreach ($this->sizes as $i => $sz) {
            $sizeStock = $this->sizeQtys[$i] ?? 0;
            $this->sizeData[] = [
                'size' => $sz,
                'stock' => $sizeStock,
                'price' => $this->sizePrices[$i] ?? 0,
                'available' => $sizeStock > 0 || $this->preordered,
                'isDefault' => ($sz === $this->defaultSize),
            ];
        }

        // Pre-compute color data for view loop
        $this->colorData = [];
        foreach ($this->colors as $i => $clr) {
            $this->colorData[] = [
                'color' => $clr,
                'price' => $this->colorPrices[$i] ?? 0,
                'isDefault' => ($clr === $this->defaultColor),
            ];
        }
    }

    /**
     * Parse sizes from MerchantItem
     */
    private function parseSizes($mp): void
    {
        $this->sizes = [];
        $this->sizeQtys = [];
        $this->sizePrices = [];

        if (!empty($mp->size)) {
            $sizesRaw = is_array($mp->size) ? $mp->size : array_map('trim', explode(',', $mp->size));
            $qtysRaw = !empty($mp->size_qty) ? (is_array($mp->size_qty) ? $mp->size_qty : array_map('trim', explode(',', $mp->size_qty))) : [];
            $pricesRaw = !empty($mp->size_price) ? (is_array($mp->size_price) ? $mp->size_price : array_map('trim', explode(',', $mp->size_price))) : [];

            foreach ($sizesRaw as $i => $sz) {
                if (trim($sz) !== '') {
                    $this->sizes[] = trim($sz);
                    $this->sizeQtys[] = (int) ($qtysRaw[$i] ?? 0);
                    $this->sizePrices[] = (float) ($pricesRaw[$i] ?? 0);
                }
            }
        }

        $this->hasSizes = count($this->sizes) > 0;
    }

    /**
     * Parse colors from MerchantItem
     */
    private function parseColors($mp): void
    {
        $this->colors = [];
        $this->colorPrices = [];

        if (!empty($mp->color_all)) {
            $colorsRaw = is_array($mp->color_all) ? $mp->color_all : array_map('trim', explode(',', $mp->color_all));
            $colorPricesRaw = !empty($mp->color_price) ? (is_array($mp->color_price) ? $mp->color_price : array_map('trim', explode(',', $mp->color_price))) : [];

            foreach ($colorsRaw as $i => $clr) {
                $clr = ltrim(trim($clr), '#');
                if ($clr !== '') {
                    $this->colors[] = $clr;
                    $this->colorPrices[] = (float) ($colorPricesRaw[$i] ?? 0);
                }
            }
        }

        $this->hasColors = count($this->colors) > 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.merchant-cart-button');
    }
}
