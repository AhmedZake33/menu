<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PublicReservationController extends Controller
{
    public function store(Request $request, Restaurant $restaurant): RedirectResponse
    {
        abort_unless($restaurant->isAvailable() && $restaurant->hasModule('public_menu') && $restaurant->hasModule('reservations'), 404);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'customer_phone.regex' => 'اكتب رقم موبايل صحيح.',
        ]);

        $reservedAt = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time']);

        if ($reservedAt->isPast()) {
            throw ValidationException::withMessages(['time' => 'اختر موعدًا قادمًا، الموعد المكتوب فات.']);
        }

        if ($reservedAt->gt(now()->addMonths(6))) {
            throw ValidationException::withMessages(['date' => 'الحجز متاح حتى 6 شهور قادمة فقط.']);
        }

        $restaurant->reservations()->create([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'guests' => $data['guests'],
            'reserved_at' => $reservedAt,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تم استلام طلب الحجز يوم '.$reservedAt->format('Y/m/d').' الساعة '.$reservedAt->format('h:i A').'. سيتواصل معك المطعم للتأكيد.');
    }
}
