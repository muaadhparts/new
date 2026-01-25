<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\SkuAlternative;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkuAlternativeFactory extends Factory
{
    protected $model = SkuAlternative::class;

    public function definition(): array
    {
        return [
            'part_number' => strtoupper($this->faker->unique()->bothify('???####')),
            'group_id' => $this->faker->numberBetween(1, 1000),
        ];
    }
}
