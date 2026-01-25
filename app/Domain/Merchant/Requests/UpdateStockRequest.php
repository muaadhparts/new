<?php

namespace App\Domain\Merchant\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * Update Stock Request
 *
 * Validates data for updating merchant item stock.
 */
class UpdateStockRequest extends FormRequest
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

        // Check if merchant owns the item
        $itemId = $this->route('item') ?? $this->merchant_item_id;

        if ($itemId) {
            return MerchantItem::where('id', $itemId)
                ->where('user_id', $user->id)
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
            'stock' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
            'adjustment_type' => [
                'nullable',
                'string',
                Rule::in(['set', 'add', 'subtract']),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'stock.required' => __('validation.stock.required'),
            'stock.integer' => __('validation.stock.integer'),
            'stock.min' => __('validation.stock.min'),
            'stock.max' => __('validation.stock.max'),
        ];
    }

    /**
     * Get the adjustment type
     */
    public function getAdjustmentType(): string
    {
        return $this->adjustment_type ?? 'set';
    }

    /**
     * Get the reason for stock change
     */
    public function getReason(): string
    {
        return $this->reason ?? 'manual_update';
    }
}
