<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;
        $past = $request->query('show') === 'past';

        $reservations = $restaurant->reservations()
            ->when($past, fn ($q) => $q->where('reserved_at', '<', now()->startOfDay())->orderByDesc('reserved_at'))
            ->when(! $past, fn ($q) => $q->where('reserved_at', '>=', now()->startOfDay())->orderBy('reserved_at'))
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.reservations.index', [
            'reservations' => $reservations,
            'past' => $past,
            'pendingCount' => $restaurant->reservations()->where('status', 'pending')->where('reserved_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->restaurant_id === $request->user()->restaurant_id, 404);

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Reservation::STATUSES))]]);
        $reservation->update($data);

        return back()->with('success', 'تم تحديث حالة الحجز.');
    }
}
