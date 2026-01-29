<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

/**
 * UserProfileService - Handle user profile operations
 *
 * Centralized service for profile updates, password resets, and file uploads.
 */
class UserProfileService
{
    /**
     * Update user profile with optional photo upload
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $photo = null): User
    {
        if ($photo) {
            $data['photo'] = $this->handlePhotoUpload($user, $photo);
        }

        $user->update($data);
        
        return $user->fresh();
    }

    /**
     * Handle photo upload and deletion of old photo
     */
    private function handlePhotoUpload(User $user, UploadedFile $photo): string
    {
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'svg'];
        
        if (!in_array($photo->getClientOriginalExtension(), $allowedExtensions)) {
            throw new \InvalidArgumentException(__('The image must be a file of type: jpeg, jpg, png, svg.'));
        }

        // Generate unique filename
        $filename = \PriceHelper::ImageCreateName($photo);
        
        // Move to public directory
        $photo->move(public_path('assets/images/users/'), $filename);
        
        // Delete old photo if exists
        if ($user->photo && file_exists(public_path('assets/images/users/' . $user->photo))) {
            unlink(public_path('assets/images/users/' . $user->photo));
        }

        return $filename;
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user, string $currentPassword, string $newPassword, string $confirmPassword): bool
    {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            throw new \InvalidArgumentException(__('Current password Does not match.'));
        }

        // Verify password confirmation
        if ($newPassword !== $confirmPassword) {
            throw new \InvalidArgumentException(__('Confirm password does not match.'));
        }

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }

    /**
     * Submit merchant application
     */
    public function submitMerchantApplication(User $user, array $applicationData): User
    {
        // Check if already a merchant
        if ($user->is_merchant >= 1) {
            throw new \LogicException(__('User is already a merchant.'));
        }

        // Update user data
        $user->update([
            'shop_name' => $applicationData['shop_name'],
            'shop_number' => $applicationData['shop_number'] ?? null,
            'shop_address' => $applicationData['shop_address'],
            'shop_message' => $applicationData['shop_message'] ?? null,
            'is_merchant' => 1, // Under verification
        ]);

        return $user->fresh();
    }

    /**
     * Validate photo file
     */
    public function validatePhoto(?UploadedFile $photo): bool
    {
        if (!$photo) {
            return true;
        }

        $allowedExtensions = ['jpeg', 'jpg', 'png', 'svg'];
        return in_array($photo->getClientOriginalExtension(), $allowedExtensions);
    }
}
