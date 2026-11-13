<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\GooglePlaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function show(Restaurant $restaurant)
    {
        $restaurant->load('administrators:id,restaurant_id,name,email,is_active');

        return view('restaurants.show', compact('restaurant'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|max:150', 'slug' => 'required|alpha_dash|unique:restaurants', 'email' => 'nullable|email', 'expires_at' => 'nullable|date', 'ordering_enabled' => 'nullable|boolean', 'admin_name' => 'required|max:150', 'admin_email' => 'required|email|unique:users,email', 'password' => 'required|min:8', ...$this->clientRules()]);
        DB::transaction(function () use ($data, $request) {
            $r = Restaurant::create([...collect($data)->only(['name', 'slug', 'email', 'expires_at'])->all(), ...$this->clientSettings($request, new Restaurant)]);
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
        $data = $request->validate(['name' => 'required|max:150', 'slug' => 'required|alpha_dash|unique:restaurants,slug,'.$restaurant->id, 'email' => 'nullable|email', 'expires_at' => 'nullable|date', 'is_active' => 'nullable|boolean', 'ordering_enabled' => 'nullable|boolean', 'map_url' => 'nullable|url|max:2000', 'google_place_id' => 'nullable|string|max:255|regex:/^[A-Za-z0-9_:\-]+$/', ...$this->clientRules()]);
        $data['google_place_id'] = filled($data['google_place_id'] ?? null)
            ? $data['google_place_id']
            : app(GooglePlaceService::class)->resolve($data['map_url'] ?? $restaurant->map_url);
        $oldValues = $restaurant->only(['modules', 'settings', 'ordering_enabled', 'is_active']);
        $restaurant->update([...collect($data)->only(['name', 'slug', 'email', 'expires_at', 'map_url', 'google_place_id'])->all(), 'is_active' => $request->boolean('is_active'), ...$this->clientSettings($request, $restaurant)]);
        ActivityLog::create(['user_id' => $request->user()->id, 'restaurant_id' => $restaurant->id, 'action' => 'restaurant.client-settings.updated', 'subject_type' => Restaurant::class, 'subject_id' => $restaurant->id, 'old_values' => $oldValues, 'new_values' => $restaurant->only(['modules', 'settings', 'ordering_enabled', 'is_active']), 'ip_address' => $request->ip()]);

        return back()->with('success', 'تم حفظ التغييرات.');
    }

    /** Validation for the per-client settings block of the form. */
    private function clientRules(): array
    {
        return [
            'modules' => 'nullable|array',
            'modules.*' => 'boolean',
            // White text sits on this colour, so one too light to read on is refused.
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/', function (string $attribute, mixed $value, \Closure $fail) {
                [$r, $g, $b] = array_map(function (int $channel) {
                    $c = $channel / 255;

                    return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
                }, sscanf($value, '#%02x%02x%02x'));

                if (1.05 / (0.2126 * $r + 0.7152 * $g + 0.0722 * $b + 0.05) < 3.5) {
                    $fail('اللون المختار فاتح جدًا والكتابة البيضاء لن تُقرأ عليه. اختر لونًا أغمق.');
                }
            }],
            'limits' => 'nullable|array',
            'limits.*' => 'nullable|integer|min:0|max:100000',
            'cashier' => 'nullable|array',
            'cashier.tax_percent' => 'nullable|numeric|min:0|max:100',
            'cashier.service_percent' => 'nullable|numeric|min:0|max:100',
            'cashier.allow_discount' => 'nullable|boolean',
            'cashier.payment_methods' => 'nullable|array',
            'cashier.payment_methods.*' => [Rule::in(array_keys(config('modules.payment_methods')))],
            'cashier.receipt_footer' => 'nullable|string|max:200',
        ];
    }

    /**
     * Columns holding everything that is configured per client. Only what the request
     * actually carries is touched, so a partial form never resets the rest.
     */
    private function clientSettings(Request $request, Restaurant $restaurant): array
    {
        $out = ['ordering_enabled' => $request->boolean('ordering_enabled')];

        if ($request->has('modules')) {
            $modules = [];
            foreach (array_keys(config('modules.list')) as $key) {
                $modules[$key] = $request->boolean("modules.$key");
            }
            $out['modules'] = $modules;
            $out['ordering_enabled'] = $modules['orders'];
        }

        $settings = $restaurant->settings ?? [];

        if ($request->has('limits')) {
            foreach (array_keys(config('modules.limits')) as $key) {
                $settings['limits'][$key] = (int) $request->input("limits.$key", 0);
            }
        }

        if ($request->has('cashier')) {
            $settings['cashier'] = [
                ...($settings['cashier'] ?? []),
                'tax_percent' => (float) $request->input('cashier.tax_percent', 0),
                'service_percent' => (float) $request->input('cashier.service_percent', 0),
                'allow_discount' => $request->boolean('cashier.allow_discount'),
                'payment_methods' => array_values((array) $request->input('cashier.payment_methods', ['cash'])),
                'receipt_footer' => $request->input('cashier.receipt_footer'),
            ];
        }

        if ($request->filled('brand_color')) {
            $settings['brand_color'] = strtolower($request->input('brand_color'));
        }

        if ($request->has('limits') || $request->has('cashier') || $request->filled('brand_color')) {
            $out['settings'] = $settings;
        }

        return $out;
    }

    public function destroy(Restaurant $restaurant)
    {
        $restaurant->delete();

        return back()->with('success', 'نُقل المطعم إلى المحذوفات.');
    }
}
