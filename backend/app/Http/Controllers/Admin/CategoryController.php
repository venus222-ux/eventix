<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(
            Category::withCount('events')->orderBy('name')->get()
        );
    }

    public function store(CategoryRequest $request)
    {
        return CategoryResource::make(Category::create($request->validated()))
            ->response()->setStatusCode(201);
    }

    public function show(Category $category)
    {
        return CategoryResource::make($category->loadCount('events'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return CategoryResource::make($category);
    }

    public function destroy(Category $category)
    {
        if ($category->events()->withTrashed()->exists()) {
            return response()->json(['message' => 'Category has events and cannot be deleted'], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
