<?php

namespace App\Domain\Identity\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Identity\Models\Operator;
use App\Domain\Identity\Models\OperatorRole;
use Illuminate\Support\Facades\Hash;

/**
 * Operator Seeder
 *
 * Seeds default admin users.
 */
class OperatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = OperatorRole::where('name', 'Super Admin')->first();

        if (!$superAdminRole) {
            $this->command->warn('Please run OperatorRoleSeeder first.');
            return;
        }

        $operators = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@muaadh.com',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id,
                'status' => 1,
            ],
        ];

        foreach ($operators as $operator) {
            Operator::firstOrCreate(
                ['email' => $operator['email']],
                $operator
            );
        }

        $this->command->info('Seeded ' . count($operators) . ' operators.');
    }
}
