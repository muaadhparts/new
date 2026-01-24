<?php

namespace App\Domain\Shipping\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Address Request
 *
 * Validates data for creating/updating a shipping address.
 */
class StoreAddressRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/',
            ],
            'street' => [
                'required',
                'string',
                'max:500',
            ],
            'building' => [
                'nullable',
                'string',
                'max:100',
            ],
            'apartment' => [
                'nullable',
                'string',
                'max:50',
            ],
            'city' => [
                'required',
                'string',
                'max:100',
            ],
            'city_id' => [
                'nullable',
                'integer',
                'exists:cities,id',
            ],
            'district' => [
                'nullable',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'country' => [
                'nullable',
                'string',
                'max:100',
            ],
            'country_id' => [
                'nullable',
                'integer',
                'exists:countries,id',
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
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'label' => [
                'nullable',
                'string',
                'in:home,work,other',
            ],
            'is_default' => [
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
            'name.required' => __('validation.address.name_required'),
            'phone.required' => __('validation.address.phone_required'),
            'phone.regex' => __('validation.address.phone_invalid'),
            'street.required' => __('validation.address.street_required'),
            'city.required' => __('validation.address.city_required'),
        ];
    }

    /**
     * Get full address string
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->building,
            $this->apartment,
            $this->district,
            $this->city,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if has coordinates
     */
    public function hasCoordinates(): bool
    {
        return $this->filled(['latitude', 'longitude']);
    }

    /**
     * Check if should be default
     */
    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }
}
