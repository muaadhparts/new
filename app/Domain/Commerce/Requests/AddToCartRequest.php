<?php

namespace App\Domain\Commerce\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add To Cart Request
 *
 * Validates data for adding items to cart.
 */
class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Cart is available to all users
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'merchant_item_id' => [
                'required',
                'integer',
                Rule::exists('merchant_items', 'id')->where('status', 1),
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('merchant_branches', 'id')->where('status', 1),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'merchant_item_id.required' => __('validation.cart.item_required'),
            'merchant_item_id.exists' => __('validation.cart.item_not_available'),
            'quantity.required' => __('validation.cart.quantity_required'),
            'quantity.min' => __('validation.cart.quantity_min'),
            'quantity.max' => __('validation.cart.quantity_max'),
            'branch_id.exists' => __('validation.cart.branch_not_available'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'merchant_item_id' => __('attributes.item'),
            'quantity' => __('attributes.quantity'),
            'branch_id' => __('attributes.branch'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => (int) ($this->quantity ?? 1),
        ]);
    }
}
