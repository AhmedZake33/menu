<?php

namespace App\Http\Controllers;

use App\Mail\MenuOrderConfirmationMail;
use App\Mail\MenuOrderVerificationCodeMail;
use App\Models\Item;
use App\Models\MenuOrder;
use App\Models\PendingMenuOrder;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicOrderController extends Controller
{
    public function sendCode(Request $request, Restaurant $restaurant): RedirectResponse|JsonResponse
    {
        [$data, $requested, $menuItems] = $this->validatedOrderData($request, $restaurant);

        $code = (string) random_int(100000, 999999);
        $pending = PendingMenuOrder::create([
            'restaurant_id' => $restaurant->id,
            'token' => (string) Str::uuid(),
            'email' => $data['customer_email'],
            'code_hash' => Hash::make($code),
            'payload' => [
                'data' => $data,
                'items' => $requested->all(),
            ],
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($pending->email)->send(new MenuOrderVerificationCodeMail($restaurant, $code));
        } catch (\Throwable $exception) {
            $pending->delete();

            Log::warning('Menu order verification email failed.', [
                'restaurant_id' => $restaurant->id,
                'email' => $data['customer_email'],
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages(['customer_email' => 'تعذر إرسال كود التأكيد حاليًا. تأكد من الإيميل وحاول مرة أخرى.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم إرسال كود التأكيد إلى '.$pending->email.'.',
                'token' => $pending->token,
            ]);
        }

        return back()->with('success', 'تم إرسال كود التأكيد إلى '.$pending->email.'.');
    }

    public function confirm(Request $request, Restaurant $restaurant): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'verification_token' => ['required', 'uuid'],
            'verification_code' => ['required', 'digits:6'],
        ]);

        abort_unless($restaurant->isAvailable() && $restaurant->ordering_enabled, 404);

        $pending = PendingMenuOrder::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('token', $validated['verification_token'])
            ->first();

        if (! $pending || $pending->expires_at->isPast()) {
            $pending?->delete();
            throw ValidationException::withMessages(['verification_code' => 'كود التأكيد منتهي أو غير صحيح. اطلب كود جديد.']);
        }

        if (! Hash::check($validated['verification_code'], $pending->code_hash)) {
            throw ValidationException::withMessages(['verification_code' => 'كود التأكيد غير صحيح.']);
        }

        $payload = $pending->payload;
        $data = $payload['data'];
        $requested = collect($payload['items'])->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity]);
        $menuItems = $this->availableItems($restaurant, $requested);

        if ($menuItems->count() !== $requested->count()) {
            throw ValidationException::withMessages(['items' => 'بعض الأصناف لم تعد متاحة حاليًا. اطلب كود جديد بعد تعديل الطلب.']);
        }

        $order = $this->createOrder($restaurant, $data, $requested, $menuItems);
        $pending->delete();

        try {
            Mail::to($order->customer_email)->send(new MenuOrderConfirmationMail($order));
            $order->update(['email_sent_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Menu order confirmation email failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $message = 'تم تأكيد وتسجيل طلبك رقم #'.$order->id.'.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'order_id' => $order->id]);
        }

        return back()->with('success', $message);
    }

    private function validatedOrderData(Request $request, Restaurant $restaurant): array
    {
        abort_unless($restaurant->isAvailable() && $restaurant->ordering_enabled, 404);

        if ($restaurant->tables_count < 1) {
            throw ValidationException::withMessages(['table_number' => 'الطلب غير متاح حاليًا لأن عدد الطاولات غير محدد.']);
        }

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

        $requested = collect($data['items'])
            ->mapWithKeys(fn ($row) => [(int) $row['id'] => (int) $row['quantity']])
            ->filter(fn ($quantity) => $quantity > 0);

        $menuItems = $this->availableItems($restaurant, $requested);

        if ($menuItems->count() !== $requested->count()) {
            throw ValidationException::withMessages(['items' => 'بعض الأصناف غير متاحة حاليًا.']);
        }

        return [$data, $requested, $menuItems];
    }

    private function availableItems(Restaurant $restaurant, $requested)
    {
        return Item::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->whereIn('id', $requested->keys())
            ->get()
            ->keyBy('id');
    }

    private function createOrder(Restaurant $restaurant, array $data, $requested, $menuItems): MenuOrder
    {
        return DB::transaction(function () use ($restaurant, $data, $requested, $menuItems) {
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
    }
}
