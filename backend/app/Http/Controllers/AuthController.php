<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $this->createDefaults($user);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('mobile')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('mobile')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Вы вышли из системы.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    private function createDefaults(User $user): void
    {
        Account::create([
            'user_id' => $user->id,
            'name' => 'Основной',
            'currency' => 'KZT',
        ]);

        $defaultCategories = [
            ['name' => 'Здоровье', 'type' => 'expense', 'icon' => '🏥', 'color' => '#ef4444'],
            ['name' => 'Химия', 'type' => 'expense', 'icon' => '🧴', 'color' => '#f97316'],
            ['name' => 'Дом', 'type' => 'expense', 'icon' => '🏠', 'color' => '#eab308'],
            ['name' => 'Связь', 'type' => 'expense', 'icon' => '📱', 'color' => '#10b981'],
            ['name' => 'Кредит', 'type' => 'expense', 'icon' => '💳', 'color' => '#06b6d4'],
            ['name' => 'Продукты', 'type' => 'expense', 'icon' => '🛒', 'color' => '#6366f1'],
            ['name' => 'Семья', 'type' => 'expense', 'icon' => '👨‍👩‍👧‍👦', 'color' => '#8b5cf6'],
            ['name' => 'Транспорт', 'type' => 'expense', 'icon' => '🚗', 'color' => '#ec4899'],
            ['name' => 'Сигареты', 'type' => 'expense', 'icon' => '🚬', 'color' => '#a855f7'],
            ['name' => 'Вода', 'type' => 'expense', 'icon' => '💧', 'color' => '#0ea5e9'],
            ['name' => 'Собака', 'type' => 'expense', 'icon' => '🐕', 'color' => '#84cc16'],
            ['name' => 'Мясо', 'type' => 'expense', 'icon' => '🥩', 'color' => '#e11d48'],
            ['name' => 'Аренда', 'type' => 'expense', 'icon' => '🏢', 'color' => '#d946ef'],
            ['name' => 'Бизнес', 'type' => 'expense', 'icon' => '💼', 'color' => '#14b8a6'],
            ['name' => 'Подарки', 'type' => 'expense', 'icon' => '🎁', 'color' => '#f43f5e'],
            ['name' => 'Зарплата', 'type' => 'income', 'icon' => '💰', 'color' => '#10b981'],
            ['name' => 'Подработка', 'type' => 'income', 'icon' => '📝', 'color' => '#0ea5e9'],
        ];

        foreach ($defaultCategories as $cat) {
            Category::create(array_merge($cat, ['user_id' => $user->id]));
        }
    }
}