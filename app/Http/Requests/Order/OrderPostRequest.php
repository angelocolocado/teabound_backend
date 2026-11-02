<?php

namespace App\Http\Requests\Order;

use App\Enum\PaymentTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class OrderPostRequest extends FormRequest
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
            'intent' => ['required', 'string', Rule::in(['calculate', 'check-out'])],
            'payment_method' => ['required_if:intent,check-out', 'string', new Enum(PaymentTypeEnum::class)],
            'gcash_reference_number' => [
                Rule::requiredIf(fn () => $this->intent === 'check-out' && $this->payment_method === 'gcash'),
                'nullable',
                'string',
                'max:100',
            ],
            'gcash_account_number' => ['sometimes', 'string', 'max:100'],
            'items' => ['required', 'array'],
            'items.*.product_option_id' => ['required', 'integer', Rule::exists('menu_options', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
