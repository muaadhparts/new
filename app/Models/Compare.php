<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compare extends Model
{
    public $items = null;

    public function __construct($oldCompare = null)
    {
        if ($oldCompare) {
            $this->items = $oldCompare->items;
        }
    }

    /**
     * Add a merchant product to comparison
     * Uses merchant_product_id as the unique identifier
     */
    public function add($merchantProduct, $merchantProductId)
    {
        // Check if this merchant product is already in comparison
        if ($this->items && array_key_exists($merchantProductId, $this->items)) {
            // Mark as already exists and don't overwrite
            $this->items[$merchantProductId]['ck'] = 1;
            return;
        }

        // Add new item
        $this->items[$merchantProductId] = [
            'ck' => 0,
            'merchant_product' => $merchantProduct
        ];
    }

    /**
     * Legacy method for backward compatibility
     * Converts product_id to merchant_product_id
     */
    public function addLegacy($product, $productId)
    {
        // Find the first active merchant product for this product
        $merchantProduct = \App\Models\MerchantProduct::where('product_id', $productId)
            ->where('status', 1)
            ->orderBy('price')
            ->first();

        if ($merchantProduct) {
            $this->add($merchantProduct, $merchantProduct->id);
        }
    }

    public function removeItem($merchantProductId)
    {
        unset($this->items[$merchantProductId]);
    }

    /**
     * Get all items with their associated products and merchant data
     */
    public function getItemsWithProducts()
    {
        if (!$this->items) {
            return [];
        }

        $items = [];
        foreach ($this->items as $merchantProductId => $itemData) {
            if (isset($itemData['merchant_product'])) {
                $merchantProduct = $itemData['merchant_product'];
                $items[$merchantProductId] = [
                    'merchant_product' => $merchantProduct,
                    'product' => $merchantProduct->product ?? null,
                    'ck' => $itemData['ck'] ?? 0
                ];
            }
        }

        return $items;
    }
}
