<?php

namespace App\Domain\Identity\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * User Seeder
 *
 * Seeds sample users (customers and merchants).
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample merchants
        $merchants = [
            [
                'name' => 'قطع غيار الرياض',
                'email' => 'riyadh-parts@example.com',
                'phone' => '+966501234567',
                'is_merchant' => 1,
            ],
            [
                'name' => 'جدة للسيارات',
                'email' => 'jeddah-auto@example.com',
                'phone' => '+966502345678',
                'is_merchant' => 1,
            ],
            [
                'name' => 'الدمام قطع غيار',
                'email' => 'dammam-parts@example.com',
                'phone' => '+966503456789',
                'is_merchant' => 1,
            ],
        ];

        // Sample customers
        $customers = [
            [
                'name' => 'أحمد محمد',
                'email' => 'ahmed@example.com',
                'phone' => '+966551234567',
                'is_merchant' => 0,
            ],
            [
                'name' => 'محمد علي',
                'email' => 'mohammed@example.com',
                'phone' => '+966552345678',
                'is_merchant' => 0,
            ],
            [
                'name' => 'فهد سعود',
                'email' => 'fahad@example.com',
                'phone' => '+966553456789',
                'is_merchant' => 0,
            ],
        ];

        $users = array_merge($merchants, $customers);

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                array_merge($user, [
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => 1,
                ])
            );
        }

        $this->command->info('Seeded ' . count($merchants) . ' merchants and ' . count($customers) . ' customers.');
    }
}
