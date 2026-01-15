<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HeroCarouselResource extends JsonResource
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
        'subname' => $this->subname_text,
        'name' => $this->name_text,
        'small_text' => $this->details_text,
        'image' => url('/') . '/assets/images/sliders/'.$this->photo,
        'redirect_url' => $this->link,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
      ];
    }
}
