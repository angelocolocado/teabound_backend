<?php

namespace App\Http\Controllers\Menu;

use App\Enum\MenuStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\MenuPatchRequest;
use App\Http\Requests\Menu\MenuPostRequest;
use App\Http\Resources\Menu\MenuResource;
use App\Models\Menu;
use Illuminate\Support\Facades\Gate;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('view-any', Menu::class);

        $data = MenuResource::collection(Menu::with(['category', 'menuOptions'])->get());

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MenuPostRequest $request)
    {
        $incomingFields = $request->validated();

        $data = Menu::create($incomingFields);

        return response()->json(new MenuResource($data), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        Gate::authorize('view', $menu);

        return response()->json(new MenuResource($menu->load(['category', 'menuOptions'])));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MenuPatchRequest $request, Menu $menu)
    {
        $incomingFields = $request->validated();

        $menu->update($incomingFields);

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        Gate::authorize('delete', $menu);

        $menu->delete();

        return response()->noContent();
    }

    /**
     * Mark the menu as available / active
     */
    public function publish(Menu $menu)
    {
        Gate::authorize('update', $menu);

        $menu->update([
            'status' => MenuStatusEnum::active,
        ]);

        return response()->noContent();
    }

    /**
     * Mark the menu as deactivated / inactive
     */
    public function deactivate(Menu $menu)
    {
        Gate::authorize('update', $menu);

        $menu->update([
            'status' => MenuStatusEnum::inactive,
        ]);

        return response()->noContent();
    }

    /**
     * Mark the menu as pulled-out
     */
    public function markPulledOut(Menu $menu)
    {
        Gate::authorize('update', $menu);

        $menu->update([
            'status' => MenuStatusEnum::pulled_out,
        ]);

        return response()->noContent();
    }

    /**
     * Manually mark the menu as out of stock
     */
    public function markOutOfStock(Menu $menu)
    {
        Gate::authorize('update', $menu);

        $menu->update([
            'status' => MenuStatusEnum::out_of_stock,
        ]);

        return response()->noContent();
    }
}
