<?php

use App\Services\MyFinanceImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('import:myfinance {email? : Email пользователя} {file? : Путь к .mmbackup}', function ($email = null, $file = null) {
    if (! $email || ! $file) {
        $this->comment('Использование: php artisan import:myfinance <email> <path_to_mmbackup>');

        return;
    }

    $filePath = realpath($file);

    if (! $filePath || ! file_exists($filePath)) {
        $this->error('Файл не найден: ' . $file);

        return;
    }

    $importer = new MyFinanceImporter();
    $importer->import($email, $filePath);
})->purpose('Импорт данных из MyFinance (.mmbackup)');
