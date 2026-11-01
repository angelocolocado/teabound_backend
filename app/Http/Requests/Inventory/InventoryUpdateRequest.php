<?php

namespace App\Http\Requests\Inventory;

use App\Enum\InventoryTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryUpdateRequest extends FormRequest
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
            'name' => ['sometimes', 'min:2', 'max:100', 'string'],
            'type' => ['sometimes', Rule::enum(InventoryTypeEnum::class)],
            // 'stock_in_pcs' => ['sometimes', 'integer', 'min:0'] SHOULD NOT UPDATE ALONG WITH THEM, The stock_in_pcs have its own dedicated endpoint.
        ];
    }
}
