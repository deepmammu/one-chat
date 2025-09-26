<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\AiPlanner;

class ChatbotController extends Controller
{
    // POST /api/chatbot
    public function chat(Request $request, AiPlanner $planner)
    {
        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json([
                'reply' => 'Hello! Please login to continue, then tell me things like: "Fill my timesheet with 4 billable and 2 unbillable hours for today."',
                'require_login' => true,
            ], 200);
        }

        // If user not logged in, greet and ask to login (but do not error)
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'reply' => 'Hi! Please login to continue. Use the Login button above, then come back and ask me to fill your timesheet (e.g., 4 billable and 2 unbillable today).',
                'require_login' => true,
            ], 200);
        }

        // Optional date override from query (defaults to today)
        $date = $request->input('date');
        if (!$date) {
            $date = now()->toDateString();
        }

        // Let the planner interpret and take actions (timesheet + tasks)
        $result = $planner->planAndApply(
            employeeId: $user->{env('USER_EMPLOYEE_ID_COLUMN', 'Employee_id')} ?? $user->id,
            message: $message,
            date: $date,
        );

        return response()->json($result);
    }
}
