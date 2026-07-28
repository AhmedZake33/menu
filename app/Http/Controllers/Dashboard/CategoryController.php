<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index()
    {
        $restaurant = request()->user()->restaurant;

        return view('dashboard.categories.index', [
            'categories' => $restaurant->categories()->with('menuPage')->withCount('items')->orderBy('sort_order')->paginate(20),
            'pages' => $restaurant->menuPages()->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        $data['image'] = $this->images->replace($request->file('image'), null, 'categories');

        Category::create([
            ...$data,
            'restaurant_id' => $request->user()->restaurant_id,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'تم إنشاء التصنيف.');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $restaurantId = $request->user()->restaurant_id;
        $data = $request->validate([
            'menu_page_id' => ['required', Rule::exists('menu_pages', 'id')->where('restaurant_id', $restaurantId)],
            'name' => 'required|max:150',
            'slug' => 'required|alpha_dash',
            'description' => 'nullable|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['image'] = $this->images->replace($request->file('image'), $category->image, 'categories');
        $category->update([...$data, 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'تم تحديث التصنيف.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $this->images->delete($category->image);
        $category->delete();

        return back()->with('success', 'تم حذف التصنيف.');
    }
}
