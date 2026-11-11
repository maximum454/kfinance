<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateController extends Controller
{
    private const CODES = ['USD', 'EUR', 'RUB', 'CNY'];

    private const CACHE_KEY = 'exchange_rates_kzt_v2';

    private const CACHE_TTL_MINUTES = 60;

    public function index(): JsonResponse
    {
        $rates = Cache::get(self::CACHE_KEY);

        if ($rates === null || $this->hasWrongShape($rates)) {
            $rates = $this->fetchRates();

            if ($rates !== []) {
                Cache::put(self::CACHE_KEY, $rates, now()->addMinutes(self::CACHE_TTL_MINUTES));
            }
        }

        return response()->json([
            'base' => 'KZT',
            'rates' => array_values($rates),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    private function hasWrongShape(array $rates): bool
    {
        return $rates !== [] && !isset($rates[0]['code']);
    }

    private function fetchRates(): array
    {
        try {
            $response = Http::timeout(10)->get('https://nationalbank.kz/rss/rates_all.xml');

            if ($response->failed()) {
                return [];
            }

            $xml = simplexml_load_string($response->body());

            if ($xml === false) {
                return [];
            }

            $result = [];

            foreach ($xml->channel->item as $item) {
                $code = strtoupper(trim((string) $item->title));

                if (!in_array($code, self::CODES, true)) {
                    continue;
                }

                $value = (float) $item->description;
                $quant = max(1, (int) $item->quant);

                if ($value <= 0) {
                    continue;
                }

                $result[$code] = [
                    'code' => $code,
                    'value' => round($value / $quant, 2),
                    'direction' => $this->direction((string) $item->index),
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function direction(string $index): string
    {
        $index = strtolower(trim($index));

        if ($index === 'up') {
            return 'up';
        }

        if ($index === 'down') {
            return 'down';
        }

        return 'flat';
    }
}