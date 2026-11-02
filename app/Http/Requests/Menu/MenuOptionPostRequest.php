<?php

namespace App\Http\Requests\Menu;

use App\Enum\MenuOptionTypeEnum;
use App\Models\MenuOption;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MenuOptionPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', MenuOption::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => str($this->name)->lower()->trim()->toString(),
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
            'name' => [
                'required',
                'string',
                'min:1',
                'max:100',
                Rule::unique('menu_options', 'name')->where(fn ($query) => $query
                    ->where('menu_id', $this->route('menu')->id)
                    ->where('type', $this->input('type'))),
            ],
            'type' => ['required', Rule::enum(MenuOptionTypeEnum::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
