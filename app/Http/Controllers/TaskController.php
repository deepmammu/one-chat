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

        $data = $request->validate([
            'employee_id' => ['required'],
            'project_id' => ['required'],
            'time_sheet_id' => ['nullable'],
            'task_name' => ['required','string'],
            'task_description' => ['nullable','string'],
            'task_mode' => ['nullable','string'],
        ]);

        $insert = [
            $empCol => $data['employee_id'],
            $projCol => $data['project_id'],
            $nameCol => $data['task_name'],
        ];
        if (!empty($data['time_sheet_id'])) $insert[$tsCol] = $data['time_sheet_id'];
        if (array_key_exists('task_description', $data)) $insert[$descCol] = $data['task_description'];
        if (array_key_exists('task_mode', $data)) $insert[$modeCol] = $data['task_mode'];

        $idCol = env('TASKS_ID_COLUMN', 'Task_id');
        $id = DB::table($table)->insertGetId($insert, $idCol);
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

        $payload = $request->validate([
            'employee_id' => ['sometimes'],
            'project_id' => ['sometimes'],
            'time_sheet_id' => ['sometimes'],
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

        if (empty($update)) {
            throw ValidationException::withMessages(['message' => 'No fields to update']);
        }

        $affected = DB::connection('projectsdb')->table($table)->where($idCol, $id)->update($update);
        return response()->json(['updated' => $affected > 0]);
    }

    // DELETE /api/tasks/{id}
    public function destroy(string $id)
    {
        $table = env('TASKS_TABLE', 'task_details');
        $idCol = env('TASKS_ID_COLUMN', 'Task_id');
        $deleted = DB::connection('projectsdb')->table($table)->where($idCol, $id)->delete();
        return response()->json(['deleted' => $deleted > 0]);
    }
}
