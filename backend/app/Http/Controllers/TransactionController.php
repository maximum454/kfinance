<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::where('user_id', $request->user()->id)
            ->with(['account', 'category', 'tags'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        $perPage = min((int) $request->input('per_page', 50), 100);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|string|in:expense,income',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date_format:Y-m-d',
            'comment' => 'nullable|string|max:1000',
            'photo_path' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        // Проверка, что счёт и категория принадлежат пользователю
        $this->assertBelongsToUser($request);

        $validated['user_id'] = $request->user()->id;

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $transaction = Transaction::create($validated);

        if (! empty($tags)) {
            $transaction->tags()->attach($tags);
        }

        $transaction->load(['account', 'category', 'tags']);

        return response()->json($transaction, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = $this->findOwn($request, $id)
            ->load(['account', 'category', 'tags']);

        return response()->json($transaction);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = $this->findOwn($request, $id);

        $validated = $request->validate([
            'account_id' => 'exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'string|in:expense,income',
            'amount' => 'numeric|min:0.01',
            'date' => 'date_format:Y-m-d',
            'comment' => 'nullable|string|max:1000',
            'photo_path' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        if (isset($validated['account_id']) || isset($validated['category_id'])) {
            $this->assertBelongsToUser($request);
        }

        $tags = $validated['tags'] ?? null;
        unset($validated['tags']);

        $transaction->update($validated);

        if ($tags !== null) {
            $transaction->tags()->sync($tags);
        }

        $transaction->load(['account', 'category', 'tags']);

        return response()->json($transaction);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $transaction = $this->findOwn($request, $id);

        $transaction->delete();

        return response()->json(['message' => 'Транзакция удалена.']);
    }

    private function findOwn(Request $request, int $id): Transaction
    {
        $transaction = Transaction::where('user_id', $request->user()->id)->find($id);

        if (! $transaction) {
            throw ValidationException::withMessages([
                'id' => ['Транзакция не найдена.'],
            ]);
        }

        return $transaction;
    }

    private function assertBelongsToUser(Request $request): void
    {
        $userId = $request->user()->id;

        if ($request->filled('account_id')) {
            $account = \App\Models\Account::find($request->input('account_id'));
            if (! $account || $account->user_id !== $userId) {
                throw ValidationException::withMessages([
                    'account_id' => ['Счёт не найден.'],
                ]);
            }
        }

        if ($request->filled('category_id')) {
            $category = \App\Models\Category::find($request->input('category_id'));
            if (! $category || $category->user_id !== $userId) {
                throw ValidationException::withMessages([
                    'category_id' => ['Категория не найдена.'],
                ]);
            }
        }
    }
}