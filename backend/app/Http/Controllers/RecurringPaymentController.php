<?php

namespace App\Http\Controllers;

use App\Models\RecurringPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecurringPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = RecurringPayment::where('user_id', $request->user()->id)
            ->with(['account', 'category'])
            ->orderBy('next_date')
            ->get();

        return response()->json($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|string|in:expense,income',
            'frequency' => 'required|string|in:daily,weekly,monthly,yearly',
            'next_date' => 'required|date_format:Y-m-d',
            'description' => 'nullable|string|max:1000',
            'active' => 'boolean',
        ]);

        $this->assertBelongsToUser($request);

        $validated['user_id'] = $request->user()->id;
        $validated['active'] = $validated['active'] ?? true;

        $payment = RecurringPayment::create($validated);
        $payment->load(['account', 'category']);

        return response()->json($payment, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $payment = $this->findOwn($request, $id)
            ->load(['account', 'category']);

        return response()->json($payment);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payment = $this->findOwn($request, $id);

        $validated = $request->validate([
            'account_id' => 'exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'amount' => 'numeric|min:0.01',
            'type' => 'string|in:expense,income',
            'frequency' => 'string|in:daily,weekly,monthly,yearly',
            'next_date' => 'date_format:Y-m-d',
            'description' => 'nullable|string|max:1000',
            'active' => 'boolean',
        ]);

        if (isset($validated['account_id']) || isset($validated['category_id'])) {
            $this->assertBelongsToUser($request);
        }

        $payment->update($validated);
        $payment->load(['account', 'category']);

        return response()->json($payment);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $payment = $this->findOwn($request, $id);

        $payment->delete();

        return response()->json(['message' => 'Регулярный платёж удалён.']);
    }

    private function findOwn(Request $request, int $id): RecurringPayment
    {
        $payment = RecurringPayment::where('user_id', $request->user()->id)->find($id);

        if (! $payment) {
            throw ValidationException::withMessages([
                'id' => ['Регулярный платёж не найден.'],
            ]);
        }

        return $payment;
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