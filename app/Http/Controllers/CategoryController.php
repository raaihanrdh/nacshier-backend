<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $categories = Category::all();
            return $this->successResponse($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve categories', 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'description' => 'nullable|string',
            ]);

            $category = Category::create($validated);
            return $this->successResponse($category, 'Category created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create category: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $category_id)
    {
        try {
            $category = Category::findOrFail($category_id);
            return $this->successResponse($category, 'Category retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Category not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve category', 500);
        }
    }

    public function update(Request $request, string $category_id)
    {
        try {
            $category = Category::findOrFail($category_id);
            
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:categories,name,'.$category_id.',category_id',
                'description' => 'sometimes|nullable|string',
            ]);

            $category->update($validated);
            return $this->successResponse($category, 'Category updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Category not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update category: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $category_id)
    {
        try {
            $category = Category::findOrFail($category_id);
            $category->delete();

            return $this->successResponse(null, 'Category deleted permanently');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Category not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete category: ' . $e->getMessage(), 500);
        }
    }
    
    public function products(string $category_id)
    {
        try {
            $category = Category::findOrFail($category_id);
            $products = $category->products()->get();
            
            return $this->successResponse([
                'category' => $category->only(['category_id', 'name']),
                'products' => $products
            ], 'Category products retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Category not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve category products', 500);
        }
    }
}