<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tasks for Employee 1001
        DB::table('task_details')->updateOrInsert(
            ['Task_id' => 5001],
            [
                'employee_id' => 1001,
                'Project_Id' => 101,
                'Time_sheet_id' => null,
                'Task_name' => 'Design homepage',
                'Task_description' => 'Create wireframes and mockups',
                'Task_mode' => 'billable',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('task_details')->updateOrInsert(
            ['Task_id' => 5002],
            [
                'employee_id' => 1001,
                'Project_Id' => 101,
                'Time_sheet_id' => null,
                'Task_name' => 'API integration',
                'Task_description' => 'Integrate auth endpoints',
                'Task_mode' => 'billable',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
