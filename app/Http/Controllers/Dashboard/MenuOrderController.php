<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MenuOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = MenuOrder::query()
            ->with('items')
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->latest()
            ->paginate(20);

        return view('dashboard.orders.index', compact('orders'));
    }

    public function update(Request $request, MenuOrder $order): RedirectResponse
    {
        abort_unless($order->restaurant_id === $request->user()->restaurant_id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,preparing,completed,cancelled'],
        ]);

        $order->update($data);

        return back()->with('success', 'تم تحديث حالة الطلب.');
    }
}
