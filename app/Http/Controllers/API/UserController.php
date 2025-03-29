<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            // Super admin gets all users across companies
            $users = User::with('roles', 'company')->get();
        } elseif ($user->hasRole('travel_admin') || $user->hasRole('employee')) {
            // Travel admin and employee get only users from their company
            $users = User::with('roles', 'company')
                ->where('company_id', $user->company_id)
                ->get();
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['users' => $users]);
    }


    public function store(Request $request)
    {
        // Only admins can create users
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,employee',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $request->user()->company_id, // Same company as admin
        ]);

        $role = Role::where('slug', $request->role)->first();
        $user->roles()->attach($role);

        return response()->json(['user' => $user->load('roles')], 201);
    }

    public function show(Request $request, User $user)
    {
        // Check if requesting own data or if admin from same company
        if ($request->user()->id === $user->id || 
            ($request->user()->isAdmin() && $request->user()->company_id === $user->company_id)) {
            return response()->json(['user' => $user->load('roles', 'company')]);
        }
        
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function update(Request $request, User $user)
    {
        // Check if updating own data or if admin from same company
        if (!($request->user()->id === $user->id || 
            ($request->user()->isAdmin() && $request->user()->company_id === $user->company_id))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        
        $user->update($data);

        // Only admins can update roles
        if ($request->user()->isAdmin() && $request->filled('role')) {
            $role = Role::where('slug', $request->role)->first();
            $user->roles()->sync([$role->id]);
        }

        return response()->json(['user' => $user->load('roles')]);
    }

    public function destroy(Request $request, User $user)
    {
        // Only admins can delete users from their company
        if (!($request->user()->isAdmin() && $request->user()->company_id === $user->company_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Don't allow deleting yourself
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 400);
        }
        
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}