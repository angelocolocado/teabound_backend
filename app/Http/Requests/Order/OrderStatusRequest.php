<?php

namespace App\Http\Requests\Order;

use App\Enum\OrderStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStatusRequest extends FormRequest
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
        return ['status' => ['required', Rule::in([
            OrderStatusEnum::preparing->value,
            OrderStatusEnum::ready->value,
            OrderStatusEnum::claimed->value,
        ])]];
    }
}
