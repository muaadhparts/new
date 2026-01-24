<?php

namespace App\Domain\Identity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Register Request
 *
 * Validates data for user registration.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !auth()->check(); // Only guests can register
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,phone',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
            'role' => [
                'nullable',
                'string',
                'in:user,merchant',
            ],
            'terms' => [
                'required',
                'accepted',
            ],
            'referral_code' => [
                'nullable',
                'string',
                'exists:users,referral_code',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.register.name_required'),
            'email.required' => __('validation.register.email_required'),
            'email.unique' => __('validation.register.email_taken'),
            'phone.unique' => __('validation.register.phone_taken'),
            'password.required' => __('validation.register.password_required'),
            'password.confirmed' => __('validation.register.password_confirmation'),
            'terms.required' => __('validation.register.terms_required'),
            'terms.accepted' => __('validation.register.terms_accepted'),
        ];
    }

    /**
     * Get the registration role
     */
    public function getRole(): string
    {
        return $this->role ?? 'user';
    }

    /**
     * Check if registering as merchant
     */
    public function isMerchantRegistration(): bool
    {
        return $this->getRole() === 'merchant';
    }

    /**
     * Get referrer ID if referral code provided
     */
    public function getReferrerId(): ?int
    {
        if (!$this->referral_code) {
            return null;
        }

        return \App\Models\User::where('referral_code', $this->referral_code)
            ->value('id');
    }
}
