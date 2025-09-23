<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->command->info('Admin user created with email: admin@example.com and password: password123');
        }

        // Create a regular test user if it doesn't exist
        if (!User::where('email', 'user@example.com')->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'is_active' => true,
                'email_verified_at' => now(),
                // Add sample API credentials for testing
                'api_key' => 'sample-api-key-123',
                'api_operator_id' => 'sample-operator-123',
                'api_base_url' => 'https://api.postingdeclaration.eu',
            ]);

            $this->command->info('Test user created with email: user@example.com and password: password123');
        }
    }
}
