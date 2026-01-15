<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
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
        'image' => url('/') . '/assets/images/announcements/'.$this->photo,
        'link' => $this->link,
        'name' => $this->name,
        'text' => $this->text
      ];
    }
}
