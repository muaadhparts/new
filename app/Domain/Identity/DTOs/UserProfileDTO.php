<?php

namespace App\Domain\Identity\DTOs;

/**
 * User Profile DTO
 * Contains user profile data for edit form display.
 * DATA_FLOW_POLICY: View receives this DTO only, no Models.
 */
final class UserProfileDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $fax,
        public readonly ?string $address,
        public readonly ?string $photo,
        public readonly string $photoUrl,
        public readonly int $isProvider,
    ) {}

    /**
     * Create from User model
     */
    public static function fromUser(\App\Domain\Identity\Models\User $user): self
    {
        // Build photo URL
        if ($user->photo) {
            if ($user->is_provider == 1) {
                $photoUrl = asset($user->photo);
            } else {
                $photoUrl = asset('assets/images/users/' . $user->photo);
            }
        } else {
            $gs = app('general_settings');
            $photoUrl = asset('assets/images/' . ($gs->user_image ?? 'noimage.png'));
        }

        return new self(
            userId: $user->id,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            fax: $user->fax,
            address: $user->address,
            photo: $user->photo,
            photoUrl: $photoUrl,
            isProvider: $user->is_provider ?? 0,
        );
    }
}
