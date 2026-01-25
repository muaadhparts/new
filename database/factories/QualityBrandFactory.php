<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\QualityBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

class QualityBrandFactory extends Factory
{
    protected $model = QualityBrand::class;

    public function definition(): array
    {
        return [
            'name_en' => $this->faker->company(),
            'name_ar' => $this->faker->company(),
            'logo' => null,
        ];
    }
}
