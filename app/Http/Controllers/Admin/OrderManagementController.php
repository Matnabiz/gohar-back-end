<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('delivery_option')) {
            $query->where('delivery_option', $request->delivery_option);
        }

        if ($request->filled('province')) {
            $query->whereHas('user', fn($q) => $q->where('province', 'like', '%' . $request->province . '%'));
        }

        if ($request->filled('city')) {
            $query->whereHas('user', fn($q) => $q->where('city', 'like', '%' . $request->city . '%'));
        }

        // date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // total filters
        if ($request->filled('min_total')) {
            $query->where('total', '>=', $request->min_total);
        }
        if ($request->filled('max_total')) {
            $query->where('total', '<=', $request->max_total);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('user', fn($uq) => $uq->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%"))
                    ->orWhere('id', $q);
            });
        }

        // sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['id', 'total', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) $sortBy = 'created_at';
        $query->orderBy($sortBy, $sortDir);

        // pagination
        $perPage = (int) $request->get('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    public function destroy(Request $request, $id){
        $order = Order::findOrFail($id);

        if (!$request->user()->is_admin && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }

}
