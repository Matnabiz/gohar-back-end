<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller {
    public function register(Request $request)
    {
        // 1. Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 2. Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 3. Create a token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 4. Return response
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }


    public function login(Request $request){
        $data = $request->validate(['email'=>'required|email','password'=>'required']);
        $user = User::where('email', $data['email'])->first();
        if(!$user || !Hash::check($data['password'], $user->password)){
            throw ValidationException::withMessages(['email'=>['The provided credentials are incorrect.']]);
        }
        // create token or use Sanctum cookie
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['user'=>$user, 'token'=>$token]);
    }

    public function logout(Request $request){
        // revoke tokens
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'Logged out']);
    }
}
