<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->load('roles', 'company');
        return response()->json($user);
    }
    
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name'                    => 'sometimes|required|string|max:255',
            'email'                   => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'avatar'                  => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'phone'                   => 'nullable|string|max:20',
            'address'                 => 'nullable|string|max:255',
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'sex'                     => 'nullable|string|in:Male,Female',
            'date_of_birth'           => 'nullable|date|before:today',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Avatar handling
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            
            // Delete old avatar if it exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $user->avatar = $avatarPath;
        }
        
        // Update user details
        $user->update($request->only([
            'name', 'email', 'phone', 'address', 
            'emergency_contact_name', 'emergency_contact_phone',
            'sex', 'date_of_birth'
        ]));
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()->load('roles', 'company')
        ]);
    }
    
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }
        
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }
    
    public function updateLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|in:en,es,fr,de,pt,ja,zh',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $user->update([
            'language' => $request->language
        ]);
        
        return response()->json([
            'message'  => 'Language preference updated successfully',
            'language' => $user->language
        ]);
    }
    
    public function updateNotificationPreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'required|boolean',
            'push_notifications'  => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $user->update([
            'email_notifications' => $request->email_notifications,
            'push_notifications'  => $request->push_notifications,
        ]);
        
        return response()->json([
            'message'     => 'Notification preferences updated successfully',
            'preferences' => [
                'email_notifications' => $user->email_notifications,
                'push_notifications'  => $user->push_notifications,
            ]
        ]);
    }
}