<?php

namespace App\Domain\Merchant\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Merchant\Models\MerchantSetting;
use App\Domain\Identity\Models\User;

/**
 * Merchant Setting Factory
 *
 * Factory for creating MerchantSetting instances in tests.
 */
class MerchantSettingFactory extends Factory
{
    protected $model = MerchantSetting::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shop_name' => $this->faker->company(),
            'shop_name_ar' => 'متجر ' . $this->faker->numberBetween(1, 1000),
            'shop_email' => $this->faker->companyEmail(),
            'shop_phone' => '+966' . $this->faker->numberBetween(500000000, 599999999),
            'shop_description' => $this->faker->paragraph(),
            'shop_logo' => null,
            'shop_banner' => null,
            'min_order_amount' => 0,
            'free_shipping_threshold' => null,
            'shipping_policy' => $this->faker->paragraph(),
            'return_policy' => $this->faker->paragraph(),
            'auto_accept_orders' => true,
            'low_stock_threshold' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * For specific merchant.
     */
    public function forMerchant(User $merchant): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $merchant->id,
        ]);
    }

    /**
     * With minimum order amount.
     */
    public function withMinOrder(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'min_order_amount' => $amount,
        ]);
    }

    /**
     * With free shipping threshold.
     */
    public function withFreeShipping(float $threshold): static
    {
        return $this->state(fn (array $attributes) => [
            'free_shipping_threshold' => $threshold,
        ]);
    }

    /**
     * Auto accept orders enabled.
     */
    public function autoAccept(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_accept_orders' => true,
        ]);
    }

    /**
     * Manual order acceptance.
     */
    public function manualAccept(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_accept_orders' => false,
        ]);
    }

    /**
     * With branding (logo and banner).
     */
    public function withBranding(): static
    {
        return $this->state(fn (array $attributes) => [
            'shop_logo' => 'merchants/logos/' . $this->faker->uuid() . '.png',
            'shop_banner' => 'merchants/banners/' . $this->faker->uuid() . '.jpg',
        ]);
    }
}
