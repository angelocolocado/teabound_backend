<?php

namespace App\Services;

use App\Enum\DiscountTypeEnum;
use App\Models\Coupon;
use InvalidArgumentException;

class CouponService
{
    /**
     * @return array{coupon: ?Coupon, discount: float}
     */
    public static function apply(?string $code, float $subtotal): array
    {
        if ($code === null || trim($code) === '') {
            return ['coupon' => null, 'discount' => 0.0];
        }

        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();

        if ($coupon === null || ! $coupon->is_active) {
            throw new InvalidArgumentException('The discount code is invalid.');
        }

        if ($coupon->expires_at !== null && $coupon->expires_at->isPast()) {
            throw new InvalidArgumentException('The discount code has expired.');
        }

        if ($subtotal < (float) $coupon->minimum_purchase) {
            throw new InvalidArgumentException('The order does not meet the minimum purchase for this discount code.');
        }

        $discount = $coupon->type === DiscountTypeEnum::percentage
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->discount_cap !== null) {
            $discount = min($discount, (float) $coupon->discount_cap);
        }

        return [
            'coupon' => $coupon,
            'discount' => min(round($discount, 2), $subtotal),
        ];
    }
}
