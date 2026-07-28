<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRestaurantIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user?->isSuperAdmin() || $user?->restaurant?->isAvailable(), 403, 'اشتراك المطعم غير نشط أو منتهي.');

        return $next($request);
    }
}
