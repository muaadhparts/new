<?php

namespace App\Domain\Merchant\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Working Hours Cast
 *
 * Handles merchant/branch working hours structure.
 */
class WorkingHoursCast implements CastsAttributes
{
    /**
     * Days of week
     */
    protected array $days = [
        'sunday', 'monday', 'tuesday', 'wednesday',
        'thursday', 'friday', 'saturday'
    ];

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return $this->getDefaultHours();
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($data)) {
            return $this->getDefaultHours();
        }

        // Normalize structure
        $hours = [];
        foreach ($this->days as $day) {
            $hours[$day] = [
                'open' => $data[$day]['open'] ?? '09:00',
                'close' => $data[$day]['close'] ?? '21:00',
                'is_closed' => (bool) ($data[$day]['is_closed'] ?? false),
            ];
        }

        return $hours;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get default working hours.
     */
    protected function getDefaultHours(): array
    {
        $hours = [];
        foreach ($this->days as $day) {
            $hours[$day] = [
                'open' => '09:00',
                'close' => '21:00',
                'is_closed' => $day === 'friday',
            ];
        }
        return $hours;
    }

    /**
     * Check if open at given time.
     */
    public static function isOpenAt(array $hours, string $day, string $time): bool
    {
        $dayHours = $hours[$day] ?? null;

        if (!$dayHours || ($dayHours['is_closed'] ?? false)) {
            return false;
        }

        return $time >= $dayHours['open'] && $time <= $dayHours['close'];
    }
}
