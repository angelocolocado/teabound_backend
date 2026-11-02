<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\CategoryPostRequest;
use App\Http\Requests\Menu\CategoryPutRequest;
use App\Http\Resources\Menu\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use function Pest\Laravel\json;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('view-any', Category::class);

        $data = CategoryResource::collection(Category::all());
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryPostRequest $request)
    {
        $incomingFields = $request->validated();

        $data = Category::create($incomingFields);

        return response()->json(new CategoryResource($data), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        Gate::authorize('view', $category);
        return response()->json(new CategoryResource($category));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryPutRequest $request, Category $category)
    {
        $incomingFields = $request->validated();

        $category->update($incomingFields);

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return response()->noContent();
    }
}
