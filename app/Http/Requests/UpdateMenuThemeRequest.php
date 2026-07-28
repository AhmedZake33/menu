<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('menuPage'));
    }

    public function rules(): array
    {
        $color = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];

        return [
            'primary_color' => $color, 'secondary_color' => $color, 'background_color' => $color, 'card_background_color' => $color,
            'text_color' => $color, 'heading_color' => $color, 'price_color' => $color, 'button_color' => $color, 'border_color' => $color,
            'font_family' => 'required|in:Tahoma,Arial,Cairo,Tajawal,system-ui', 'heading_font_family' => 'required|in:Tahoma,Arial,Cairo,Tajawal,system-ui',
            'layout_type' => 'required|in:grid,list', 'category_layout' => 'required|in:tabs,pills,buttons', 'item_card_style' => 'required|in:vertical,horizontal,minimal',
            'image_position' => 'required|in:top,right,left', 'image_shape' => 'required|in:rounded,square,circle',
            'items_per_row_desktop' => 'required|integer|between:1,4', 'items_per_row_tablet' => 'required|integer|between:1,3', 'items_per_row_mobile' => 'required|integer|between:1,2',
            'card_border_radius' => 'required|integer|between:0,40', 'card_shadow' => 'required|in:none,soft,medium,strong', 'content_width' => 'required|in:960px,1140px,1320px,100%',
            'show_item_images' => 'nullable|boolean', 'show_descriptions' => 'nullable|boolean', 'show_prices' => 'nullable|boolean', 'show_category_images' => 'nullable|boolean',
            'sticky_categories' => 'nullable|boolean', 'enable_search' => 'nullable|boolean', 'enable_category_filter' => 'nullable|boolean', 'enable_dark_mode' => 'nullable|boolean',
        ];
    }
}
