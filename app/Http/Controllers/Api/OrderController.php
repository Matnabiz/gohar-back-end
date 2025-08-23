<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;

class OrderController extends Controller {
    public function store(Request $request){
        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();
        if(!$cart || $cart->items->isEmpty()) return response()->json(['message'=>'Cart empty'], 422);

        $total = $cart->items->sum(fn($it)=> $it->price * $it->quantity);
        $order = Order::create(['user_id'=>$user->id,'total'=>$total, 'status'=>'pending']);

        foreach($cart->items as $it){
            $order->items()->create([
                'product_id'=>$it->product_id,
                'quantity'=>$it->quantity,
                'price'=>$it->price,
                'meta'=>null
            ]);
        }
        // clear cart
        $cart->items()->delete();
        return response()->json($order->load('items'));
    }
}
