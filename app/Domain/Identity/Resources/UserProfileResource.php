<?php

namespace App\Domain\Identity\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Profile Resource
 *
 * Transforms User model for profile API responses (full details).
 */
class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'language' => $this->language ?? 'ar',
            'is_verified' => (bool) $this->email_verified_at,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
            'referral_code' => $this->referral_code,
            'notifications_enabled' => (bool) $this->notifications_enabled,
            'marketing_enabled' => (bool) $this->marketing_enabled,
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'orders_count' => $this->when($this->purchases_count !== null, $this->purchases_count),
            'favorites_count' => $this->when($this->favorites_count !== null, $this->favorites_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
