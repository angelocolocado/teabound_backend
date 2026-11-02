<?php

namespace App\Http\Requests\Menu;

use App\Enum\MenuOptionTypeEnum;
use App\Models\MenuOption;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MenuOptionPatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('menu_option'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => str($this->name)->lower()->trim()->toString(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $menuOption = $this->route('menu_option');

        return [
            'name' => [
                'sometimes',
                'string',
                'min:1',
                'max:100',
                Rule::unique('menu_options', 'name')
                    ->where(fn ($query) => $query
                        ->where('menu_id', $menuOption->menu_id)
                        ->where('type', $this->input('type', $menuOption->type->value)))
                    ->ignore($menuOption),
            ],
            'type' => ['sometimes', Rule::enum(MenuOptionTypeEnum::class)],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Prevent a partial update from violating the menu option identity constraint.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $menuOption = $this->route('menu_option');
                $name = $this->input('name', $menuOption->name);
                $type = $this->input('type', $menuOption->type->value);

                $duplicateExists = MenuOption::query()
                    ->where('menu_id', $menuOption->menu_id)
                    ->where('name', $name)
                    ->where('type', $type)
                    ->whereKeyNot($menuOption->id)
                    ->exists();

                if ($duplicateExists) {
                    $validator->errors()->add('name', 'This menu option already exists.');
                }
            },
        ];
    }
}
