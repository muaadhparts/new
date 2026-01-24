<?php

namespace App\Domain\Commerce\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Cart Item Request
 *
 * Validates data for updating cart item quantity.
 */
class UpdateCartItemRequest extends FormRequest
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
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quantity.required' => __('validation.cart.quantity_required'),
            'quantity.integer' => __('validation.cart.quantity_invalid'),
            'quantity.min' => __('validation.cart.quantity_min_zero'),
            'quantity.max' => __('validation.cart.quantity_max'),
        ];
    }

    /**
     * Check if quantity is zero (remove item)
     */
    public function isRemoval(): bool
    {
        return (int) $this->quantity === 0;
    }
}
