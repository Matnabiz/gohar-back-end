<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request){
        return response()->json($request->user());
    }

    public function update(Request $request){
        $user = $request->user();
        $data = $request->validate([
            'name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'preferences' => 'nullable|array',
        ]);
        $user->fill($data)->save();
        return response()->json($request->user());
    }

    public function updateAvatar(Request $request){
        $request->validate(['avatar' => 'required|image|max:4096']);
        $path = $request->file('avatar')->store('avatars', 'public');
        $user = $request->user();
        $user->avatar_path = $path;
        $user->save();
        return ['avatar_url' => Storage::url($path)];
    }

    public function orders(Request $request){
        return $request->user()->orders()->latest()->get();
    }

    public function wishlist(Request $request){
        return $request->user()->wishlist()->with('product')->get()->map(fn($w) => [
            'id' => $w->id,
            'title' => $w->product->title,
            'price' => $w->product->price,
            'image' => $w->product->image_url,
        ]);
    }

}
