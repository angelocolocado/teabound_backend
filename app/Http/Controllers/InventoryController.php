<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventory\InventoryCreateRequest;
use App\Http\Requests\Inventory\InventoryUpdateRequest;
use App\Http\Resources\Inventory\InventoryResource;
use App\Models\AuditLog;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    public function index()
    {
        return response()->json(InventoryResource::collection(Inventory::all()));
    }

    public function lowStock()
    {
        return response()->json(InventoryResource::collection(
            Inventory::query()
                ->whereColumn('stock_in_pcs', '<=', 'low_stock_threshold')
                ->orderBy('name')
                ->get()
        ));
    }

    public function show(Inventory $inventory)
    {
        return response()->json(new InventoryResource($inventory));
    }

    public function store(InventoryCreateRequest $request)
    {
        Gate::authorize('create', Inventory::class);

        $incomingFields = $request->validated();

        $inventory = Inventory::create($incomingFields);

        return response()->json(new InventoryResource($inventory), 201);
    }

    public function update(InventoryUpdateRequest $request, Inventory $inventory)
    {
        Gate::authorize('update', $inventory);

        $incomingFields = $request->validated();

        $inventory->update($incomingFields);

        return response()->noContent(200);
    }

    public function updateStock(Request $request, Inventory $inventory)
    {
        Gate::authorize('update', $inventory);

        $incomingFields = $request->validate([
            'stock_in_pcs' => 'required|min:0|integer',
        ]);

        $inventory->update($incomingFields);
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'inventory.adjust-stock',
            'description' => 'Inventory stock for '.$inventory->name.' was adjusted.',
            'auditable_type' => Inventory::class,
            'auditable_id' => $inventory->id,
            'metadata' => ['stock_in_pcs' => $inventory->stock_in_pcs],
        ]);

        return response()->noContent(200);
    }

    public function destroy(Inventory $inventory)
    {
        Gate::authorize('delete', $inventory);

        $inventory->delete();

        return response()->noContent(200);
    }
}
