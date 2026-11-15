<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockUploadsWithoutFilesModule
{
    /**
     * Sits on every client dashboard route, so no upload field (present or added
     * later) can store a file for a client whose "files" module is switched off.
     */
    public function handle(Request $request, Closure $next)
    {
        $restaurant = $request->user()?->restaurant;

        // The image menu is its own paid option, so its uploads do not depend on "files".
        $imageMenuUpload = $request->routeIs('dashboard.menu-images.*') && $restaurant?->hasModule('image_menu');

        if ($request->allFiles() && ! $imageMenuUpload && ! $restaurant?->hasModule('files')) {
            abort(403, 'رفع الملفات غير متاح في اشتراك هذا المطعم.');
        }

        return $next($request);
    }
}
