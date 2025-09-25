<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    // GET /api/tasks?employee_id=&project_id=&time_sheet_id=
    public function index(Request $request)
    {
        $table = env('TASKS_TABLE', 'task_details');
        $empCol = env('TASKS_EMPLOYEE_ID_COLUMN', 'employee_id');
        $projCol = env('TASKS_PROJECT_ID_COLUMN', 'Project_Id');
        $tsCol  = env('TASKS_TIME_SHEET_ID_COLUMN', 'Time_sheet_id');

        $q = DB::table($table);
        if ($request->filled('employee_id')) {
            $q->where($empCol, $request->string('employee_id'));
        }
        if ($request->filled('project_id')) {
            $q->where($projCol, $request->string('project_id'));
        }
        if ($request->filled('time_sheet_id')) {
            $q->where($tsCol, $request->string('time_sheet_id'));
        }

        return response()->json($q->limit(200)->get());
    }

    // POST /api/tasks
    public function store(Request $request)
    {
        $table = env('TASKS_TABLE', 'task_details');
        $empCol = env('TASKS_EMPLOYEE_ID_COLUMN', 'employee_id');
        $projCol = env('TASKS_PROJECT_ID_COLUMN', 'Project_Id');
        $tsCol  = env('TASKS_TIME_SHEET_ID_COLUMN', 'Time_sheet_id');
        $nameCol = env('TASKS_NAME_COLUMN', 'Task_name');
        $descCol = env('TASKS_DESCRIPTION_COLUMN', 'Task_description');
        $modeCol = env('TASKS_MODE_COLUMN', 'Task_mode');

        // Define timesheet table helpers used below
        $timesheetTable = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $tsIdCol = env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');
        $taskHoursCol = 'Billable_hours';
        $taskUnbillCol = 'Unbillable_hours';
        // Timesheet table/columns reused in multiple checks
        $timesheetTable = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $tsIdCol = env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');

        // Accept "billable_hours" as an alias for "billing_hours"
        if ($request->has('billable_hours') && !$request->has('billing_hours')) {
            $request->merge(['billing_hours' => $request->input('billable_hours')]);
        }

        $data = $request->validate([
            'employee_id' => ['required'],
            'project_id' => ['required'],
            'time_sheet_id' => ['nullable'],
            // If time_sheet_id is not provided, require a date to link/create a timesheet
            'date' => ['required_without:time_sheet_id','date'],
            'billing_hours' => ['required_unless:task_mode,unbillable,non-billable,nonbillable,non billable','nullable','numeric','min:0','max:9'],
            'unbillable_hours' => ['required_if:task_mode,unbillable,non-billable,nonbillable,non billable','nullable','numeric','min:0'],
            'task_name' => ['required','string'],
            'task_description' => ['nullable','string'],
            'task_mode' => ['nullable','string'],
        ]);

        $insert = [
            $empCol => $data['employee_id'],
            $projCol => $data['project_id'],
            $nameCol => $data['task_name'],
        ];
        // Determine if task is unbillable (bypasses cap and parent timesheet hour checks)
        $mode = strtolower((string)($data['task_mode'] ?? ''));
        $isUnbillable = in_array($mode, ['unbillable','non-billable','nonbillable','non billable']);

        // Resolve/link time_sheet_id
        if (!empty($data['time_sheet_id'])) {
            // Verify provided timesheet exists
            $tsRow = DB::table($timesheetTable)->where($tsIdCol, $data['time_sheet_id'])->first();
            if (!$tsRow) {
                return response()->json(['message' => 'fill timesheet for ' . ($data['date'] ?? 'today')], 422);
            }
            $errDate = $tsRow->{$dateCol} ?? ($data['date'] ?? null);
            if ($isUnbillable) {
                // Unbillable must have timesheet.Unbillable_hours > 0 and not exceed remaining capacity
                $tsUnbillCap = (int) ($tsRow->Unbillable_hours ?? 0);
                if ($tsUnbillCap <= 0) {
                    return response()->json(['message' => 'please fill unable_hours'], 422);
                }
                $usedUnbill = (int) DB::table($table)->where($tsCol, $tsRow->{$tsIdCol})->sum('Unbillable_hours');
                $requestedUnbill = (int) ($data['unbillable_hours'] ?? 0);
                if ($usedUnbill + $requestedUnbill > $tsUnbillCap) {
                    return response()->json(['message' => 'unbillable hours completed', 'date' => ($errDate ?? 'today')], 422);
                }
            } else {
                // Billable: require filled billable hours and open day
                if ((int)($tsRow->{env('TIMESHEETS_HOURS_COLUMN', 'Billable_hours')} ?? 0) <= 0) {
                    return response()->json(['message' => 'fill timesheet for ' . ($errDate ?? 'today')], 422);
                }
                if (isset($tsRow->Status) && strtolower($tsRow->Status) === 'closed') {
                    return response()->json(['message' => 'timesheet closed for ' . ($errDate ?? 'today')], 422);
                }
            }
            $insert[$tsCol] = $data['time_sheet_id'];
        } else {
            // Find timesheet by date+employee. For unbillable, require existing with capacity; do NOT auto-create.
            $lookup = DB::table($timesheetTable)
                ->where($dateCol, $data['date'])
                ->where(env('TIMESHEETS_EMPLOYEE_ID_COLUMN', 'Employee_id'), $data['employee_id']);

            $existing = $lookup->first();
            $hoursCol = env('TIMESHEETS_HOURS_COLUMN', 'Billable_hours');
            if ($isUnbillable) {
                if (!$existing) {
                    return response()->json(['message' => 'please fill unable_hours'], 422);
                }
                // Must have capacity > 0
                $tsUnbillCap = (int) ($existing->Unbillable_hours ?? 0);
                if ($tsUnbillCap <= 0) {
                    return response()->json(['message' => 'please fill unable_hours'], 422);
                }
                // Check remaining capacity
                $usedUnbill = (int) DB::table($table)->where($tsCol, $existing->{$tsIdCol})->sum('Unbillable_hours');
                $requestedUnbill = (int) ($data['unbillable_hours'] ?? 0);
                if ($usedUnbill + $requestedUnbill > $tsUnbillCap) {
                    return response()->json(['message' => 'unbillable hours completed', 'date' => $data['date']], 422);
                }
                $insert[$tsCol] = $existing->{$tsIdCol};
            } else {
                if (!$existing || (int)($existing->{$hoursCol} ?? 0) <= 0) {
                    return response()->json(['message' => 'fill timesheet for ' . $data['date']], 422);
                }
                if (isset($existing->Status) && strtolower($existing->Status) === 'closed') {
                    return response()->json(['message' => 'timesheet closed for ' . $data['date']], 422);
                }
                $insert[$tsCol] = $existing->{$tsIdCol};
            }
        }
        // Enforce daily cap: sum of task Billing_hours for this timesheet must not exceed 9
        $tsId = $insert[$tsCol] ?? null;
        if ($tsId && !$isUnbillable) {
            $existingSum = (int) DB::table($table)->where($tsCol, $tsId)->sum($taskHoursCol);
            $proposed = $existingSum + (int) $data['billing_hours'];
            if ($proposed > 9) {
                $errDate = DB::table($timesheetTable)->where($tsIdCol, $tsId)->value($dateCol);
                return response()->json(['message' => 'billing hours completed', 'date' => $errDate], 422);
            }
        }
        if (array_key_exists('task_description', $data)) $insert[$descCol] = $data['task_description'];
        if (array_key_exists('task_mode', $data)) $insert[$modeCol] = $data['task_mode'];

        // Set task hours
        $insert[$taskHoursCol] = $isUnbillable ? 0 : (int) ($data['billing_hours'] ?? 0);
        $insert[$taskUnbillCol] = $isUnbillable ? (int) ($data['unbillable_hours'] ?? 0) : 0;

        // Ensure timestamps are populated when using the query builder
        $insert['created_at'] = now();
        $insert['updated_at'] = now();

        $idCol = env('TASKS_ID_COLUMN', 'Task_id');
        $id = DB::table($table)->insertGetId($insert, $idCol);

        // After insert, update parent daily totals (billable, and unbillable separately)
        if ($tsId) {
            $sumBillable = (int) DB::table($table)->where($tsCol, $tsId)->sum($taskHoursCol);
            $sumUnbill = (int) DB::table($table)->where($tsCol, $tsId)->sum($taskUnbillCol);
            DB::table(env('TIMESHEETS_TABLE', 'daily_timeSheet'))
                ->where(env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id'), $tsId)
                ->update([
                    env('TIMESHEETS_HOURS_COLUMN', 'Billable_hours') => $sumBillable,
                    'Unbillable_hours' => $sumUnbill,
                    'updated_at' => now(),
                ]);
        }
        return response()->json(['id' => $id], 201);
    }

    // PUT/PATCH /api/tasks/{id}
    public function update(Request $request, string $id)
    {
        $table = env('TASKS_TABLE', 'task_details');
        $idCol = env('TASKS_ID_COLUMN', 'Task_id');
        $empCol = env('TASKS_EMPLOYEE_ID_COLUMN', 'employee_id');
        $projCol = env('TASKS_PROJECT_ID_COLUMN', 'Project_Id');
        $tsCol  = env('TASKS_TIME_SHEET_ID_COLUMN', 'Time_sheet_id');
        $nameCol = env('TASKS_NAME_COLUMN', 'Task_name');
        $descCol = env('TASKS_DESCRIPTION_COLUMN', 'Task_description');
        $modeCol = env('TASKS_MODE_COLUMN', 'Task_mode');
        $taskHoursCol = 'Billable_hours';
        $taskUnbillCol = 'Unbillable_hours';

        // Timesheet helpers used for validations and syncing
        $timesheetTable = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $tsIdCol = env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');

        // Accept "billable_hours" alias on update too
        if ($request->has('billable_hours') && !$request->has('billing_hours')) {
            $request->merge(['billing_hours' => $request->input('billable_hours')]);
        }

        $payload = $request->validate([
            'date' => ['sometimes','date'],
            'employee_id' => ['sometimes'],
            'project_id' => ['sometimes'],
            'billing_hours' => ['sometimes','nullable','numeric','min:0','max:9'],
            'unbillable_hours' => ['sometimes','nullable','numeric','min:0'],
            'comment' => ['sometimes','nullable','string'],
            'task_name' => ['sometimes','string'],
            'task_description' => ['sometimes','nullable','string'],
            'task_mode' => ['sometimes','nullable','string'],
        ]);

        $update = [];
        foreach ([
            $empCol => 'employee_id',
            $projCol => 'project_id',
            $tsCol => 'time_sheet_id',
            $nameCol => 'task_name',
            $descCol => 'task_description',
            $modeCol => 'task_mode',
        ] as $col => $key) {
            if (array_key_exists($key, $payload)) {
                $update[$col] = $payload[$key];
            }
        }

        // If billing_hours is provided, enforce daily cap before update
        $current = DB::table($table)->where($idCol, $id)->first();
        if (!$current) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        $tsId = $current->{$projCol = env('TASKS_TIME_SHEET_ID_COLUMN', 'Time_sheet_id')};
        // Note: restore original $projCol variable
        $projCol = env('TASKS_PROJECT_ID_COLUMN', 'Project_Id');
        if ($tsId) {
            // Determine current or new mode to decide billable behavior
            $newMode = strtolower((string)($payload['task_mode'] ?? $current->Task_mode ?? ''));
            $isUnbillableUpdate = in_array($newMode, ['unbillable','non-billable','nonbillable','non billable']);
        }

        if (array_key_exists('billing_hours', $payload) && $tsId && !$isUnbillableUpdate) {
            $existingSum = (int) DB::table($table)->where('Time_sheet_id', $tsId)->sum($taskHoursCol);
            $currentHours = (int) ($current->{$taskHoursCol} ?? 0);
            $newHours = (int) $payload['billing_hours'];
            $proposed = $existingSum - $currentHours + $newHours;
            if ($proposed > 9) {
                $errDate = DB::table($timesheetTable)->where($tsIdCol, $tsId)->value($dateCol);
                return response()->json(['message' => 'billing hours completed', 'date' => $errDate], 422);
            }
            $update[$taskHoursCol] = $newHours;
        }

        // Handle unbillable updates
        if ($tsId) {
            $rowTs = DB::table($timesheetTable)->where($tsIdCol, $tsId)->first();
        }
        if (isset($isUnbillableUpdate) && $isUnbillableUpdate) {
            // Ensure capacity not exceeded when changing/unsetting hours
            $tsCap = (int) ($rowTs->Unbillable_hours ?? 0);
            if (array_key_exists('unbillable_hours', $payload)) {
                $newUnbill = (int) $payload['unbillable_hours'];
                $sumOther = (int) DB::table($table)
                    ->where('Time_sheet_id', $tsId)
                    ->where($idCol, '!=', $id)
                    ->sum($taskUnbillCol);
                if ($sumOther + $newUnbill > $tsCap) {
                    $errDate = $rowTs->{$dateCol} ?? null;
                    return response()->json(['message' => 'unbillable hours completed', 'date' => ($errDate ?? 'today')], 422);
                }
                $update[$taskUnbillCol] = $newUnbill;
            }
            // Force billable to 0 for unbillable tasks
            $update[$taskHoursCol] = 0;
        } elseif (array_key_exists('unbillable_hours', $payload)) {
            // Ignore unbillable_hours for billable tasks
        }

        if (empty($update)) {
            throw ValidationException::withMessages(['message' => 'No fields to update']);
        }

        // touch updated_at
        $update['updated_at'] = now();
        $affected = DB::table($table)->where($idCol, $id)->update($update);

        // Recalculate and update the parent timesheet totals (billable and unbillable)
        if ($tsId) {
            $sumAfter = (int) DB::table($table)->where('Time_sheet_id', $tsId)->sum($taskHoursCol);
            $sumUnbillAfter = (int) DB::table($table)->where('Time_sheet_id', $tsId)->sum($taskUnbillCol);
            DB::table(env('TIMESHEETS_TABLE', 'daily_timeSheet'))
                ->where(env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id'), $tsId)
                ->update([
                    env('TIMESHEETS_HOURS_COLUMN', 'Billable_hours') => $sumAfter,
                    'Unbillable_hours' => $sumUnbillAfter,
                    'updated_at' => now(),
                ]);
        }
        return response()->json(['updated' => $affected > 0]);
    }

    // DELETE /api/tasks/{id}
    public function destroy(string $id)
    {
        $table = env('TASKS_TABLE', 'task_details');
        $idCol = env('TASKS_ID_COLUMN', 'Task_id');
        $deleted = DB::table($table)->where($idCol, $id)->delete();
        return response()->json(['deleted' => $deleted > 0]);
    }
}
