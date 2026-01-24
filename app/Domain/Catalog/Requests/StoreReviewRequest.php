<?php

namespace App\Domain\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Review Request
 *
 * Validates data for submitting a product review.
 */
class StoreReviewRequest extends FormRequest
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
        $catalogItemId = $this->route('catalog_item') ?? $this->catalog_item_id;

        return [
            'catalog_item_id' => [
                'required_without:catalog_item',
                'integer',
                Rule::exists('catalog_items', 'id')->where('status', 1),
                // Prevent duplicate reviews
                Rule::unique('catalog_reviews')
                    ->where('user_id', $userId)
                    ->where('catalog_item_id', $catalogItemId),
            ],
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'comment' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'pros' => [
                'nullable',
                'array',
                'max:5',
            ],
            'pros.*' => [
                'string',
                'max:100',
            ],
            'cons' => [
                'nullable',
                'array',
                'max:5',
            ],
            'cons.*' => [
                'string',
                'max:100',
            ],
            'images' => [
                'nullable',
                'array',
                'max:5',
            ],
            'images.*' => [
                'image',
                'max:2048', // 2MB
                'mimes:jpeg,png,jpg,webp',
            ],
            'recommend' => [
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
            'catalog_item_id.required_without' => __('validation.review.item_required'),
            'catalog_item_id.exists' => __('validation.review.item_not_found'),
            'catalog_item_id.unique' => __('validation.review.already_reviewed'),
            'rating.required' => __('validation.review.rating_required'),
            'rating.min' => __('validation.review.rating_min'),
            'rating.max' => __('validation.review.rating_max'),
            'comment.max' => __('validation.review.comment_max'),
            'images.max' => __('validation.review.images_max'),
            'images.*.max' => __('validation.review.image_size'),
        ];
    }

    /**
     * Check if review is positive
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if has images
     */
    public function hasImages(): bool
    {
        return $this->hasFile('images') && count($this->file('images')) > 0;
    }
}
