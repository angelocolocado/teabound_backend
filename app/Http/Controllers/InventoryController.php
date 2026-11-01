<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventory\InventoryCreateRequest;
use App\Http\Requests\Inventory\InventoryUpdateRequest;
use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Ramsey\Uuid\Exception\UnsupportedOperationException;

class InventoryController extends Controller
{

    public function index()
    {
        return response()->json(InventoryResource::collection(Inventory::all()));
    }

    public function show(Inventory $inventory)
    {
        return response()->json(new InventoryResource($inventory));
    }

    public function store(InventoryCreateRequest $request){
        Gate::authorize('create', Inventory::class);

        $incomingFields = $request->validated();

        Inventory::create($incomingFields);

        return response()->noContent(201);
    }

    public function update(InventoryUpdateRequest $request, Inventory $inventory){
        Gate::authorize('update', $inventory);

        $incomingFields = $request->validated();

        $inventory->update($incomingFields);

        return response()->noContent(200);
    }

    public function updateStock(Request $request, Inventory $inventory)
    {
        Gate::authorize('update', $inventory);

        $incomingFields = $request->validate([
            'stock_in_pcs' => 'required|min:0|integer'
        ]);

        $inventory->update($incomingFields);

        return response()->noContent(200);
    }

    public function destroy(Inventory $inventory){
        Gate::authorize('delete', $inventory);

        $inventory->delete();

        return response()->noContent(200);
    }
}
