<?php

namespace App\Domain\Commerce\Queries;

use App\Models\Purchase;
use App\Models\MerchantPurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * PurchaseQuery - Query builder for purchases
 *
 * Provides fluent interface for building Purchase queries.
 */
class PurchaseQuery
{
    protected Builder $query;

    public function __construct()
    {
        $this->query = Purchase::query();
    }

    /**
     * Create new query instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Filter by customer (user)
     */
    public function forCustomer(int $userId): self
    {
        $this->query->where('user_id', $userId);
        return $this;
    }

    /**
     * Filter by status
     */
    public function withStatus(string $status): self
    {
        $this->query->where('status', $status);
        return $this;
    }

    /**
     * Filter by multiple statuses
     */
    public function withStatuses(array $statuses): self
    {
        $this->query->whereIn('status', $statuses);
        return $this;
    }

    /**
     * Filter pending purchases
     */
    public function pending(): self
    {
        return $this->withStatus('pending');
    }

    /**
     * Filter processing purchases
     */
    public function processing(): self
    {
        return $this->withStatus('processing');
    }

    /**
     * Filter completed purchases
     */
    public function completed(): self
    {
        return $this->withStatus('completed');
    }

    /**
     * Filter cancelled purchases
     */
    public function cancelled(): self
    {
        return $this->withStatus('cancelled');
    }

    /**
     * Filter by date range
     */
    public function betweenDates(string $startDate, string $endDate): self
    {
        $this->query->whereBetween('created_at', [$startDate, $endDate]);
        return $this;
    }

    /**
     * Filter by payment status
     */
    public function withPaymentStatus(string $status): self
    {
        $this->query->where('payment_status', $status);
        return $this;
    }

    /**
     * Filter paid purchases
     */
    public function paid(): self
    {
        return $this->withPaymentStatus('paid');
    }

    /**
     * Filter unpaid purchases
     */
    public function unpaid(): self
    {
        return $this->withPaymentStatus('unpaid');
    }

    /**
     * Filter by payment method
     */
    public function paymentMethod(string $method): self
    {
        $this->query->where('method', $method);
        return $this;
    }

    /**
     * Filter COD (Cash on Delivery) purchases
     */
    public function cod(): self
    {
        return $this->paymentMethod('Cash On Delivery');
    }

    /**
     * Filter by minimum total
     */
    public function minTotal(float $amount): self
    {
        $this->query->where('pay_amount', '>=', $amount);
        return $this;
    }

    /**
     * Filter by maximum total
     */
    public function maxTotal(float $amount): self
    {
        $this->query->where('pay_amount', '<=', $amount);
        return $this;
    }

    /**
     * Search by order number or customer info
     */
    public function search(string $term): self
    {
        $this->query->where(function ($q) use ($term) {
            $q->where('order_number', 'like', '%' . $term . '%')
                ->orWhere('customer_name', 'like', '%' . $term . '%')
                ->orWhere('customer_email', 'like', '%' . $term . '%')
                ->orWhere('customer_phone', 'like', '%' . $term . '%');
        });
        return $this;
    }

    /**
     * Order by newest first
     */
    public function latest(): self
    {
        $this->query->orderBy('created_at', 'desc');
        return $this;
    }

    /**
     * Order by oldest first
     */
    public function oldest(): self
    {
        $this->query->orderBy('created_at', 'asc');
        return $this;
    }

    /**
     * Order by amount descending
     */
    public function highestFirst(): self
    {
        $this->query->orderBy('pay_amount', 'desc');
        return $this;
    }

    /**
     * Eager load relations
     */
    public function withRelations(): self
    {
        $this->query->with(['user', 'merchantPurchases.merchant']);
        return $this;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15)
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all results
     */
    public function get(): Collection
    {
        return $this->query->get();
    }

    /**
     * Get first result
     */
    public function first(): ?Purchase
    {
        return $this->query->first();
    }

    /**
     * Get count
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Get sum of pay_amount
     */
    public function totalAmount(): float
    {
        return (float) $this->query->sum('pay_amount');
    }

    /**
     * Get the underlying query builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }
}
