<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo active user
        DB::table('users')->updateOrInsert(
            ['email' => 'demo.user@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password123'),
                'Employee_id' => 1001,
                'Status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Inactive user (for negative tests)
        DB::table('users')->updateOrInsert(
            ['email' => 'inactive.user@example.com'],
            [
                'name' => 'Inactive User',
                'password' => Hash::make('password123'),
                'Employee_id' => 1002,
                'Status' => 'inactive',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
