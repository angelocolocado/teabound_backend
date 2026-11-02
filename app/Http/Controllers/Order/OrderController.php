<?php

namespace App\Http\Controllers\Order;

use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderPostRequest;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class OrderController extends Controller
{
    /**
     * Calculate an order total from menu option prices.
     */
    public function calculate(OrderPostRequest $request): JsonResponse
    {

        $incomingFields = $request->validated();

        return response()->json(CheckoutService::calculate($incomingFields['items']));

    }

    /**
     * Deduct inventory for an order after calculating its total.
     */
    public function checkout(OrderPostRequest $request): JsonResponse
    {

        $incomingFields = $request->validated();

        $checkoutResponse = CheckoutService::calculate($incomingFields['items']);

        try {
            $inventoryResponse = InventoryService::deductInventory($incomingFields['items']);
        } catch (InsufficientInventoryException|InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'shortages' => $exception instanceof InsufficientInventoryException
                    ? $exception->shortages
                    : [],
            ], 422);
        }

        return response()->json([
            'checkout' => $checkoutResponse,
            'inventory' => $inventoryResponse,
        ]);
    }
}
