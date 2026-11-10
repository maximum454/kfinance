<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where('user_id', $request->user()->id)->get();

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:expense,income',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
        ]);

        $category = Category::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
        ]);

        return response()->json($category, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $category = $this->findOwn($request, $id);

        return response()->json($category);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = $this->findOwn($request, $id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'string|in:expense,income',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = $this->findOwn($request, $id);

        $category->delete();

        return response()->json(['message' => 'Категория удалена.']);
    }

    private function findOwn(Request $request, int $id): Category
    {
        $category = Category::where('user_id', $request->user()->id)->find($id);

        if (! $category) {
            throw ValidationException::withMessages([
                'id' => ['Категория не найдена.'],
            ]);
        }

        return $category;
    }
}