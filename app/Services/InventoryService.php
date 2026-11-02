<?php

namespace App\Services;

use App\Exceptions\InsufficientInventoryException;
use App\Models\Inventory;
use App\Models\MenuOption;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Deduct the inventory required by the selected menu options.
     *
     * @param  array<int, array{product_option_id: int, quantity: int}>  $items
     * @return array{deducted: array<int, int>}
     *
     * @throws InsufficientInventoryException
     */
    public static function deductInventory(array $items = []): array
    {
        if ($items === []) {
            return ['deducted' => []];
        }

        $quantitiesByOption = [];

        foreach ($items as $item) {
            $optionId = (int) ($item['product_option_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($optionId < 1 || $quantity < 1) {
                throw new InvalidArgumentException('Each order item must have a valid option and quantity.');
            }

            $quantitiesByOption[$optionId] = ($quantitiesByOption[$optionId] ?? 0) + $quantity;
        }

        return DB::transaction(function () use ($quantitiesByOption): array {
            $menuOptions = MenuOption::query()
                ->whereIn('id', array_keys($quantitiesByOption))
                ->with('menuContents.inventory')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($menuOptions->count() !== count($quantitiesByOption)) {
                throw new InvalidArgumentException('One or more selected menu options do not exist.');
            }

            $requiredByInventory = [];

            foreach ($quantitiesByOption as $optionId => $quantity) {
                $option = $menuOptions->get($optionId);

                if (! $option->is_active) {
                    throw new InvalidArgumentException("Menu option {$option->id} is inactive.");
                }

                foreach ($option->menuContents as $content) {
                    if ($content->inventory === null) {
                        throw new InvalidArgumentException("Menu content {$content->id} has no inventory record.");
                    }

                    $requiredByInventory[$content->inventory_id] =
                        ($requiredByInventory[$content->inventory_id] ?? 0)
                        + ($content->stock_to_deduct_in_pcs * $quantity);
                }

            }

            $inventories = Inventory::query()
                ->whereIn('id', array_keys($requiredByInventory))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $shortages = [];

            foreach ($requiredByInventory as $inventoryId => $required) {
                $inventory = $inventories->get($inventoryId);

                if ($inventory === null || $inventory->stock_in_pcs < $required) {
                    $shortages[] = [
                        'inventory_id' => (int) $inventoryId,
                        'required' => $required,
                        'available' => $inventory?->stock_in_pcs ?? 0,
                    ];
                }
            }

            if ($shortages !== []) {
                throw new InsufficientInventoryException($shortages);
            }

            foreach ($requiredByInventory as $inventoryId => $required) {
                $inventories->get($inventoryId)->decrement('stock_in_pcs', $required);
            }

            return ['deducted' => $requiredByInventory];
        });
    }

    public static function restoreInventory(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $requirements = [];
            $order->loadMissing('items.menuOption.menuContents');

            foreach ($order->items as $item) {
                foreach ($item->menuOption->menuContents as $content) {
                    $requirements[$content->inventory_id] =
                        ($requirements[$content->inventory_id] ?? 0)
                        + ($content->stock_to_deduct_in_pcs * $item->quantity);
                }
            }

            $inventories = Inventory::query()
                ->whereIn('id', array_keys($requirements))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requirements as $inventoryId => $quantity) {
                $inventories->get($inventoryId)?->increment('stock_in_pcs', $quantity);
            }
        });
    }
}
