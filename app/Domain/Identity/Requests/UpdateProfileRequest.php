<?php

namespace App\Domain\Identity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Profile Request
 *
 * Validates data for updating user profile.
 */
class UpdateProfileRequest extends FormRequest
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
        $userId = auth()->id();

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
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'avatar' => [
                'nullable',
                'image',
                'max:2048',
                'mimes:jpeg,png,jpg,webp',
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],
            'gender' => [
                'nullable',
                'string',
                'in:male,female,other',
            ],
            'language' => [
                'nullable',
                'string',
                'in:ar,en',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.profile.name_required'),
            'email.required' => __('validation.profile.email_required'),
            'email.unique' => __('validation.profile.email_taken'),
            'phone.unique' => __('validation.profile.phone_taken'),
            'avatar.max' => __('validation.profile.avatar_size'),
        ];
    }

    /**
     * Check if avatar is being updated
     */
    public function hasNewAvatar(): bool
    {
        return $this->hasFile('avatar');
    }
}
