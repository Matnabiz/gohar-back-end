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

    public function show(Request $request)
    {
        $cart = $this->currentCart($request);

        // Load products with each cart item
        $cart->load('items.product');

        // Transform products to include main_image_url
        $cart->items->transform(function ($it) {
            $it->product->main_image_url = $it->product->main_image
                ? asset('storage/' . $it->product->main_image)
                : asset('images/placeholder.png');
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
        $cart = $this->currentCart($request);
        $item = $cart->items()->where('id', $data['item_id'])->firstOrFail();
        $item->quantity = $data['quantity'];
        $item->save();
        return $this->show($request);
    }

    public function remove(Request $request){
        $data = $request->validate(['item_id'=>'required|exists:cart_items,id']);
        CartItem::findOrFail($data['item_id'])->delete();
        return $this->show($request);
    }
}
