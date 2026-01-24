<?php

namespace App\Domain\Merchant\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Price Request
 *
 * Validates data for updating merchant item price.
 */
class UpdatePriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'merchant') {
            return false;
        }

        $itemId = $this->route('item') ?? $this->merchant_item_id;

        if ($itemId) {
            return \App\Models\MerchantItem::where('id', $itemId)
                ->where('merchant_id', $user->id)
                ->exists();
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999999.99',
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'discount_type' => [
                'nullable',
                'string',
                'in:percentage,fixed',
            ],
            'discount_start' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'discount_end' => [
                'nullable',
                'date',
                'after:discount_start',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.required' => __('validation.price.required'),
            'price.numeric' => __('validation.price.numeric'),
            'price.min' => __('validation.price.min'),
            'discount.max' => __('validation.discount.max'),
            'discount_end.after' => __('validation.discount.end_after_start'),
        ];
    }

    /**
     * Check if discount is being applied
     */
    public function hasDiscount(): bool
    {
        return $this->filled('discount') && $this->discount > 0;
    }

    /**
     * Get calculated discounted price
     */
    public function getDiscountedPrice(): float
    {
        if (!$this->hasDiscount()) {
            return (float) $this->price;
        }

        if ($this->discount_type === 'fixed') {
            return max(0, $this->price - $this->discount);
        }

        return $this->price * (1 - $this->discount / 100);
    }
}
