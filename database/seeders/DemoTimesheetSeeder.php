<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTimesheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo day for Employee 1001 on Project 101
        DB::table('daily_timeSheet')->updateOrInsert(
            ['Time_sheet_id' => 7001],
            [
                'Date' => now()->toDateString(),
                'Employee_id' => 1001,
                'Project_Id' => 101,
                'Billable_hours' => 6,
                'Comment' => 'Initial setup and design',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
