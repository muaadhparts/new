<?php

namespace App\Domain\Shipping\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Calculate Shipping Request
 *
 * Validates data for calculating shipping costs.
 */
class CalculateShippingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'city_id' => [
                'required',
                'integer',
                'exists:cities,id',
            ],
            'merchant_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'merchant_ids.*' => [
                'integer',
                'exists:users,id',
            ],
            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000',
            ],
            'subtotal' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'city_id.required' => __('validation.shipping.city_required'),
            'city_id.exists' => __('validation.shipping.city_invalid'),
            'merchant_ids.required' => __('validation.shipping.merchants_required'),
        ];
    }

    /**
     * Get the destination city ID
     */
    public function getCityId(): int
    {
        return (int) $this->city_id;
    }

    /**
     * Get merchant IDs
     */
    public function getMerchantIds(): array
    {
        return $this->merchant_ids;
    }

    /**
     * Get total weight if provided
     */
    public function getWeight(): ?float
    {
        return $this->weight ? (float) $this->weight : null;
    }
}
