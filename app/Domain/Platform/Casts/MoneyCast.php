<?php

namespace App\Domain\Platform\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Platform\ValueObjects\Money;

/**
 * Money Cast
 *
 * Casts monetary values to Money value object.
 */
class MoneyCast implements CastsAttributes
{
    /**
     * Currency code
     */
    protected ?string $currency;

    /**
     * Create a new cast instance.
     */
    public function __construct(?string $currency = null)
    {
        $this->currency = $currency;
    }

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        $currency = $this->currency ?? monetaryUnit()->getCurrent()->code ?? 'SAR';

        return new Money((float) $value, $currency);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->getAmount();
        }

        return (float) $value;
    }
}
