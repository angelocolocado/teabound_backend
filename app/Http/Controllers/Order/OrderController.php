<?php

namespace App\Http\Controllers\Order;

use App\Enum\OrderStatusEnum;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderPostRequest;
use App\Http\Requests\Order\OrderStatusRequest;
use App\Http\Requests\Order\OrderVoidRequest;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Services\CheckoutService;
use App\Services\CouponService;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class OrderController extends Controller
{
    /** Calculate an order total from menu option prices and an optional discount. */
    public function calculate(OrderPostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $checkout = CheckoutService::calculateStrict($data['items']);
        $discount = CouponService::apply($data['discount_code'] ?? null, $checkout['subtotal']);

        return response()->json([
            'subtotal' => $checkout['subtotal'],
            'discount' => $discount['discount'],
            'total' => round($checkout['subtotal'] - $discount['discount'], 2),
        ]);
    }

    /** Persist an order, payment record, discount snapshot, and inventory deduction atomically. */
    public function checkout(OrderPostRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $order = DB::transaction(function () use ($data): Order {
                $checkout = CheckoutService::calculateStrict($data['items']);
                $discount = CouponService::apply($data['discount_code'] ?? null, $checkout['subtotal']);
                $total = round($checkout['subtotal'] - $discount['discount'], 2);

                InventoryService::deductInventory($data['items']);

                $order = Order::create([
                    'order_number' => 'ORD-'.now()->format('YmdHis').'-'.str()->upper(str()->random(4)),
                    'status' => OrderStatusEnum::preparing,
                    'subtotal' => $checkout['subtotal'],
                    'discount_amount' => $discount['discount'],
                    'total' => $total,
                    'coupon_id' => $discount['coupon']?->id,
                    'coupon_code' => $discount['coupon']?->code,
                ]);

                foreach ($data['items'] as $item) {
                    $option = $checkout['options'][$item['product_option_id']];
                    $quantity = (int) $item['quantity'];
                    $order->items()->create([
                        'menu_option_id' => $option->id,
                        'menu_name' => $option->menu->name,
                        'option_name' => $option->name,
                        'option_type' => $option->type->value,
                        'quantity' => $quantity,
                        'unit_price' => $option->price,
                        'line_total' => round($quantity * (float) $option->price, 2),
                    ]);
                }

                $order->paymentRecord()->create([
                    'payment_method' => $data['payment_method'],
                    'gcash_customer_number' => $data['gcash_customer_number']
                        ?? $data['gcash_account_number']
                        ?? null,
                    'gcash_reference_number' => $data['gcash_reference_number'] ?? null,
                    'amount' => $total,
                    'recorded_by' => auth()->id(),
                ]);

                return $order->load(['items', 'paymentRecord']);
            });
        } catch (InsufficientInventoryException|InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'shortages' => $exception instanceof InsufficientInventoryException
                    ? $exception->shortages
                    : [],
            ], 422);
        }

        return response()->json(['order' => $order], 201);
    }

    /** List orders, optionally filtered by all, preparing, ready, or claimed status. */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $status = $request->query('status');
        $query = Order::query()->with(['items', 'paymentRecord'])->latest();

        if ($status !== null && $status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json($query->get());
    }

    /** Update the preparation status of an order. */
    public function updateStatus(OrderStatusRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('update', $order);
        $order->update($request->validated());

        return response()->json($order->refresh());
    }

    /** Void an order and restore the inventory deducted during checkout. */
    public function void(OrderVoidRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('void', $order);

        if ($order->status === OrderStatusEnum::voided) {
            return response()->json(['message' => 'The order has already been voided.'], 422);
        }

        DB::transaction(function () use ($request, $order): void {
            InventoryService::restoreInventory($order);
            $order->update([
                'status' => OrderStatusEnum::voided,
                'void_reason' => $request->validated()['reason'],
                'voided_at' => now(),
                'voided_by' => auth()->id(),
            ]);
        });

        return response()->json($order->refresh());
    }

    /** Return sales, revenue, product, option, topping, and peak-hour analytics. */
    public function salesReport(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $orders = $this->filteredOrders($request)->with('items.menuOption')->get();
        $validOrders = $orders->where('status', '!=', OrderStatusEnum::voided);
        $items = $validOrders->flatMap->items;

        return response()->json([
            'sales' => [
                'orders' => $validOrders->count(),
                'items' => $items->sum('quantity'),
            ],
            'revenue' => [
                'subtotal' => round((float) $validOrders->sum('subtotal'), 2),
                'discounts' => round((float) $validOrders->sum('discount_amount'), 2),
                'total' => round((float) $validOrders->sum('total'), 2),
            ],
            'top_selling_items' => $items->groupBy('menu_name')->map(fn ($group, $name) => [
                'name' => $name,
                'quantity' => $group->sum('quantity'),
            ])->sortByDesc('quantity')->values(),
            'popular_sizes' => $this->popularByType($items, 'size'),
            'popular_toppings' => $this->popularByType($items, 'topping'),
            'peak_ordering_hours' => $validOrders->groupBy(fn (Order $order) => $order->created_at->format('H:00'))
                ->map(fn ($group, $hour) => ['hour' => $hour, 'orders' => $group->count()])
                ->sortByDesc('orders')->values(),
        ]);
    }

    /** Return payment records with optional date and payment-method filters. */
    public function paymentRecords(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);
        $query = PaymentRecord::query()->with('order')->latest();

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }
        if ($request->filled('gcash_reference_number')) {
            $query->where('gcash_reference_number', $request->string('gcash_reference_number'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->get());
    }

    private function filteredOrders(Request $request): Builder
    {
        $period = $request->query('period');
        $periodStart = match ($period) {
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };
        $query = Order::query()->where('created_at', '>=', $request->date('from') ?? $periodStart);

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }
        if ($request->filled('payment_method')) {
            $query->whereHas('paymentRecord', fn (Builder $payment) => $payment
                ->where('payment_method', $request->string('payment_method')));
        }

        return $query;
    }

    private function popularByType($items, string $type)
    {
        return $items->filter(fn ($item) => $item->option_type === $type)
            ->groupBy('option_name')
            ->map(fn ($group, $name) => ['name' => $name, 'quantity' => $group->sum('quantity')])
            ->sortByDesc('quantity')
            ->values();
    }
}
