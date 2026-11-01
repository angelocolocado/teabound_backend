<?php

namespace App\Http\Requests\Inventory;

use App\Enum\InventoryTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:100', 'string'],
            'type' => ['required', Rule::enum(InventoryTypeEnum::class)],
            'stock_in_pcs' => ['required', 'integer', 'min:0'],
        ];
    }
}
