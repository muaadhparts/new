<?php

namespace Database\Factories;

use App\Domain\Merchant\Models\MerchantBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchantBranchFactory extends Factory
{
    protected $model = MerchantBranch::class;

    public function definition(): array
    {
        return [
            'user_id' => null, // Must be provided
            'warehouse_name' => $this->faker->company() . ' Warehouse',
            'warehouse_name_ar' => $this->faker->company(),
            'address' => $this->faker->address(),
            'city_id' => null,
            'phone' => $this->faker->phoneNumber(),
            'status' => 1,
        ];
    }
}
