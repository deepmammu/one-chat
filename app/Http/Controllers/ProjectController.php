<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    // GET /api/projects
    public function index(Request $request)
    {
        $table = env('PROJECTS_TABLE', 'projects');
        $idCol = env('PROJECTS_ID_COLUMN', 'Project_Id');
        $empCol = env('PROJECTS_EMPLOYEE_ID_COLUMN', 'Employee_id');
        $statusCol = env('PROJECTS_STATUS_COLUMN', 'Project_status');

        $q = DB::table($table);

        // Filter by employee if provided
        if ($request->filled('employee_id')) {
            $q->where($empCol, $request->string('employee_id'));
        }

        // Only active by default unless explicitly requested otherwise
        $onlyActive = $request->boolean('active', true);
        if ($onlyActive) {
            $q->where($statusCol, 'active');
        }

        // Ordering by id desc if present
        $q->orderBy($idCol, 'desc');

        return response()->json($q->limit(100)->get());
    }

    // GET /api/projects/{id}
    public function show(string $id)
    {
        $table = env('PROJECTS_TABLE', 'projects');
        $idCol = env('PROJECTS_ID_COLUMN', 'Project_Id');
        $project = DB::table($table)->where($idCol, $id)->first();
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }
        return response()->json($project);
    }
}
