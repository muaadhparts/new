<?php

namespace App\Domain\Catalog\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Published Scope
 *
 * Global scope to filter only published/visible catalog items.
 */
class PublishedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getTable() . '.status', 1)
            ->whereHas('merchantItems', function ($query) {
                $query->where('status', 1)
                    ->where('stock', '>', 0);
            });
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withUnpublished', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('onlyUnpublished', function (Builder $builder) {
            return $builder->withoutGlobalScope($this)
                ->where(function ($query) {
                    $query->where('status', 0)
                        ->orWhereDoesntHave('merchantItems', function ($q) {
                            $q->where('status', 1)->where('stock', '>', 0);
                        });
                });
        });
    }
}
