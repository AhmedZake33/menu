<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveOfferRequest;
use App\Models\ActivityLog;
use App\Models\Offer;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    public function index()
    {
        $offers = request()->user()->restaurant->offers()->latest()->paginate(15);

        return view('dashboard.offers.index', compact('offers'));
    }

    public function create()
    {
        return view('dashboard.offers.form', ['offer' => new Offer(['is_active' => true, 'discount_type' => 'percent'])]);
    }

    public function store(SaveOfferRequest $request)
    {
        DB::transaction(function () use ($request) {
            $offer = $request->user()->restaurant->offers()->create($request->validated());
            $this->log($offer, 'created', null, $offer->toArray());
        });

        return redirect()->route('dashboard.offers.index')->with('success', 'تم إنشاء العرض.');
    }

    public function edit(Offer $offer)
    {
        $this->authorize('update', $offer);

        return view('dashboard.offers.form', compact('offer'));
    }

    public function update(SaveOfferRequest $request, Offer $offer)
    {
        $this->authorize('update', $offer);
        DB::transaction(function () use ($request, $offer) {
            $old = $offer->toArray();
            $offer->update($request->validated());
            $this->log($offer, 'updated', $old, $offer->fresh()->toArray());
        });

        return redirect()->route('dashboard.offers.index')->with('success', 'تم تحديث العرض.');
    }

    public function destroy(Offer $offer)
    {
        $this->authorize('delete', $offer);
        DB::transaction(function () use ($offer) {
            $this->log($offer, 'deleted', $offer->toArray(), null);
            $offer->delete();
        });

        return back()->with('success', 'تم حذف العرض.');
    }

    private function log(Offer $offer, string $action, ?array $old, ?array $new): void
    {
        ActivityLog::create([
            'user_id' => request()->user()->id, 'restaurant_id' => $offer->restaurant_id,
            'action' => 'offer.'.$action, 'subject_type' => Offer::class, 'subject_id' => $offer->id,
            'old_values' => $old, 'new_values' => $new, 'ip_address' => request()->ip(),
        ]);
    }
}
