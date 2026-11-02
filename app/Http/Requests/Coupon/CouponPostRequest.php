<?php

namespace App\Http\Requests\Coupon;

use App\Enum\DiscountTypeEnum;
use App\Models\Coupon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class CouponPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Coupon::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:coupons,code'],
            'type' => ['required', new Enum(DiscountTypeEnum::class)],
            'value' => ['required', 'numeric', 'gt:0'],
            'minimum_purchase' => ['sometimes', 'numeric', 'min:0'],
            'discount_cap' => ['nullable', 'numeric', 'gt:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
