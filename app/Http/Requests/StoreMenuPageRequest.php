<?php

namespace App\Http\Requests;

use App\Models\MenuPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MenuPage::class);
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:150', 'slug' => ['required', 'alpha_dash', 'max:150', Rule::unique('menu_pages')->where('restaurant_id', $this->user()->restaurant_id)], 'description' => 'nullable|string|max:2000', 'sort_order' => 'nullable|integer|min:0', 'is_default' => 'nullable|boolean', 'is_active' => 'nullable|boolean'];
    }
}
