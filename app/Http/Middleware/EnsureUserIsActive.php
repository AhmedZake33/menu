<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->is_active, 403, 'الحساب غير نشط.');

        return $next($request);
    }
}
