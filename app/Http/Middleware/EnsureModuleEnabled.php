<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureModuleEnabled
{
    /**
     * A module the client does not have must look like it does not exist, so the
     * answer is 404 rather than 403.
     */
    public function handle(Request $request, Closure $next, string $module)
    {
        abort_unless($request->user()?->restaurant?->hasModule($module), 404);

        return $next($request);
    }
}
