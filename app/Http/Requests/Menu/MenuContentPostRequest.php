<?php

namespace App\Http\Requests\Menu;

use App\Models\MenuContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MenuContentPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', MenuContent::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_id' => ['required', 'integer',
                Rule::exists('inventories', 'id'),
                Rule::unique('menu_contents', 'inventory_id')->where(function ($query) {
                    return $query->where('menu_option_id', $this->route('menu_option')->id);
                })],
            'name' => ['required', 'string'],
            'stock_to_deduct_in_pcs' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
