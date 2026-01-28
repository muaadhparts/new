<?php

namespace App\Domain\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Search Request
 *
 * Validates catalog search parameters.
 */
class SearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Search is public
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'q' => [
                'nullable',
                'string',
                'max:255',
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],
            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'max_price' => [
                'nullable',
                'numeric',
                'gt:min_price',
            ],
            'sort' => [
                'nullable',
                'string',
                'in:relevance,price_asc,price_desc,newest,rating,popular',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'in_stock' => [
                'nullable',
                'boolean',
            ],
            'merchant_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    /**
     * Get search query
     */
    public function getSearchQuery(): ?string
    {
        $query = $this->q;
        return $query ? trim($query) : null;
    }

    /**
     * Get sort option with default
     */
    public function getSortOption(): string
    {
        return $this->sort ?? 'relevance';
    }

    /**
     * Get per page with default
     */
    public function getPerPage(): int
    {
        return (int) ($this->per_page ?? 24);
    }

    /**
     * Check if filtering by stock
     */
    public function inStockOnly(): bool
    {
        return (bool) $this->in_stock;
    }

    /**
     * Get price range
     */
    public function getPriceRange(): array
    {
        return [
            'min' => $this->min_price ? (float) $this->min_price : null,
            'max' => $this->max_price ? (float) $this->max_price : null,
        ];
    }
}
