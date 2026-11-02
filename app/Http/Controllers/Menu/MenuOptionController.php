<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\MenuOptionPatchRequest;
use App\Http\Requests\Menu\MenuOptionPostRequest;
use App\Http\Resources\Menu\MenuOptionResource;
use App\Models\Menu;
use App\Models\MenuOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MenuOptionController extends Controller
{
    /**
     * List the cup sizes and toppings configured for a menu.
     */
    public function index(Menu $menu): JsonResponse
    {
        Gate::authorize('viewAny', MenuOption::class);

        return response()->json(MenuOptionResource::collection(
            $menu->menuOptions()
                ->orderBy('type')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        ));
    }

    /**
     * Add a cup size or topping to a menu.
     */
    public function store(MenuOptionPostRequest $request, Menu $menu): JsonResponse
    {
        $menuOption = $menu->menuOptions()->create($request->validated());

        return response()->json(new MenuOptionResource($menuOption), 201);
    }

    /**
     * Display a configured cup size or topping.
     */
    public function show(MenuOption $menuOption): JsonResponse
    {
        Gate::authorize('view', $menuOption);

        return response()->json(new MenuOptionResource($menuOption));
    }

    /**
     * Update a configured cup size or topping.
     */
    public function update(MenuOptionPatchRequest $request, MenuOption $menuOption): Response
    {
        $menuOption->update($request->validated());

        return response()->noContent();
    }

    /**
     * Remove a configured cup size or topping.
     */
    public function destroy(MenuOption $menuOption): Response
    {
        Gate::authorize('delete', $menuOption);
        $menuOption->delete();

        return response()->noContent();
    }
}
