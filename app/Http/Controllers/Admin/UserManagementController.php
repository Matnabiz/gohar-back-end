<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(){
        return User::all();
    }
    public function store(Request $request){
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required|string', // e.g. 'admin' or 'user'
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'data'    => $user
        ], 201);
    }
    public function update(Request $request, User $user){
        $validated = $request->validate([
            'name' => 'string',
            'email' => 'email',
            'is_admin' => 'boolean'
        ]);

        $user->update($validated);
        return $user;
    }

    public function destroy(User $user){
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
