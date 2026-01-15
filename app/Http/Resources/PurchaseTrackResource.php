<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseTrackResource extends JsonResource
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
        'purchase_id' => $this->user_id,
        'name' => $this->name,
        'text' => $this->text,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
      ];
    }
}
