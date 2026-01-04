<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecResource extends JsonResource
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
            'specable_id' => $this->specable_id,
            'specable_type' => $this->specable_type,
            'name' => $this->name,
            'input_name' => $this->input_name,
            'spec_values' => route('spec.options', $this->id),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
