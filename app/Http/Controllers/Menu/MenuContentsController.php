<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\MenuContentPatchRequest;
use App\Http\Requests\Menu\MenuContentPostRequest;
use App\Http\Resources\Menu\MenuContentResource;
use App\Models\Menu;
use App\Models\MenuContent;
use App\Models\MenuOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MenuContentsController extends Controller
{
    /**
     * List the inventory contents required by a menu option.
     */
    public function index(Menu $menu, MenuOption $menuOption): JsonResponse
    {
        Gate::authorize('viewAny', MenuContent::class);

        return response()->json(MenuContentResource::collection(
            $menuOption->menuContents()->with('inventory')->get()
        ));
    }

    /**
     * Add an inventory content requirement to a menu option.
     */
    public function store(MenuContentPostRequest $request, Menu $menu, MenuOption $menuOption): JsonResponse
    {
        $data = $menuOption->menuContents()->create($request->validated());

        return response()->json(new MenuContentResource($data->load('inventory')), 201);
    }

    /**
     * Update an inventory content requirement.
     */
    public function update(MenuContentPatchRequest $request, MenuContent $menuContent): Response
    {
        $menuContent->update($request->validated());

        return response()->noContent();
    }

    /**
     * Remove an inventory content requirement.
     */
    public function destroy(MenuContent $menuContent): Response
    {
        Gate::authorize('delete', $menuContent);

        $menuContent->delete();

        return response()->noContent();
    }
}
