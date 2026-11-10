<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $baseQuery = Transaction::where('user_id', $userId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo);

        // Общие суммы
        $totalIncome = (float) (clone $baseQuery)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = (float) (clone $baseQuery)
            ->where('type', 'expense')
            ->sum('amount');

        // Агрегация по категориям (расходы)
        $expenseByCategory = (clone $baseQuery)
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name,icon,color')
            ->get()
            ->map(function ($item) use ($totalExpense) {
                return [
                    'category_id' => $item->category_id,
                    'category' => $item->category,
                    'total' => (float) $item->total,
                    'percent' => $totalExpense > 0
                        ? round(($item->total / $totalExpense) * 100, 1)
                        : 0,
                ];
            });

        // Агрегация по категориям (доходы)
        $incomeByCategory = (clone $baseQuery)
            ->where('type', 'income')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category:id,name,icon,color')
            ->get()
            ->map(function ($item) use ($totalIncome) {
                return [
                    'category_id' => $item->category_id,
                    'category' => $item->category,
                    'total' => (float) $item->total,
                    'percent' => $totalIncome > 0
                        ? round(($item->total / $totalIncome) * 100, 1)
                        : 0,
                ];
            });

        return response()->json([
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'totals' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense,
            ],
            'categories' => [
                'expense' => $expenseByCategory,
                'income' => $incomeByCategory,
            ],
        ]);
    }
}