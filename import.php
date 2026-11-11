<?php
/**
 * Import from MyFinance .mmbackup into Kfinance (MySQL via Docker)
 * Usage: php import.php <email>
 *
 * 1. Extract .mmbackup to /tmp/mm-import/MyFinance.db first
 * 2. Make sure you have a registered user in Kfinance
 * 3. Run this script
 */

$email = $argv[1] ?? null;

if (!$email) {
    die("Usage: php import.php <email>\n");
}

// Connect to MySQL (Docker container on 127.0.0.1:3306)
$dst = new PDO('mysql:host=127.0.0.1;port=3306;dbname=kfinance;charset=utf8mb4', 'kfinance', 'kfinance_pass');
$dst->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Connect to source SQLite DB
$backupDb = '/tmp/mm-import/MyFinance.db';
if (!file_exists($backupDb)) {
    die("Source DB not found: $backupDb\nExtract your .mmbackup first:\n  unzip -o your.mmbackup -d /tmp/mm-import/\n");
}
$src = new PDO("sqlite:$backupDb");
$src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Find user
$user = $dst->prepare('SELECT id FROM users WHERE email = ?');
$user->execute([$email]);
$userId = $user->fetchColumn();

if (!$userId) {
    die("User with email '$email' not found.\n");
}

echo "Importing for user ID: $userId\n\n";

// --- Step 1: Categories ---
echo "=== Step 1: Categories ===\n";

$srcCats = $src->query("SELECT uid, title, type, icon, color FROM category WHERE isRemoved=0")->fetchAll(PDO::FETCH_ASSOC);

$iconToEmoji = [
    'heart' => '❤️', 'present' => '🎁', 'other' => '📦', 'bank' => '🏦',
    'prepaid' => '💳', 'family' => '👨‍👩‍👧‍👦', 'transport' => '🚗',
    'products' => '🛒', 'home' => '🏠', 'bill_coins' => '🧾',
    'mobile_shopping' => '🛍️', 'drinks' => '🥤', 'percent' => '💯',
    'meat' => '🥩', 'vegetables' => '🥦', 'money' => '💰',
    'mobile' => '📱', 'wine' => '🍷', 'pets' => '🐾', 'washer' => '🧴',
    'favorites' => '⭐', 'gift' => '🎁',
];

function argbToHex($int) {
    if (!$int) return '#059669';
    return '#' . substr(dechex($int), -6);
}

function iconToEmoji($icon, $iconMap) {
    return $iconMap[strtolower($icon)] ?? '•';
}

function titleFallback($title, $icon, $iconMap) {
    if ($title) return $title;
    $map = [
        'heart' => 'Здоровье', 'present' => 'Подарки', 'other' => 'Остальное',
        'bank' => 'Зарплата', 'prepaid' => 'Подработка', 'family' => 'Семья',
        'transport' => 'Транспорт', 'products' => 'Продукты', 'home' => 'Дом',
        'bill_coins' => 'Счета', 'money' => 'Переводы',
    ];
    return $map[strtolower($icon)] ?? 'Категория';
}

$existingCats = $dst->prepare("SELECT id, name, icon FROM categories WHERE user_id = ?");
$existingCats->execute([$userId]);
$existingMap = [];
foreach ($existingCats->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $existingMap[$c['name']] = $c;
}

$insertCat = $dst->prepare("INSERT INTO categories (user_id, name, type, icon, color, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");

$uidToCatId = [];
$catCreated = 0;

foreach ($srcCats as $cat) {
    $title = $cat['title'] ?: titleFallback($cat['title'], $cat['icon'], $iconToEmoji);
    $emoji = $cat['icon'] ? iconToEmoji($cat['icon'], $iconToEmoji) : ($cat['icon'] ?: '📁');
    $hex = argbToHex($cat['color']);

    if (isset($existingMap[$title])) {
        $uidToCatId[$cat['uid']] = $existingMap[$title]['id'];
        echo "  EXISTS: $title (id={$existingMap[$title]['id']})\n";
    } else {
        $insertCat->execute([$userId, $title, strtolower($cat['type']), $emoji, $hex]);
        $newId = $dst->lastInsertId();
        $uidToCatId[$cat['uid']] = $newId;
        $existingMap[$title] = ['id' => $newId];
        $catCreated++;
        echo "  CREATED: $title ($cat[type]) -> id=$newId\n";
    }
}

echo "Categories: $catCreated created, " . (count($srcCats) - $catCreated) . " existing\n\n";

// --- Step 2: Account ---
echo "=== Step 2: Account ===\n";

// Get the default account
$existingAccount = $dst->prepare("SELECT id FROM accounts WHERE user_id = ? AND name = 'Основной'");
$existingAccount->execute([$userId]);
$accountId = $existingAccount->fetchColumn();

if (!$accountId) {
    $createAcc = $dst->prepare("INSERT INTO accounts (user_id, name, currency, created_at, updated_at) VALUES (?, 'Основной', 'KZT', NOW(), NOW())");
    $createAcc->execute([$userId]);
    $accountId = $dst->lastInsertId();
    echo "  CREATED: Основной (id=$accountId)\n";
} else {
    echo "  EXISTS: Основной (id=$accountId)\n";
}

// --- Step 3: Transactions ---
echo "\n=== Step 3: Transactions ===\n";

// Get all transaction UIDs with their category and account links
$txnLinks = $src->query("
    SELECT t.uid, t.type, t.amountInDefaultCurrency, t.date, t.comment, t.isRemoved,
           l_cat.otherUid as category_uid,
           l_acc.otherUid as account_uid
    FROM \"transaction\" t
    LEFT JOIN sync_link l_cat ON l_cat.entityUid = t.uid AND l_cat.entityType = 'Transaction' AND l_cat.otherType = 'Category' AND l_cat.isRemoved = 0
    LEFT JOIN sync_link l_acc ON l_acc.entityUid = t.uid AND l_acc.entityType = 'Transaction' AND l_acc.otherType = 'Account' AND l_acc.isRemoved = 0
    WHERE t.isRemoved = 0
")->fetchAll(PDO::FETCH_ASSOC);

$insertTxn = $dst->prepare("INSERT INTO transactions (user_id, account_id, category_id, type, amount, date, comment, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

$imported = 0;
$skipped = 0;

foreach ($txnLinks as $txn) {
    $catId = isset($uidToCatId[$txn['category_uid']]) ? $uidToCatId[$txn['category_uid']] : null;

    if (!$catId) {
        $skipped++;
        continue;
    }

    $amount = $txn['amountInDefaultCurrency'] / 100; // stored in cents/tiiyn
    $type = strtolower($txn['type']);
    $date = $txn['date'] ?: date('Y-m-d');
    $comment = $txn['comment'] ?: null;

    $insertTxn->execute([$userId, $accountId, $catId, $type, $amount, $date, $comment]);
    $imported++;
}

echo "Transactions: $imported imported, $skipped skipped (no category match)\n\n";

echo "=== Done! ===\n";
echo "Check your accounts and analytics in the app.\n";
