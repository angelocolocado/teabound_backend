<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MenuPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Menu::class);
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => str($this->name)->lower()->trim(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'min:2', 'max:150', Rule::unique('menus', 'name')],
            'notes' => ['sometimes', 'string', 'min:2', 'max:255'],
        ];
    }
}
