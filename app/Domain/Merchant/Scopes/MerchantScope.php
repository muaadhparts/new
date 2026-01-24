<?php

namespace App\Domain\Merchant\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Merchant Scope
 *
 * Global scope to filter by merchant context.
 */
class MerchantScope implements Scope
{
    protected ?int $merchantId;

    public function __construct(?int $merchantId = null)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($this->merchantId) {
            $builder->where($model->getTable() . '.user_id', $this->merchantId);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('forAllMerchants', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forMerchant', function (Builder $builder, int $merchantId) {
            return $builder->withoutGlobalScope($this)
                ->where('user_id', $merchantId);
        });
    }
}
