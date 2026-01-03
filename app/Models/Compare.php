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
     * Add a merchant catalogItem to comparison
     * Uses merchant_item_id as the unique identifier
     */
    public function add($merchantItem, $merchantProductId)
    {
        // Check if this merchant catalogItem is already in comparison
        if ($this->items && array_key_exists($merchantProductId, $this->items)) {
            // Mark as already exists and don't overwrite
            $this->items[$merchantProductId]['ck'] = 1;
            return;
        }

        // Add new item
        $this->items[$merchantProductId] = [
            'ck' => 0,
            'merchant_product' => $merchantItem
        ];
    }

    /**
     * Legacy method for backward compatibility
     * Converts catalog_item_id to merchant_item_id
     */
    public function addLegacy($catalogItem, $catalogItemId)
    {
        // Find the first active merchant item for this catalog item
        $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->orderBy('price')
            ->first();

        if ($merchantItem) {
            $this->add($merchantItem, $merchantItem->id);
        }
    }

    public function removeItem($merchantProductId)
    {
        unset($this->items[$merchantProductId]);
    }

    /**
     * Get all items with their associated catalogItems and merchant data
     */
    public function getItemsWithCatalogItems()
    {
        if (!$this->items) {
            return [];
        }

        $items = [];
        foreach ($this->items as $merchantProductId => $itemData) {
            if (isset($itemData['merchant_product'])) {
                $merchantItem = $itemData['merchant_product'];
                $catalogItem = $merchantItem->catalogItem ?? null;

                // Return in the format expected by the view (with 'item' key)
                $items[$merchantProductId] = [
                    'item' => $catalogItem, // The actual CatalogItem model
                    'merchant_product' => $merchantItem,
                    'ck' => $itemData['ck'] ?? 0
                ];
            }
        }

        return $items;
    }
}
