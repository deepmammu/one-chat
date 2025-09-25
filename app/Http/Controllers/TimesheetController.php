<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimesheetController extends Controller
{
    // GET /api/timesheets?date=YYYY-MM-DD&employee_id=...&project_id=...
    public function index(Request $request)
    {
        $table = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');
        $empCol = env('TIMESHEETS_EMPLOYEE_ID_COLUMN', 'Employee_id');
        $projCol = env('TIMESHEETS_PROJECT_ID_COLUMN', 'Project_Id');

        $q = DB::table($table)->orderBy($dateCol, 'desc');
        if ($request->filled('date')) {
            $q->where($dateCol, $request->string('date'));
        }
        if ($request->filled('employee_id')) {
            $q->where($empCol, $request->string('employee_id'));
        }
        if ($request->filled('project_id')) {
            $q->where($projCol, $request->string('project_id'));
        }
        return response()->json($q->limit(100)->get());
    }

    // POST /api/timesheets
    public function store(Request $request)
    {
        $table = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');
        $empCol = env('TIMESHEETS_EMPLOYEE_ID_COLUMN', 'Employee_id');
        $projCol = env('TIMESHEETS_PROJECT_ID_COLUMN', 'Project_Id');
        $hoursCol = env('TIMESHEETS_HOURS_COLUMN', 'Billing_hours');
        $notesCol = env('TIMESHEETS_NOTES_COLUMN', 'Comment');

        $data = $request->validate([
            'date' => ['required','date'],
            'employee_id' => ['nullable'],
            'project_id' => ['nullable'],
            'billing_hours' => ['required','numeric','min:0'],
            'comment' => ['nullable','string'],
        ]);

        $insert = [
            $dateCol => $data['date'],
            $hoursCol => $data['billing_hours'],
        ];
        if (array_key_exists('employee_id', $data)) $insert[$empCol] = $data['employee_id'];
        if (array_key_exists('project_id', $data)) $insert[$projCol] = $data['project_id'];
        if (array_key_exists('comment', $data)) $insert[$notesCol] = $data['comment'];

        // Insert and try to return primary key if available
        $idCol = env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id');
        $id = DB::table($table)->insertGetId($insert, $idCol);

        return response()->json(['id' => $id], 201);
    }

    // PUT/PATCH /api/timesheets/{id}
    public function update(Request $request, string $id)
    {
        $table = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $idCol = env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id');
        $dateCol = env('TIMESHEETS_DATE_COLUMN', 'Date');
        $empCol = env('TIMESHEETS_EMPLOYEE_ID_COLUMN', 'Employee_id');
        $projCol = env('TIMESHEETS_PROJECT_ID_COLUMN', 'Project_Id');
        $hoursCol = env('TIMESHEETS_HOURS_COLUMN', 'Billing_hours');
        $notesCol = env('TIMESHEETS_NOTES_COLUMN', 'Comment');

        $payload = $request->validate([
            'date' => ['sometimes','date'],
            'employee_id' => ['sometimes'],
            'project_id' => ['sometimes'],
            'billing_hours' => ['sometimes','numeric','min:0'],
            'comment' => ['sometimes','nullable','string'],
        ]);

        $update = [];
        foreach ([
            $dateCol => 'date',
            $empCol => 'employee_id',
            $projCol => 'project_id',
            $hoursCol => 'billing_hours',
            $notesCol => 'comment',
        ] as $col => $key) {
            if (array_key_exists($key, $payload)) {
                $update[$col] = $payload[$key];
            }
        }

        if (empty($update)) {
            throw ValidationException::withMessages(['message' => 'No fields to update']);
        }

        $affected = DB::table($table)->where($idCol, $id)->update($update);
        return response()->json(['updated' => $affected > 0]);
    }

    // DELETE /api/timesheets/{id}
    public function destroy(string $id)
    {
        $table = env('TIMESHEETS_TABLE', 'daily_timeSheet');
        $idCol = env('TIMESHEETS_ID_COLUMN', 'Time_sheet_id');
        $deleted = DB::table($table)->where($idCol, $id)->delete();
        return response()->json(['deleted' => $deleted > 0]);
    }
}
