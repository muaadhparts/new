<?php

namespace App\Domain\Commerce\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout Request
 *
 * Validates data for completing checkout.
 */
class CheckoutRequest extends FormRequest
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
            // Shipping Address
            'shipping_address' => ['required', 'array'],
            'shipping_address.name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:20'],
            'shipping_address.street' => ['required', 'string', 'max:500'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_address.notes' => ['nullable', 'string', 'max:1000'],

            // Payment
            'payment_method' => [
                'required',
                'string',
                Rule::in(['cod', 'credit_card', 'bank_transfer', 'wallet']),
            ],

            // Shipping
            'shipping_method_id' => [
                'nullable',
                'integer',
                'exists:shippings,id',
            ],

            // Optional
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'save_address' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shipping_address.required' => __('validation.checkout.address_required'),
            'shipping_address.name.required' => __('validation.checkout.name_required'),
            'shipping_address.phone.required' => __('validation.checkout.phone_required'),
            'shipping_address.street.required' => __('validation.checkout.street_required'),
            'shipping_address.city.required' => __('validation.checkout.city_required'),
            'payment_method.required' => __('validation.checkout.payment_required'),
            'payment_method.in' => __('validation.checkout.payment_invalid'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'shipping_address.name' => __('attributes.recipient_name'),
            'shipping_address.phone' => __('attributes.phone'),
            'shipping_address.street' => __('attributes.street'),
            'shipping_address.city' => __('attributes.city'),
            'payment_method' => __('attributes.payment_method'),
        ];
    }

    /**
     * Get shipping address as array
     */
    public function getShippingAddress(): array
    {
        return $this->validated()['shipping_address'];
    }

    /**
     * Check if address should be saved
     */
    public function shouldSaveAddress(): bool
    {
        return (bool) $this->save_address;
    }
}
