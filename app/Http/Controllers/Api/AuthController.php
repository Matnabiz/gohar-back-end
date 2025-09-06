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
        try {
            $validated = $request->validate([
                'email'    => 'nullable|string|email|max:255|unique:users|required_without:phone',
                'phone'    => 'nullable|string|regex:/^[0-9]{10,15}$/|unique:users|required_without:email',
                'password' => 'required|string|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = User::create([
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    public function login(Request $request){
        $data = $request->validate(['email'=>'required|email','password'=>'required']);
        $user = User::where('email', $data['email'])->first();
        if(!$user || !Hash::check($data['password'], $user->password)){
            throw ValidationException::withMessages(['email'=>['ایمیل یا رمز عبور، اشتباه است.']]);
        }
        // create token or use Sanctum cookie
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['user'=>$user, 'token'=>$token]);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'Logged out']);
    }
}
