<?php

namespace App\Http\Requests\Coupon;

use App\Enum\DiscountTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CouponPatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('coupon'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($this->route('coupon'))],
            'type' => ['sometimes', new Enum(DiscountTypeEnum::class)],
            'value' => ['sometimes', 'numeric', 'gt:0'],
            'minimum_purchase' => ['sometimes', 'numeric', 'min:0'],
            'discount_cap' => ['nullable', 'numeric', 'gt:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
