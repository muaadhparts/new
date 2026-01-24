<?php

namespace App\Domain\Merchant\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Merchant Setting Resource
 *
 * Transforms MerchantSetting model for API responses.
 */
class MerchantSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'auto_accept_orders' => (bool) $this->auto_accept_orders,
            'low_stock_threshold' => $this->low_stock_threshold,
            'notification_email' => $this->notification_email,
            'notification_phone' => $this->notification_phone,
            'accepts_cod' => (bool) $this->accepts_cod,
            'accepts_card' => (bool) $this->accepts_card,
            'min_order_amount' => $this->when(
                $this->min_order_amount !== null,
                fn() => monetaryUnit()->format($this->min_order_amount)
            ),
            'tax_enabled' => (bool) $this->tax_enabled,
            'tax_rate' => $this->tax_rate,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
