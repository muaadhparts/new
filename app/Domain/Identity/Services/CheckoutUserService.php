<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * CheckoutUserService
 *
 * Handles guest user registration during checkout process.
 * Creates new user account and logs them in automatically.
 */
class CheckoutUserService
{
    /**
     * Register a guest user during checkout
     *
     * @param array $data Must contain: personal_name, personal_email, personal_pass, personal_confirm
     * @return array ['success' => bool, 'user' => User|null, 'error' => string|null]
     * @throws \InvalidArgumentException If required fields are missing
     */
    public function registerGuestUser(array $data): array
    {
        $this->validateCheckoutData($data);

        try {
            // Check if email already exists
            if (User::where('email', $data['personal_email'])->exists()) {
                return [
                    'success' => false,
                    'user' => null,
                    'error' => __("This Email Already Exist."),
                ];
            }

            // Validate password confirmation
            if ($data['personal_pass'] !== $data['personal_confirm']) {
                return [
                    'success' => false,
                    'user' => null,
                    'error' => __("Confirm Password Doesn't Match."),
                ];
            }

            // Create user
            $user = $this->createUser($data);

            // Auto login
            Auth::login($user);

            Log::info('Guest user registered during checkout', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'user' => $user,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to register guest user during checkout', [
                'email' => $data['personal_email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'user' => null,
                'error' => __("Registration failed. Please try again."),
            ];
        }
    }

    /**
     * Validate required checkout data
     */
    private function validateCheckoutData(array $data): void
    {
        $required = ['personal_name', 'personal_email', 'personal_pass', 'personal_confirm'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!filter_var($data['personal_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format");
        }
    }

    /**
     * Create new user account
     */
    private function createUser(array $data): User
    {
        $user = new User();
        $user->name = $data['personal_name'];
        $user->email = $data['personal_email'];
        $user->password = Hash::make($data['personal_pass']);
        $user->verification_link = md5(time() . $data['personal_name'] . $data['personal_email']);
        $user->affilate_code = md5($data['personal_name'] . $data['personal_email']);
        $user->email_verified = 'Yes';
        $user->save();

        return $user;
    }

    /**
     * Check if user is authenticated for checkout
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Get current authenticated user
     *
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        return Auth::user();
    }
}
