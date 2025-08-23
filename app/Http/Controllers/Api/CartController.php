<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends Controller {
    protected function currentCart(Request $request){
        $user = $request->user();
        $cart = $user->cart()->first() ?? Cart::create(['user_id'=>$user->id]);
        return $cart;
    }

    public function show(Request $request){
        $cart = $this->currentCart($request);
        $cart->load('items.product.images');
        $cart->items->transform(function($it){
            $it->product->images = $it->product->images->map(fn($img)=> asset('storage/'.$img->path));
            return $it;
        });
        return response()->json($cart);
    }

    public function add(Request $request){
        $data = $request->validate([
            'product_id'=>'required|exists:products,id',
            'quantity'=>'nullable|integer|min:1'
        ]);
        $cart = $this->currentCart($request);
        $product = Product::findOrFail($data['product_id']);
        $qty = $data['quantity'] ?? 1;
        $item = $cart->items()->where('product_id',$product->id)->first();
        if($item){
            $item->quantity += $qty;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id'=>$product->id,
                'quantity'=>$qty,
                'price'=>$product->price
            ]);
        }
        return $this->show($request);
    }

    public function update(Request $request){
        $data = $request->validate([
            'item_id'=>'required|exists:cart_items,id',
            'quantity'=>'required|integer|min:1'
        ]);
        $item = CartItem::findOrFail($data['item_id']);
        $item->quantity = $data['quantity'];
        $item->save();
        return response()->json(['ok'=>true]);
    }

    public function remove(Request $request){
        $data = $request->validate(['item_id'=>'required|exists:cart_items,id']);
        CartItem::findOrFail($data['item_id'])->delete();
        return response()->json(['ok'=>true]);
    }
}
