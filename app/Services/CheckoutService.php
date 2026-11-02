<?php

namespace App\Services;

use App\Models\MenuOption;
use InvalidArgumentException;

class CheckoutService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public static function calculate(array $items = []): array|float
    {
        if (count($items) == 0) {
            return 0.00;
        }

        $optionsId = array_column($items, 'product_option_id');
        $menuOptions = MenuOption::whereIn('id', $optionsId)->get()->keyBy('id');

        $total = 0.00;
        $inactiveItems = 0;

        foreach ($items as $item) {
            $option = $menuOptions->get($item['product_option_id']);

            if ($option) {
                if (! $option->is_active) {
                    $inactiveItems++;

                    continue;
                }

                $total += $item['quantity'] * $option->price;
            }

        }

        if ($inactiveItems == 0) {
            return [
                'total' => $total,
            ];
        }

        return [
            'total' => $total,
            'message' => "$inactiveItems inactive items were excluded from the total.",
        ];
    }

    /**
     * @param  array<int, array{product_option_id: int, quantity: int}>  $items
     * @return array{subtotal: float, options: array<int, MenuOption>}
     */
    public static function calculateStrict(array $items): array
    {
        $optionsId = array_column($items, 'product_option_id');
        $menuOptions = MenuOption::query()->with('menu')->whereIn('id', $optionsId)->get()->keyBy('id');
        $subtotal = 0.0;

        foreach ($items as $item) {
            $option = $menuOptions->get((int) $item['product_option_id']);

            if ($option === null || ! $option->is_active) {
                throw new InvalidArgumentException('One or more selected menu options are invalid or inactive.');
            }

            $subtotal += (int) $item['quantity'] * (float) $option->price;
        }

        return ['subtotal' => round($subtotal, 2), 'options' => $menuOptions->all()];
    }
}
