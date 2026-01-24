<?php

namespace App\Domain\Platform\DTOs;

/**
 * MonetaryValueDTO - Represents a formatted monetary value
 *
 * Encapsulates both the raw amount and formatted representation.
 */
class MonetaryValueDTO
{
    public float $amount;
    public string $formatted;
    public string $code;
    public string $sign;

    /**
     * Create from amount using current monetary unit
     */
    public static function fromAmount(float $amount): self
    {
        $dto = new self();
        $service = monetaryUnit();

        $dto->amount = $service->convert($amount);
        $dto->formatted = $service->format($amount);
        $dto->code = $service->getCode();
        $dto->sign = $service->getSign();

        return $dto;
    }

    /**
     * Create from amount with specific currency
     */
    public static function fromAmountWithCurrency(float $amount, string $currencyCode): self
    {
        $dto = new self();
        $service = monetaryUnit();
        $unit = $service->getByCode($currencyCode);

        if ($unit) {
            $dto->amount = $amount;
            $dto->code = $unit->code;
            $dto->sign = $unit->sign;
            $dto->formatted = $dto->sign . number_format($amount, 2);
        } else {
            // Fallback to current unit
            $dto->amount = $service->convert($amount);
            $dto->formatted = $service->format($amount);
            $dto->code = $service->getCode();
            $dto->sign = $service->getSign();
        }

        return $dto;
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->amount = (float) ($data['amount'] ?? 0);
        $dto->formatted = $data['formatted'] ?? '';
        $dto->code = $data['code'] ?? '';
        $dto->sign = $data['sign'] ?? '';
        return $dto;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'formatted' => $this->formatted,
            'code' => $this->code,
            'sign' => $this->sign,
        ];
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->formatted;
    }

    /**
     * Check if value is zero
     */
    public function isZero(): bool
    {
        return $this->amount <= 0;
    }

    /**
     * Check if value is positive
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }
}
