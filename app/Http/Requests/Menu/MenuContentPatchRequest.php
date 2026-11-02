<?php

namespace App\Http\Requests\Menu;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MenuContentPatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $menuContent = $this->route('menu_content');

        return Gate::allows('update', $menuContent);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_id' => ['sometimes', 'integer',
                Rule::exists('inventories', 'id'),
                Rule::unique('menu_contents', 'inventory_id')
                    ->where(function ($query) {
                        return $query->where('menu_option_id', $this->route('menu_content')->menu_option_id);
                    })
                    ->ignore($this->route('menu_content'))],
            'name' => ['sometimes', 'max:100'],
        ];
    }
}
