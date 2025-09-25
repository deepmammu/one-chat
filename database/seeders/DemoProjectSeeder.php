<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Two demo projects for Employee_id 1001
        DB::table('projects')->updateOrInsert(
            ['Project_Id' => 101],
            [
                'Employee_id' => 1001,
                'Project_name' => 'Intranet Revamp',
                'Project_description' => 'Revamp company intranet portal',
                'Project_start_date' => now()->subDays(30)->toDateString(),
                'Project_end_date' => null,
                'Billing_days' => 10,
                'Project_sow' => 1,
                'Project_status' => 'active',
                'Role' => 'Developer',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('projects')->updateOrInsert(
            ['Project_Id' => 102],
            [
                'Employee_id' => 1001,
                'Project_name' => 'Legacy System Audit',
                'Project_description' => 'Audit and documentation project',
                'Project_start_date' => now()->subDays(60)->toDateString(),
                'Project_end_date' => now()->subDays(10)->toDateString(),
                'Billing_days' => 20,
                'Project_sow' => 2,
                'Project_status' => 'inactive',
                'Role' => 'Analyst',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
