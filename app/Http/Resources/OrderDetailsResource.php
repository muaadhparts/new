<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
class OrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        
      return [
        'id' => $this->id,
        'number' => $this->order_number,
        'total' => $this->currency_sign . "" . round($this->pay_amount * $this->currency_value , 2),
        'status' => $this->status,
        'payment_status' => $this->payment_status,
        'method' => $this->method,
        'payment_url' => $this->payment_status == 'Pending' ? route('payment.checkout') . '?order_number=' . $this->order_number : null,
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
        'paid_amount' => $this->currency_sign . '' . round($this->pay_amount * $this->currency_value , 2),
        'payment_method' => $this->method,
        'shipping_cost' => $this->shipping_cost,
        'packing_cost' => $this->packing_cost,
        'charge_id' => $this->charge_id,
        'transaction_id' => $this->txnid,
        'ordered_products' => $this->when(!empty($this->cart), function() {
          $cart = json_decode($this->cart,true);
          foreach($cart['items'] as $key=> $item){
              $item['item']['photo'] = \Illuminate\Support\Facades\Storage::url($item['item']['photo']) ?? asset('assets/images/noimage.png');
              $new[$key.Str::random(3)]['item'] = $item;
          }
          return $new;
        }),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
      ];
    }
}
