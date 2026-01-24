<?php

namespace App\Domain\Identity\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Operator Role Resource
 *
 * Transforms OperatorRole model for API responses.
 */
class OperatorRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'is_super_admin' => (bool) $this->is_super_admin,
            'operators_count' => $this->when($this->operators_count !== null, $this->operators_count),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
