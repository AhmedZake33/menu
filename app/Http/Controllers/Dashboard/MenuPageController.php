<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuPageRequest;
use App\Models\MenuPage;
use App\Services\MenuPageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuPageController extends Controller
{
    public function __construct(private MenuPageService $service) {}

    public function index()
    {
        return view('dashboard.menu-pages.index', ['pages' => request()->user()->restaurant->menuPages()->withCount(['categories', 'items', 'views'])->orderBy('sort_order')->get()]);
    }

    public function create()
    {
        return view('dashboard.menu-pages.form', ['menuPage' => new MenuPage]);
    }

    public function store(StoreMenuPageRequest $request)
    {
        $this->service->create($request->user()->restaurant_id, $this->booleans($request->validated(), $request));

        return redirect()->route('dashboard.menu-pages.index')->with('success', 'تم إنشاء صفحة المنيو.');
    }

    public function edit(MenuPage $menuPage)
    {
        $this->authorize('update', $menuPage);

        return view('dashboard.menu-pages.form', compact('menuPage'));
    }

    public function update(Request $request, MenuPage $menuPage)
    {
        $this->authorize('update', $menuPage);
        $data = $request->validate(['name' => 'required|max:150', 'slug' => ['required', 'alpha_dash', Rule::unique('menu_pages')->where('restaurant_id', $request->user()->restaurant_id)->ignore($menuPage->id)], 'description' => 'nullable|max:2000', 'sort_order' => 'integer|min:0', 'is_default' => 'nullable|boolean', 'is_active' => 'nullable|boolean']);
        $this->service->update($menuPage, $this->booleans($data, $request));

        return back()->with('success', 'تم حفظ الصفحة.');
    }

    public function destroy(MenuPage $menuPage)
    {
        $this->authorize('delete', $menuPage);
        $menuPage->delete();

        return back()->with('success', 'تم حذف الصفحة.');
    }

    private function booleans(array $data, Request $request): array
    {
        return [...$data, 'is_default' => $request->boolean('is_default'), 'is_active' => $request->boolean('is_active')];
    }
}
