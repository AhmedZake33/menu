<?php

namespace App\Http\Controllers;

use App\Mail\MenuOrderConfirmationMail;
use App\Models\Item;
use App\Models\MenuOrder;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicOrderController extends Controller
{
    public function store(Request $request, Restaurant $restaurant): RedirectResponse
    {
        abort_unless($restaurant->isAvailable() && $restaurant->ordering_enabled, 404);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['required', 'email', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'table_number' => ['required', 'integer', 'min:1', 'max:'.max(1, (int) $restaurant->tables_count)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($restaurant->tables_count < 1) {
            throw ValidationException::withMessages(['table_number' => 'الطلب غير متاح حاليًا لأن عدد الطاولات غير محدد.']);
        }

        $requested = collect($data['items'])
            ->mapWithKeys(fn ($row) => [(int) $row['id'] => (int) $row['quantity']])
            ->filter(fn ($quantity) => $quantity > 0);

        $menuItems = Item::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->whereIn('id', $requested->keys())
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== $requested->count()) {
            throw ValidationException::withMessages(['items' => 'بعض الأصناف غير متاحة حاليًا.']);
        }

        $order = DB::transaction(function () use ($restaurant, $data, $requested, $menuItems) {
            $subtotal = $menuItems->sum(fn (Item $item) => (float) $item->price * $requested[$item->id]);

            $order = MenuOrder::create([
                'restaurant_id' => $restaurant->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'table_number' => $data['table_number'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => $restaurant->currency,
                'confirmation_token' => (string) Str::uuid(),
            ]);

            foreach ($menuItems as $item) {
                $quantity = $requested[$item->id];
                $unitPrice = (float) $item->price;

                $order->items()->create([
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                ]);
            }

            return $order->load(['restaurant', 'items']);
        });

        try {
            Mail::to($order->customer_email)->send(new MenuOrderConfirmationMail($order));
            $order->update(['email_sent_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Menu order confirmation email failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('success', 'تم تسجيل طلبك رقم #'.$order->id.'، لكن تعذر إرسال إيميل التأكيد حاليًا.');
        }

        return back()->with('success', 'تم تسجيل طلبك رقم #'.$order->id.' وإرسال إيميل التأكيد.');
    }
}
