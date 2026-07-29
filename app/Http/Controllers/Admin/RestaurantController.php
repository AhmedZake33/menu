<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $restaurants = Restaurant::withCount(['menuPages', 'categories', 'items', 'menuViews'])->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%$v%"))->latest()->paginate(15)->withQueryString();

        return view('admin.restaurants.index', compact('restaurants'));
    }

    public function create()
    {
        return view('admin.restaurants.form', ['restaurant' => new Restaurant]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|max:150', 'slug' => 'required|alpha_dash|unique:restaurants', 'email' => 'nullable|email', 'expires_at' => 'nullable|date', 'ordering_enabled' => 'nullable|boolean', 'admin_name' => 'required|max:150', 'admin_email' => 'required|email|unique:users,email', 'password' => 'required|min:8']);
        DB::transaction(function () use ($data, $request) {
            $r = Restaurant::create([...collect($data)->only(['name', 'slug', 'email', 'expires_at'])->all(), 'ordering_enabled' => $request->boolean('ordering_enabled')]);
            User::create(['name' => $data['admin_name'], 'email' => $data['admin_email'], 'password' => $data['password'], 'role' => UserRole::RestaurantAdmin, 'restaurant_id' => $r->id]);
            ActivityLog::create(['user_id' => $request->user()->id, 'restaurant_id' => $r->id, 'action' => 'restaurant.created', 'subject_type' => Restaurant::class, 'subject_id' => $r->id, 'new_values' => $r->toArray(), 'ip_address' => $request->ip()]);
        });

        return redirect()->route('admin.restaurants.index')->with('success', 'تم إنشاء المطعم وحساب المدير.');
    }

    public function edit(Restaurant $restaurant)
    {
        return view('admin.restaurants.form', compact('restaurant'));
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $data = $request->validate(['name' => 'required|max:150', 'slug' => 'required|alpha_dash|unique:restaurants,slug,'.$restaurant->id, 'email' => 'nullable|email', 'expires_at' => 'nullable|date', 'is_active' => 'nullable|boolean', 'ordering_enabled' => 'nullable|boolean']);
        $restaurant->update([...$data, 'is_active' => $request->boolean('is_active'), 'ordering_enabled' => $request->boolean('ordering_enabled')]);

        return back()->with('success', 'تم حفظ التغييرات.');
    }

    public function destroy(Restaurant $restaurant)
    {
        $restaurant->delete();

        return back()->with('success', 'نُقل المطعم إلى المحذوفات.');
    }
}
