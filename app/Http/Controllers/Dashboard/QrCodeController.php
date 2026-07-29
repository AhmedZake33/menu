<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MenuPage;
use App\Models\Restaurant;
use App\Services\QrCodeService;

class QrCodeController extends Controller
{
    public function restaurant(string $format, QrCodeService $service)
    {
        abort_unless(in_array($format, ['png', 'svg']), 404);

        /** @var Restaurant $restaurant */
        $restaurant = request()->user()->restaurant;
        $url = route('public.restaurant', $restaurant);
        $format = $service->format($format);
        $body = $service->render($url, $format);

        return response($body)
            ->header('Content-Type', $format === 'png' ? 'image/png' : 'image/svg+xml')
            ->header('Content-Disposition', 'inline; filename="'.$restaurant->slug.'-full-menu.'.$format.'"');
    }

    public function __invoke(MenuPage $menuPage, string $format, QrCodeService $service)
    {
        $this->authorize('view', $menuPage);
        abort_unless(in_array($format, ['png', 'svg']), 404);
        $url = $menuPage->is_default
            ? route('public.restaurant', $menuPage->restaurant)
            : route('public.menu', [$menuPage->restaurant, $menuPage]);
        $format = $service->format($format);
        $body = $service->render($url, $format);

        return response($body)->header('Content-Type', $format === 'png' ? 'image/png' : 'image/svg+xml')->header('Content-Disposition', 'inline; filename="'.$menuPage->slug.'.'.$format.'"');
    }
}
