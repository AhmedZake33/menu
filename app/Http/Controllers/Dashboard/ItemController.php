<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index()
    {
        $restaurant = request()->user()->restaurant;

        return view('dashboard.items.index', [
            'items' => $restaurant->items()->with(['category', 'menuPage'])->orderBy('sort_order')->paginate(20),
            'categories' => $restaurant->categories()->with('menuPage')->get(),
        ]);
    }

    public function store(StoreItemRequest $request)
    {
        $data = $request->validated();
        $category = Category::where('restaurant_id', $request->user()->restaurant_id)->findOrFail($data['category_id']);
        $data['image'] = $this->images->replace($request->file('image'), null, 'items');

        Item::create([
            ...$data,
            'restaurant_id' => $request->user()->restaurant_id,
            'menu_page_id' => $category->menu_page_id,
            ...$this->flags($request),
        ]);

        return back()->with('success', 'تم إنشاء الصنف.');
    }

    public function update(Request $request, Item $item)
    {
        $this->authorize('update', $item);

        $restaurantId = $request->user()->restaurant_id;
        $data = $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            'name' => 'required|max:150',
            'short_description' => 'nullable|max:500',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|gte:price',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = Category::where('restaurant_id', $restaurantId)->findOrFail($data['category_id']);
        $data['image'] = $this->images->replace($request->file('image'), $item->image, 'items');

        $item->update([
            ...$data,
            'menu_page_id' => $category->menu_page_id,
            ...$this->flags($request),
        ]);

        return back()->with('success', 'تم تحديث الصنف.');
    }

    public function destroyImage(Item $item)
    {
        $this->authorize('update', $item);

        $this->images->delete($item->image);
        $item->update(['image' => null]);

        return back()->with('success', 'تم حذف صورة الصنف.');
    }

    public function destroy(Item $item)
    {
        $this->authorize('delete', $item);

        $this->images->delete($item->image);
        $item->delete();

        return back()->with('success', 'تم حذف الصنف.');
    }

    private function flags(Request $request): array
    {
        return [
            'is_active' => $request->boolean('is_active'),
            'is_available' => $request->boolean('is_available'),
            'is_featured' => $request->boolean('is_featured'),
            'is_new' => $request->boolean('is_new'),
        ];
    }
}
