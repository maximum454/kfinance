<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tags = Tag::where('user_id', $request->user()->id)->get();

        return response()->json($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag = Tag::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
        ]);

        return response()->json($tag, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tag = $this->findOwn($request, $id);

        return response()->json($tag);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tag = $this->findOwn($request, $id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag->update($validated);

        return response()->json($tag);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tag = $this->findOwn($request, $id);

        $tag->delete();

        return response()->json(['message' => 'Тег удалён.']);
    }

    private function findOwn(Request $request, int $id): Tag
    {
        $tag = Tag::where('user_id', $request->user()->id)->find($id);

        if (! $tag) {
            throw ValidationException::withMessages([
                'id' => ['Тег не найден.'],
            ]);
        }

        return $tag;
    }
}