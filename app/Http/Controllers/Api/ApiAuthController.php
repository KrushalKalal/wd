<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user has employee record
        if (!$user->employee) {
            return response()->json([
                'success' => false,
                'message' => 'User is not an employee'
            ], 403);
        }

        // Check if employee is active
        if (!$user->employee->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Employee account is inactive'
            ], 403);
        }

        // Create token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                ],
                'employee' => [
                    'id' => $user->employee->id,
                    'name' => $user->employee->name,
                    'designation' => $user->employee->designation,
                    'employee_image' => $user->employee->employee_image
                        ? asset('storage/' . $user->employee->employee_image)
                        : null,
                    'contact' => $user->employee->contact_number_1,
                ]
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'employee' => $employee->load([
                    'company',
                    'branch',
                    'department',
                    'manager',
                    'activeStoreAssignments.store',
                    'currentMonthTarget'
                ])
            ]
        ]);
    }
}