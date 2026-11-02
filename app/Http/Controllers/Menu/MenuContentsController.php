<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\MenuContentPatchRequest;
use App\Http\Requests\Menu\MenuContentPostRequest;
use App\Http\Resources\Menu\MenuResource;
use App\Models\Menu;
use App\Models\MenuContent;
use Illuminate\Support\Facades\Gate;

class MenuContentsController extends Controller
{
    public function getMenuContentsOfMenu(Menu $menu)
    {
        Gate::authorize('view-any', MenuContent::class);

        $data = $menu->load(['menuContents.inventory', 'category']);

        return response()->json(new MenuResource($data));
    }

    public function addMenuContent(MenuContentPostRequest $request, Menu $menu)
    {
        $incomingFields = $request->validated();

        $data = $menu->menuContents()->create($incomingFields);

        return response()->json($data, 201);
    }

    public function patchMenuContent(MenuContentPatchRequest $request, MenuContent $menu_content)
    {
        $incomingFields = $request->validated();

        $menu_content->update($incomingFields);

        return response()->noContent();
    }

    public function destroyMenuContent(MenuContent $menu_content)
    {
        Gate::authorize('delete', $menu_content);

        $menu_content->delete();

        return response()->noContent();
    }
}
