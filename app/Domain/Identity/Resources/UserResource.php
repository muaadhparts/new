<?php

namespace App\Domain\Identity\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource
 *
 * Transforms User model for API responses.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->shouldShowEmail($request), $this->email),
            'phone' => $this->when($this->shouldShowPhone($request), $this->phone),
            'avatar' => $this->avatar,
            'role' => $this->role,
            'is_verified' => (bool) $this->email_verified_at,
            'is_merchant' => $this->isMerchant(),
            'shop_name' => $this->when($this->isMerchant(), $this->shop_name),
            'shop_logo' => $this->when($this->isMerchant(), $this->shop_logo),
            'language' => $this->language ?? 'ar',
            'joined_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Check if email should be shown
     */
    protected function shouldShowEmail(Request $request): bool
    {
        // Show email to the user themselves or admins
        return $request->user()?->id === $this->id
            || $request->user()?->isOperator();
    }

    /**
     * Check if phone should be shown
     */
    protected function shouldShowPhone(Request $request): bool
    {
        return $request->user()?->id === $this->id
            || $request->user()?->isOperator();
    }

    /**
     * Check if user is merchant
     */
    protected function isMerchant(): bool
    {
        return $this->role === 'merchant';
    }
}
