<?php

namespace App\Domain\Identity\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Identity\Models\OperatorRole;

/**
 * Operator Role Seeder
 *
 * Seeds default admin roles with permissions.
 */
class OperatorRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'permissions' => ['*'],
            ],
            [
                'name' => 'Admin',
                'permissions' => [
                    'dashboard.view',
                    'catalog.*',
                    'categories.*',
                    'brands.*',
                    'merchants.view', 'merchants.update',
                    'orders.*',
                    'users.view', 'users.update',
                    'shipping.*',
                    'reports.view',
                ],
            ],
            [
                'name' => 'Content Manager',
                'permissions' => [
                    'dashboard.view',
                    'catalog.view', 'catalog.create', 'catalog.update',
                    'categories.view', 'categories.create', 'categories.update',
                    'brands.view', 'brands.create', 'brands.update',
                    'reviews.view', 'reviews.update',
                ],
            ],
            [
                'name' => 'Order Manager',
                'permissions' => [
                    'dashboard.view',
                    'orders.*',
                    'merchants.view',
                    'shipping.view', 'shipping.update',
                    'users.view',
                ],
            ],
            [
                'name' => 'Finance Manager',
                'permissions' => [
                    'dashboard.view',
                    'orders.view',
                    'accounting.*',
                    'withdrawals.*',
                    'reports.*',
                ],
            ],
            [
                'name' => 'Support',
                'permissions' => [
                    'dashboard.view',
                    'orders.view',
                    'users.view',
                    'merchants.view',
                    'reviews.view', 'reviews.update',
                ],
            ],
        ];

        foreach ($roles as $role) {
            OperatorRole::firstOrCreate(
                ['name' => $role['name']],
                ['permissions' => json_encode($role['permissions'])]
            );
        }

        $this->command->info('Seeded ' . count($roles) . ' operator roles.');
    }
}
