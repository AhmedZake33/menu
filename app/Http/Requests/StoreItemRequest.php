<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Item::class);
    }

    public function rules(): array
    {
        $rid = $this->user()->restaurant_id;

        return ['category_id' => ['required', Rule::exists('categories', 'id')->where('restaurant_id', $rid)], 'name' => 'required|string|max:150', 'short_description' => 'nullable|string|max:500', 'price' => 'required|numeric|min:0', 'old_price' => 'nullable|numeric|gte:price', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072', 'is_available' => 'nullable|boolean', 'is_active' => 'nullable|boolean', 'is_featured' => 'nullable|boolean', 'is_new' => 'nullable|boolean', 'sort_order' => 'nullable|integer|min:0'];
    }
}
