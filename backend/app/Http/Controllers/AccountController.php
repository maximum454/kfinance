<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = Account::where('user_id', $request->user()->id)->get();

        return response()->json($accounts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'string|size:3',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        $account = Account::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'currency' => strtoupper($validated['currency'] ?? 'KZT'),
            'color' => $validated['color'] ?? null,
            'icon' => $validated['icon'] ?? null,
        ]);

        return response()->json($account, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $account = $this->findOwn($request, $id);

        return response()->json($account);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $account = $this->findOwn($request, $id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'currency' => 'string|size:3',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        if (isset($validated['currency'])) {
            $validated['currency'] = strtoupper($validated['currency']);
        }

        $account->update($validated);

        return response()->json($account);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $account = $this->findOwn($request, $id);

        $account->delete();

        return response()->json(['message' => 'Счёт удалён.']);
    }

    private function findOwn(Request $request, int $id): Account
    {
        $account = Account::where('user_id', $request->user()->id)->find($id);

        if (! $account) {
            throw ValidationException::withMessages([
                'id' => ['Счёт не найден.'],
            ]);
        }

        return $account;
    }
}