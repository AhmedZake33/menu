<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\MenuPageService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Self-service sign-up for a restaurant or cafe owner: one form creates the restaurant,
 * its manager account and a free trial that ends on its own (expires_at).
 */
class RegisteredUserController extends Controller
{
    public function __construct(private MenuPageService $pages) {}

    public function create(): View
    {
        $this->ensureSignupIsOpen();

        return view('auth.register', [
            'trialDays' => $this->trialDays(),
            'modes' => config('modules.menu_modes'),
            'maxImages' => (int) config('modules.signup.max_menu_images'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSignupIsOpen();

        // The link is typed in Latin letters and is case-insensitive.
        $request->merge(['slug' => Str::lower(trim((string) $request->input('slug')))]);

        $data = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('restaurants', 'slug')],
            'phone' => ['required', 'string', 'max:30'],
            'menu_mode' => ['required', Rule::in(array_keys(config('modules.menu_modes')))],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'slug.regex' => 'الرابط حروف إنجليزية صغيرة وأرقام وشرطة (-) فقط، مثل: cafe-mocha',
            'slug.unique' => 'الرابط ده مستخدم قبل كده، جرّب اسم تاني.',
            'email.unique' => 'البريد ده مسجل بالفعل. لو ده حسابك سجّل دخول.',
        ]);

        $user = DB::transaction(function () use ($data, $request) {
            $days = $this->trialDays();

            $restaurant = Restaurant::create([
                'name' => $data['restaurant_name'],
                'slug' => $data['slug'],
                'phone' => $data['phone'],
                'menu_mode' => $data['menu_mode'],
                'is_active' => true,
                'expires_at' => now()->addDays($days),
                'settings' => [
                    'trial' => true,
                    'signup' => ['source' => 'self', 'trial_days' => $days, 'at' => now()->toIso8601String()],
                    'limits' => ['max_menu_images' => (int) config('modules.signup.max_menu_images')],
                ],
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::RestaurantAdmin,
                'restaurant_id' => $restaurant->id,
                'is_active' => true,
            ]);

            // A ready first page, so the public link works as soon as they add items and
            // switching later from the image menu to the digital one starts from something.
            $this->pages->create($restaurant->id, ['name' => 'المنيو', 'slug' => 'menu', 'is_default' => true, 'is_active' => true, 'sort_order' => 0]);

            ActivityLog::create([
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'action' => 'restaurant.registered',
                'subject_type' => Restaurant::class,
                'subject_id' => $restaurant->id,
                'description' => 'تسجيل ذاتي مع فترة تجريبية '.$days.' يوم.',
                'new_values' => $restaurant->only(['name', 'slug', 'phone', 'menu_mode', 'expires_at']),
                'ip_address' => $request->ip(),
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard.home')->with('success', 'أهلًا بك! بدأت فترتك التجريبية المجانية ('.$this->trialDays().' يوم).');
    }

    private function ensureSignupIsOpen(): void
    {
        abort_unless(config('modules.signup.enabled'), 404);
    }

    private function trialDays(): int
    {
        return max(1, (int) config('modules.signup.trial_days'));
    }
}
