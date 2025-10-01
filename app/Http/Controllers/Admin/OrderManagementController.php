<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function destroy(Request $request, $id){
        $order = Order::findOrFail($id);

        if (!$request->user()->is_admin && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If you want soft delete, make sure Order model uses SoftDeletes
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }

}
