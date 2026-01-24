<?php

namespace App\Domain\Merchant\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Branch Request
 *
 * Validates data for creating a new merchant branch.
 */
class StoreBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user && $user->role === 'merchant' && $user->status === 1;
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
            'name_ar' => [
                'nullable',
                'string',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'address' => [
                'required',
                'string',
                'max:500',
            ],
            'city_id' => [
                'required',
                'integer',
                'exists:cities,id',
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'working_hours' => [
                'nullable',
                'array',
            ],
            'working_hours.*.day' => [
                'required_with:working_hours',
                'string',
                Rule::in(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
            ],
            'working_hours.*.open' => [
                'required_with:working_hours',
                'date_format:H:i',
            ],
            'working_hours.*.close' => [
                'required_with:working_hours',
                'date_format:H:i',
                'after:working_hours.*.open',
            ],
            'is_main' => [
                'nullable',
                'boolean',
            ],
            'status' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.branch.name_required'),
            'phone.required' => __('validation.branch.phone_required'),
            'address.required' => __('validation.branch.address_required'),
            'city_id.required' => __('validation.branch.city_required'),
            'city_id.exists' => __('validation.branch.city_invalid'),
            'latitude.between' => __('validation.branch.latitude_invalid'),
            'longitude.between' => __('validation.branch.longitude_invalid'),
        ];
    }

    /**
     * Check if branch should be main
     */
    public function isMain(): bool
    {
        return (bool) $this->is_main;
    }

    /**
     * Get coordinates if provided
     */
    public function getCoordinates(): ?array
    {
        if ($this->filled(['latitude', 'longitude'])) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ];
        }

        return null;
    }
}
