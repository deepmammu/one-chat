<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AiPlanner
{
    /**
     * Very lightweight intent/slot parser and executor.
     * Extracts billable and unbillable hours from the message and applies them
     * by creating/updating the day's timesheet and creating corresponding tasks.
     *
     * Returns a simple result payload for the chatbot to show.
     */
    public function planAndApply(int $employeeId, string $message, string $date): array
    {
        // Extract numbers: e.g., "4 billable", "2 unbillable"
        $billable = $this->extractNumber($message, '(billable|billable hours|billable_hour|billablehour)');
        $unbillable = $this->extractNumber($message, '(unbillable|unbillable hours|non-billable|non billable)');

        // Fallbacks: if user says "fill my timesheet" with no numbers, default 0/0
        $billable = max(0, (int) $billable);
        $unbillable = max(0, (int) $unbillable);

        $tsTable = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $tsIdCol = env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');
        $empCol = env('TIMESHEETS_EMPLOYEE_ID_COLUMN', 'Employee_id');
        $projCol = env('TIMESHEETS_PROJECT_ID_COLUMN', 'Project_Id');
        $hoursCol = env('TIMESHEETS_HOURS_COLUMN', 'Billable_hours');

        // Upsert (date, employee) timesheet
        $existing = DB::table($tsTable)
            ->where($dateCol, $date)
            ->where($empCol, $employeeId)
            ->first();

        if ($existing) {
            // If closed, return message
            if (isset($existing->Status) && strtolower($existing->Status) === 'closed') {
                return [
                    'reply' => 'Timesheet is closed for ' . $existing->{$dateCol} . '. Please reopen it before changes.',
                    'date' => $existing->{$dateCol},
                ];
            }
            // Clamp billable to 9
            $billable = min(9, $billable);
            DB::table($tsTable)
                ->where($tsIdCol, $existing->{$tsIdCol})
                ->update([
                    $hoursCol => $billable,
                    'Unbillable_hours' => $unbillable,
                    'updated_at' => now(),
                ]);
            $tsId = $existing->{$tsIdCol};
        } else {
            // create open timesheet
            $tsId = DB::table($tsTable)->insertGetId([
                $dateCol => $date,
                $empCol => $employeeId,
                $projCol => null,
                $hoursCol => min(9, $billable),
                'Unbillable_hours' => $unbillable,
                'Status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ], $tsIdCol);
        }

        // Create tasks to reflect entries
        $taskTable = env('TASKS_TABLE', 'task_details');
        $taskTsCol = env('TASKS_TIME_SHEET_ID_COLUMN', 'Time_sheet_id');

        // Create a single billable task if billable > 0
        if ($billable > 0) {
            // Ensure adding does not exceed daily cap 9
            $sumExisting = (int) DB::table($taskTable)->where($taskTsCol, $tsId)->sum('Billable_hours');
            $allocatable = max(0, min(9 - $sumExisting, $billable));
            if ($allocatable > 0) {
                DB::table($taskTable)->insert([
                    $taskTsCol => $tsId,
                    'employee_id' => $employeeId,
                    'Project_Id' => null,
                    'Task_name' => 'Chatbot billable entry',
                    'Task_mode' => 'billable',
                    'Billable_hours' => $allocatable,
                    'Unbillable_hours' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Create a single unbillable task if unbillable > 0 (respect unbillable capacity)
        if ($unbillable > 0) {
            $cap = (int) DB::table($tsTable)->where($tsIdCol, $tsId)->value('Unbillable_hours');
            $used = (int) DB::table($taskTable)->where($taskTsCol, $tsId)->sum('Unbillable_hours');
            $allocatable = max(0, min($cap - $used, $unbillable));
            if ($allocatable > 0) {
                DB::table($taskTable)->insert([
                    $taskTsCol => $tsId,
                    'employee_id' => $employeeId,
                    'Project_Id' => null,
                    'Task_name' => 'Chatbot unbillable entry',
                    'Task_mode' => 'unbillable',
                    'Billable_hours' => 0,
                    'Unbillable_hours' => $allocatable,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Sync timesheet totals from tasks to be consistent
        $sumBill = (int) DB::table($taskTable)->where($taskTsCol, $tsId)->sum('Billable_hours');
        $sumUnbill = (int) DB::table($taskTable)->where($taskTsCol, $tsId)->sum('Unbillable_hours');
        DB::table($tsTable)->where($tsIdCol, $tsId)->update([
            $hoursCol => $sumBill,
            'Unbillable_hours' => $sumUnbill,
            'updated_at' => now(),
        ]);

        return [
            'reply' => 'Updated timesheet and tasks for ' . $date,
            'timesheet_id' => $tsId,
            'billable_total' => $sumBill,
            'unbillable_total' => $sumUnbill,
        ];
    }

    private function extractNumber(string $message, string $labelRegex): int
    {
        // Find patterns like "4 billable" or "billable 4"
        $patterns = [
            '/(\d+)\s*' . $labelRegex . '/i',
            '/' . $labelRegex . '\s*(\d+)/i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $message, $m)) {
                return (int) ($m[1] ?? $m[2] ?? 0);
            }
        }
        return 0;
    }
}
