<?php

namespace App\Services;

use App\Models\MenuOption;

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
}
