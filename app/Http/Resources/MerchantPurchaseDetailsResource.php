<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MerchantPurchaseDetailsResource extends JsonResource
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
            'status' => $this->status,
            'shipping_name' => $this->customer_name,
            'shipping_email' => $this->customer_email,
            'shipping_phone' => $this->customer_phone,
            'shipping_address' => $this->customer_address,
            'shipping_zip' => $this->customer_zip,
            'shipping_city' => $this->customer_city,
            'shipping_country' => $this->customer_country,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_address' => $this->customer_address,
            'customer_zip' => $this->customer_zip,
            'customer_city' => $this->customer_city,
            'customer_country' => $this->customer_country,
            'shipping' => $this->shipping,
            'total_qty' => $this->merchantPurchases()->where('user_id','=',$user->id)->sum('qty'),
            'pay_amount' => $this->currency_sign . "" . round($this->merchantPurchases()->where('user_id','=',$user->id)->sum('price') * $this->currency_value , 2),
            'shipping_cost' => $this->when($this->vendor_shipping_id == $user->id, function() {
                return $this->shipping_cost;
            }),
            'packing_cost' => $this->when($this->vendor_packing_id == $user->id, function() {
                return $this->packing_cost;
            }),
            'packing_cost' => $this->packing_cost,
            'ordered_products' => $this->when(!empty($this->cart), function() use ($user) {
            $user = auth()->user();
              $cart = unserialize(bzdecompress(utf8_decode($this->cart)));
              $prods = $cart->items;
              foreach($prods as $key => $data){
                  if($data['item']['user_id'] != $user->id){
                      unset($prods[$key]);
                  }
              }
              return $prods;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
          ];
    }
}
