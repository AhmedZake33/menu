<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MenuImage;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** The pictures a client shows on their public link while they use the image menu. */
class MenuImageController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(): View
    {
        $restaurant = request()->user()->restaurant;

        return view('dashboard.menu-images', [
            'restaurant' => $restaurant,
            'images' => $restaurant->menuImages()->get(),
            'limit' => $restaurant->limit('max_menu_images'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'images.required' => 'اختر صورة واحدة على الأقل.',
            'images.max' => 'ارفع حتى 20 صورة في المرة الواحدة.',
            'images.*.image' => 'الملف لازم يكون صورة.',
            'images.*.mimes' => 'الصيغ المسموحة: JPG وPNG وWEBP.',
            'images.*.max' => 'حجم الصورة الواحدة لا يزيد عن 8 ميجا.',
        ]);

        $files = $request->file('images');
        $count = $restaurant->menuImages()->count();

        if ($restaurant->limit('max_menu_images') > 0 && $count + count($files) > $restaurant->limit('max_menu_images')) {
            $left = max(0, $restaurant->limit('max_menu_images') - $count);

            return back()->withErrors(['images' => 'الحد الأقصى لصور المنيو في اشتراكك '.$restaurant->limit('max_menu_images').' صورة، ومتبقي لك '.$left.'.']);
        }

        $order = (int) $restaurant->menuImages()->max('sort_order');

        foreach ($files as $file) {
            $restaurant->menuImages()->create([
                'path' => $this->images->replace($file, null, "restaurants/{$restaurant->id}/menu-images"),
                'sort_order' => ++$order,
            ]);
        }

        return back()->with('success', 'تم رفع '.count($files).' صورة.');
    }

    public function move(Request $request, MenuImage $menuImage): RedirectResponse
    {
        $this->own($menuImage);
        $direction = $request->validate(['direction' => 'required|in:up,down'])['direction'];

        DB::transaction(function () use ($menuImage, $direction) {
            $ids = $menuImage->restaurant->menuImages()->pluck('id')->all();
            $at = array_search($menuImage->id, $ids, true);
            $to = $direction === 'up' ? $at - 1 : $at + 1;

            if ($at === false || ! isset($ids[$to])) {
                return;
            }

            [$ids[$at], $ids[$to]] = [$ids[$to], $ids[$at]];

            foreach ($ids as $position => $id) {
                MenuImage::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back();
    }

    public function destroy(MenuImage $menuImage): RedirectResponse
    {
        $this->own($menuImage);
        $this->images->delete($menuImage->path);
        $menuImage->delete();

        return back()->with('success', 'تم حذف الصورة.');
    }

    /** Another client's picture must look like it does not exist. */
    private function own(MenuImage $menuImage): void
    {
        abort_unless($menuImage->restaurant_id === request()->user()->restaurant_id, 404);
    }
}
