<?php

namespace App\Domain\Identity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Change Password Request
 *
 * Validates data for changing user password.
 */
class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, auth()->user()->password)) {
                        $fail(__('validation.password.current_incorrect'));
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => __('validation.password.current_required'),
            'password.required' => __('validation.password.new_required'),
            'password.confirmed' => __('validation.password.confirmation_mismatch'),
            'password.min' => __('validation.password.min_length'),
        ];
    }

    /**
     * Get the new hashed password
     */
    public function getHashedPassword(): string
    {
        return Hash::make($this->password);
    }
}
