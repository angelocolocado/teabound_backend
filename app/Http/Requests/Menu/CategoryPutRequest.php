<?php

namespace App\Http\Requests\Menu;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoryPutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return Gate::allows('update', $category);
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => str($this->name)->lower()->trim()
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
            'name' => ['required', 'min:2', Rule::unique('categories', 'name')->ignore($this->user)]
        ];
    }
}
