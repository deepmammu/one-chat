<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    // POST /api/auth/login
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        // Attempt to validate against external user database
        $userRow = DB::table(env('USER_TABLE', 'users'))
            ->where(env('USER_EMAIL_COLUMN', 'email'), $data['email'])
            ->first();

        if (!$userRow) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $passwordColumn = env('USER_PASSWORD_COLUMN', 'password');
        $hashed = $userRow->{$passwordColumn} ?? null;

        if (!$hashed || !Hash::check($data['password'], $hashed)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        // Ensure the user is active if a status column exists
        $statusCol = env('USER_STATUS_COLUMN', 'Status');
        if (isset($userRow->{$statusCol})) {
            $statusVal = strtolower((string)$userRow->{$statusCol});
            if ($statusVal !== 'active') {
                throw ValidationException::withMessages([
                    'email' => ['User is not active.'],
                ]);
            }
        }

        // Sync or create a local User for Sanctum token issuance
        $nameColumn = env('USER_NAME_COLUMN', 'name');
        $employeeIdCol = env('USER_EMPLOYEE_ID_COLUMN', 'Employee_id');
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $userRow->{$nameColumn} ?? $data['email'], 'password' => $hashed]
        );

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $userRow->{$employeeIdCol} ?? null,
            ],
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    // GET /api/auth/me
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
