<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::RestaurantAdmin
            && $this->user()->is_active && (bool) $this->user()->restaurant?->isAvailable();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', $this->input('discount_type') === 'percent' ? 'max:100' : 'max:99999999.99'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', ...($this->filled('starts_at') ? ['after_or_equal:starts_at'] : [])],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
