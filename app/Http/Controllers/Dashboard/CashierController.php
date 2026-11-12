<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\MenuOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashierController extends Controller
{
    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        $categories = $restaurant->categories()
            ->where('is_active', true)
            ->with(['menuPage', 'items' => fn ($q) => $q->where('is_active', true)->where('is_available', true)])
            ->orderBy('menu_page_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($category) => $category->items->isNotEmpty())
            ->values();

        $unpaidOrders = $restaurant->hasModule('orders')
            ? $restaurant->orders()
                ->with('items')
                ->where('payment_status', 'unpaid')
                ->where('status', '!=', 'cancelled')
                ->latest()
                ->take(30)
                ->get()
            : collect();

        return view('dashboard.cashier.index', [
            'restaurant' => $restaurant,
            'categories' => $categories,
            'unpaidOrders' => $unpaidOrders,
            'cashier' => $restaurant->cashierSettings(),
            'paymentMethods' => $restaurant->paymentMethods(),
            'receiptOrder' => session('receipt_order')
                ? $restaurant->orders()->find(session('receipt_order'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;
        $cashier = $restaurant->cashierSettings();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'table_number' => ['nullable', 'integer', 'min:1', 'max:'.max(1, (int) $restaurant->tables_count)],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(array_keys($restaurant->paymentMethods()))],
        ], [
            'items.required' => 'أضف صنفًا واحدًا على الأقل قبل التحصيل.',
        ]);

        // Quantities of the same item sent twice are added up, never overwritten.
        $requested = collect($data['items'])
            ->groupBy(fn ($row) => (int) $row['id'])
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $items = Item::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->whereIn('id', $requested->keys())
            ->get();

        if ($items->count() !== $requested->count()) {
            throw ValidationException::withMessages(['items' => 'بعض الأصناف لم تعد متاحة. حدّث الصفحة وأعد المحاولة.']);
        }

        // Prices always come from the database, never from the browser.
        $subtotal = round($items->sum(fn (Item $item) => (float) $item->price * $requested[$item->id]), 2);
        $discount = $cashier['allow_discount'] ? round((float) ($data['discount'] ?? 0), 2) : 0.0;

        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'الخصم أكبر من قيمة الفاتورة.']);
        }

        $totals = $restaurant->cashierTotals($subtotal, $discount);

        $order = DB::transaction(function () use ($restaurant, $request, $data, $items, $requested, $totals) {
            $order = MenuOrder::create([
                'restaurant_id' => $restaurant->id,
                'source' => 'cashier',
                'order_type' => $data['order_type'],
                'customer_name' => $data['customer_name'] ?? 'عميل الكاشير',
                'customer_phone' => $data['customer_phone'] ?? null,
                'table_number' => $data['order_type'] === 'dine_in' ? ($data['table_number'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                ...$totals,
                'currency' => $restaurant->currency,
                'payment_status' => 'paid',
                'payment_method' => $data['payment_method'],
                'paid_at' => now(),
                'cashier_id' => $request->user()->id,
                'confirmation_token' => (string) Str::uuid(),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'quantity' => $requested[$item->id],
                    'unit_price' => $item->price,
                    'line_total' => round((float) $item->price * $requested[$item->id], 2),
                ]);
            }

            return $order;
        });

        return redirect()->route('dashboard.cashier.index')
            ->with('success', 'تم تسجيل الفاتورة رقم #'.$order->id.' وتحصيلها.')
            ->with('receipt_order', $order->id);
    }

    public function pay(Request $request, MenuOrder $order): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;
        abort_unless($order->restaurant_id === $restaurant->id, 404);

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys($restaurant->paymentMethods()))],
        ]);

        if ($order->payment_status === 'paid') {
            return back()->withErrors(['payment_method' => 'الطلب #'.$order->id.' محصَّل من قبل.']);
        }

        if ($order->status === 'cancelled') {
            return back()->withErrors(['payment_method' => 'لا يمكن تحصيل طلب ملغي.']);
        }

        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $data['payment_method'],
            'paid_at' => now(),
            'cashier_id' => $request->user()->id,
        ]);

        return redirect()->route('dashboard.cashier.index')
            ->with('success', 'تم تحصيل الطلب رقم #'.$order->id.'.')
            ->with('receipt_order', $order->id);
    }

    public function receipt(Request $request, MenuOrder $order): View
    {
        $restaurant = $request->user()->restaurant;
        abort_unless($order->restaurant_id === $restaurant->id, 404);

        return view('dashboard.cashier.receipt', [
            'restaurant' => $restaurant,
            'order' => $order->load(['items', 'cashier']),
            'cashier' => $restaurant->cashierSettings(),
            'paymentMethods' => config('modules.payment_methods'),
        ]);
    }

    public function report(Request $request): View
    {
        $restaurant = $request->user()->restaurant;
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        $orders = $restaurant->orders()
            ->with(['items', 'cashier'])
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$date, $date->copy()->endOfDay()])
            ->latest('paid_at')
            ->get();

        $methods = config('modules.payment_methods');

        return view('dashboard.cashier.report', [
            'restaurant' => $restaurant,
            'date' => $date,
            'orders' => $orders,
            'paymentMethods' => $methods,
            'summary' => [
                'count' => $orders->count(),
                'total' => (float) $orders->sum('total'),
                'discount' => (float) $orders->sum('discount'),
                'service' => (float) $orders->sum('service'),
                'tax' => (float) $orders->sum('tax'),
                'cashier_sales' => (float) $orders->where('source', 'cashier')->sum('total'),
                'menu_orders' => (float) $orders->where('source', 'menu')->sum('total'),
            ],
            'byMethod' => collect($methods)
                ->map(fn ($label, $key) => ['label' => $label, 'count' => $orders->where('payment_method', $key)->count(), 'total' => (float) $orders->where('payment_method', $key)->sum('total')])
                ->filter(fn ($row) => $row['count'] > 0),
        ]);
    }

    public function settings(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        return view('dashboard.cashier.settings', [
            'restaurant' => $restaurant,
            'cashier' => $restaurant->cashierSettings(),
            'allMethods' => config('modules.payment_methods'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        $data = $request->validate([
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => [Rule::in(array_keys(config('modules.payment_methods')))],
            'receipt_footer' => ['nullable', 'string', 'max:200'],
        ], [
            'payment_methods.required' => 'اختر طريقة دفع واحدة على الأقل.',
        ]);

        // allow_discount is granted by the super admin only, so it is not accepted here.
        $restaurant->saveCashierSettings($data);

        return back()->with('success', 'تم حفظ إعدادات الكاشير.');
    }
}
