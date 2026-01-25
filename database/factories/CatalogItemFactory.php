<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    public function definition(): array
    {
        $partNumber = strtoupper($this->faker->unique()->bothify('???####'));

        return [
            'part_number' => $partNumber,
            'label_en' => $this->faker->words(3, true),
            'label_ar' => $this->faker->words(2, true),
            'slug' => Str::slug($partNumber),
            'photo' => null,
            'thumbnail' => null,
            'weight' => $this->faker->randomFloat(2, 0.1, 10),
            'views' => 0,
        ];
    }
}
