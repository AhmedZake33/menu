<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    public function rules(): array
    {
        $rid = $this->user()->restaurant_id;

        return ['menu_page_id' => ['required', Rule::exists('menu_pages', 'id')->where('restaurant_id', $rid)], 'name' => 'required|string|max:150', 'slug' => 'required|alpha_dash|max:150', 'description' => 'nullable|string|max:2000', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072', 'sort_order' => 'nullable|integer|min:0', 'is_active' => 'nullable|boolean'];
    }
}
