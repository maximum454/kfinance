<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\RecurringPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Импорт данных из .mmbackup (MyFinance / Money Lover).
 *
 * Использование:
 *   php artisan import:myfinance <email> <path_to_mmbackup>
 *
 * Пример:
 *   php artisan import:myfinance maximum454@gmail.com ../2026_11_10_18_56_56_237218.mmbackup
 */
class MyFinanceImporter
{
    /**
     * Главный метод импорта.
     */
    public function import(string $email, string $mmPath): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new \RuntimeException('Пользователь с email "' . $email . '" не найден.');
        }

        echo '→ Импорт для: ' . $user->name . ' (' . $user->email . ")\n";

        // 1. Распаковка .mmbackup → извлекаем MyFinance.db
        echo '  Распаковка архива...' . "\n";
        $tmpDir = '/tmp/kfinance_import_' . bin2hex(random_bytes(4));
        $dbPath = $tmpDir . '/MyFinance.db';
        exec('mkdir -p ' . $tmpDir);

        // Пробуем unzip напрямую (файл имеет 8-байтовый префикс ZIP)
        $unzipOk = true;
        exec('unzip -o -j "' . $mmPath . '" MyFinance.db -d "' . $tmpDir . '" 2>/dev/null');

        if (! file_exists($dbPath)) {
            // fallback: tail -c +9 убирает 8-байтовый префикс
            exec('tail -c +9 "' . $mmPath . '" > "' . $tmpDir . '/data.zip"');
            exec('unzip -o -j "' . $tmpDir . '/data.zip" MyFinance.db -d "' . $tmpDir . '" 2>/dev/null');
        }

        if (! file_exists($dbPath)) {
            exec('rm -rf ' . $tmpDir);
            throw new \RuntimeException('Не удалось извлечь MyFinance.db из архива.');
        }

        echo '  Извлечено: ' . $dbPath . "\n";

        // 2. Читаем данные из MyFinance.db через sqlite3 в JSON
        echo '  Чтение данных из MyFinance.db...' . "\n";

        $accounts = $this->queryDb($dbPath, 'account', 'isRemoved = 0');
        $categories = $this->queryDb($dbPath, 'category', 'isRemoved = 0');
        $transactions = $this->queryDb($dbPath, '"transaction"', 'isRemoved = 0');
        $links = $this->queryDb($dbPath, 'sync_link', "entityType = 'Transaction' AND isRemoved = 0");
        $remindings = $this->queryDb($dbPath, 'reminding', "remindingType = 'regularPayments'");

        echo '  Найдено: ' . count($accounts) . ' счетов, '
            . count($categories) . ' категорий, '
            . count($transactions) . ' транзакций, '
            . count($links) . ' связей, '
            . count($remindings) . ' регулярных платежей' . "\n";

        // 3. Импорт в Kfinance в транзакции
        DB::beginTransaction();

        try {
            $accountMap = $this->importAccounts($accounts, $user);
            echo '  ✓ Счета: ' . count($accountMap) . "\n";

            $categoryMap = $this->importCategories($categories, $user);
            echo '  ✓ Категории: ' . count($categoryMap) . "\n";

            $txCount = $this->importTransactions($transactions, $links, $user, $accountMap, $categoryMap);
            echo '  ✓ Транзакции: ' . $txCount . "\n";

            $rpCount = $this->importRecurringPayments($remindings, $user, $accountMap);
            echo '  ✓ Регулярные платежи: ' . $rpCount . "\n";

            DB::commit();
        } catch (e) {
            DB::rollback();
            exec('rm -rf ' . $tmpDir);
            throw e;
        }

        // 4. Очистка
        exec('rm -rf ' . $tmpDir);

        echo "\n✅ Импорт завершён.\n";
    }

    /**
     * Выполнить SQL-запрос к MyFinance.db и вернуть массив ассоциативных массивов.
     */
    private function queryDb(string $dbPath, string $table, string $where): array
    {
        $sql = 'SELECT * FROM ' . $table . ' WHERE ' . $where;
        $tmpFile = '/tmp/mf_' . bin2hex(random_bytes(4)) . '.json';
        $sqlFile = '/tmp/mf_' . bin2hex(random_bytes(4)) . '.sql';

        file_put_contents($sqlFile, $sql);
        exec('sqlite3 -json "' . $dbPath . '" < "' . $sqlFile . '" > "' . $tmpFile . '"');
        unlink($sqlFile);

        if (! file_exists($tmpFile)) {
            return [];
        }

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        if (empty(trim($content))) {
            return [];
        }

        $rows = json_decode($content, true);

        if (! is_array($rows)) {
            return [];
        }

        return $rows;
    }

    /**
     * Импорт счетов.
     * Возвращает map: myfinance_uid → kfinance_account_id
     */
    private function importAccounts(array $rows, User $user): array
    {
        $map = [];

        foreach ($rows as $row) {
            $name = $row['title'];

            if (empty($name)) {
                $name = 'Основной';
            }

            $account = Account::create([
                'user_id' => $user->id,
                'name' => $name,
                'currency' => $row['currencyCode'] ?? 'KZT',
                'icon' => $this->mapIcon($row['icon']),
                'color' => $this->argbToHex($row['color']),
            ]);

            $map[$row['uid']] = $account->id;
            echo '    - Счёт: ' . $name . "\n";
        }

        return $map;
    }

    /**
     * Импорт категорий.
     * Возвращает map: myfinance_uid → kfinance_category_id
     */
    private function importCategories(array $rows, User $user): array
    {
        $map = [];

        foreach ($rows as $row) {
            $name = $row['title'];

            if (empty($name)) {
                $name = $row['uid'];
            }

            $icon = $this->mapIcon($row['icon']);

            $category = Category::create([
                'user_id' => $user->id,
                'name' => $name,
                'type' => strtolower($row['type']),
                'icon' => $icon,
                'color' => $this->argbToHex($row['color']),
            ]);

            $map[$row['uid']] = $category->id;
        }

        return $map;
    }

    /**
     * Импорт транзакций.
     */
    private function importTransactions(
        array $txRows,
        array $links,
        User $user,
        array $accountMap,
        array $categoryMap
    ): int
    {
        // Индексируем связи по entityUid транзакции
        $txLinks = [];

        foreach ($links as $link) {
            $txUid = $link['entityUid'];
            if (! isset($txLinks[$txUid])) {
                $txLinks[$txUid] = [];
            }
            $txLinks[$txUid][] = $link;
        }

        $count = 0;

        foreach ($txRows as $row) {
            $linksForTx = $txLinks[$row['uid']] ?? [];

            $accountId = null;
            $categoryId = null;

            foreach ($linksForTx as $link) {
                if ($link['otherType'] === 'Account' && isset($accountMap[$link['otherUid']])) {
                    $accountId = $accountMap[$link['otherUid']];
                } elseif ($link['otherType'] === 'Category' && isset($categoryMap[$link['otherUid']])) {
                    $categoryId = $categoryMap[$link['otherUid']];
                }
            }

            // Без счёта — пропускаем
            if (! $accountId) {
                continue;
            }

            $amount = $this->amountToDecimal($row['amountInAccountCurrency']);

            // Сумма должна быть > 0
            if ($amount <= 0) {
                continue;
            }

            $type = strtolower($row['type']); // expense / income
            $date = $row['date'];
            $comment = $row['comment'] ?? '';

            Transaction::create([
                'user_id' => $user->id,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'type' => $type,
                'amount' => $amount,
                'date' => $date,
                'comment' => $comment,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Импорт регулярных платежей (напоминания с типом regularPayments).
     */
    private function importRecurringPayments(array $remindings, User $user, array $accountMap): int
    {
        if (empty($remindings)) {
            return 0;
        }

        // Берём первый счёт из импортированных
        $firstAccountId = null;
        foreach ($accountMap as $accId) {
            $firstAccountId = $accId;
            break;
        }

        $count = 0;

        foreach ($remindings as $rem) {
            $name = $rem['title'];

            if (empty($name)) {
                continue;
            }

            $amount = $this->amountToDecimal($rem['amount']);

            if ($amount <= 0) {
                continue;
            }

            $type = 'expense';
            if ($rem['transactionType'] === 'Income') {
                $type = 'income';
            }

            $frequency = $this->mapFrequency($rem['period']);
            $nextDate = $rem['startDate'];

            RecurringPayment::create([
                'user_id' => $user->id,
                'account_id' => $firstAccountId,
                'category_id' => null,
                'amount' => $amount,
                'type' => $type,
                'frequency' => $frequency,
                'next_date' => $nextDate,
                'active' => ($rem['enabled'] === 1) ? true : false,
                'description' => $name,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Конвертация Integer (минимальные единицы, копейки/тиыны) → decimal.
     * В MyFinance суммы хранятся как Integer (100 = 1 тенге).
     */
    private function amountToDecimal(int $amount): float
    {
        return (float) $amount / 100;
    }

    /**
     * Конвертация ARGB int → HEX color (#RRGGBB).
     */
    private function argbToHex(?int $argb): ?string
    {
        if ($argb === null) {
            return null;
        }

        $red = ($argb >> 16) & 0xFF;
        $green = ($argb >> 8) & 0xFF;
        $blue = $argb & 0xFF;

        return '#' . sprintf('%02x%02x%02x', $red, $green, $blue);
    }

    /**
     * Маппинг иконок MyFinance → emoji для Kfinance.
     */
    private function mapIcon(?string $mfIcon): ?string
    {
        if ($mfIcon === null || empty($mfIcon)) {
            return null;
        }

        $iconMap = [
            'cash' => '💵',
            'card' => '💳',
            'bank' => '🏦',
            'wallet' => '👛',
            'heart' => '🏥',
            'present' => '🎁',
            'vegetables' => '🥬',
            'other' => '📦',
            'washer' => '🧴',
            'bill_coins' => '🏢',
            'meat' => '🥩',
            'pets' => '🐕',
            'prepaid' => '💰',
            'percent' => '💳',
            'mobile' => '📱',
            'family' => '👨‍👩‍👧‍👦',
            'transport' => '🚗',
            'money' => '💵',
            'products' => '🛒',
            'home' => '🏠',
            'mobile_shopping' => '💼',
            'drinks' => '💧',
            'wine' => '🚬',
            'sport' => '🏋️',
            'education' => '📚',
            'cafe' => '☕',
            'leisure' => '🎮',
            'presents' => '🎀',
            'salary' => '💰',
            'health' => '🏥',
        ];

        $lowered = strtolower($mfIcon);

        if (isset($iconMap[$lowered])) {
            return $iconMap[$lowered];
        }

        return $mfIcon;
    }

    /**
     * Маппинг частоты MyFinance → формат Kfinance.
     */
    private function mapFrequency(?string $period): string
    {
        $map = [
            'Day' => 'daily',
            'Week' => 'weekly',
            'Month' => 'monthly',
            'Year' => 'yearly',
        ];

        if ($period !== null && isset($map[$period])) {
            return $map[$period];
        }

        return 'monthly';
    }
}