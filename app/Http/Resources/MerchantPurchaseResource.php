<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MerchantPurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = auth()->user();
        return [
            'id' => $this->id,
            'number' => $this->purchase_number,
            'total_qty' => $this->merchantPurchases()->where('user_id','=',$user->id)->sum('qty'),
            'pay_amount' => $this->currency_sign . "" . round($this->merchantPurchases()->where('user_id','=',$user->id)->sum('price') * $this->currency_value , 2),
            'status' => $this->status,
            'details' => route('vendor-purchase-show', $this->purchase_number),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
          ];
    }
}
