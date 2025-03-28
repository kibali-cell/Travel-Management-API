<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_id' => 'required_without:company_name|exists:companies,id',
            'company_name' => 'required_without:company_id|string|max:255',
            'role' => 'required|in:employee,travel_admin',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {
            $company = $request->company_id
                ? Company::find($request->company_id)
                : Company::create(['name' => $request->company_name]);
    
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $company->id,
            ]);
    
            // Ensure the role exists with both name and slug
            $displayName = ucwords(str_replace('_', ' ', $request->role)); // e.g., "Travel Admin"
            $role = \Spatie\Permission\Models\Role::firstOrCreate(
                ['slug' => $request->role],
                ['name' => $displayName, 'slug' => $request->role, 'description' => "$displayName role"]
            );
    
            // Assign the role and log the result
            $user->assignRole($role);
            $user->refresh(); // Ensure the roles relationship is reloaded
            $assignedRoles = $user->roles->pluck('name')->toArray();
            \Log::info('Assigned role to user', [
                'user_id' => $user->id,
                'role' => $request->role,
                'roles_after_assignment' => $assignedRoles,
            ]);
    
            // Check if the role was actually assigned
            if (empty($assignedRoles)) {
                \Log::error('Failed to assign role to user', [
                    'user_id' => $user->id,
                    'role' => $request->role,
                ]);
                throw new \Exception('Failed to assign role to user');
            }
    
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json(['access_token' => $token, 'user' => $user], 201);
        } catch (\Exception $e) {
            \Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('roles')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('roles', 'company');
        return response()->json(['user' => $user]);
    }
}