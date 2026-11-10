# Kfinance

Личный финансовый трекер — быстро, без рекламы, всегда под рукой. PWA, которое работает с телефона как родное приложение.

## Стек

| Компонент | Технология |
|-----------|------------|
| Backend | Laravel 11 + Sanctum (token auth) |
| Frontend | Vue 3 + Vite + Vue Router + Pinia |
| База данных | SQLite (dev) / MySQL (production) |
| PWA | vite-plugin-pwa |
| Хостинг | Timeweb (shared hosting, SCP) |

## Возможности

- ✅ Auth: регистрация/вход/выход по токену (Sanctum)
- ✅ Dashboard: круговая диаграмма по категориям, фильтры периода (день/неделя/месяц/год)
- ✅ Категории: расход/доход, иконка, цвет — CRUD
- ✅ Счета: несколько счетов, баланс вычисляется из транзакций
- ✅ Транзакции: добавить, редактировать, удалить — категория, счёт, теги, дата
- ✅ История: список с фильтрами и пагинацией
- ✅ Теги: привязка к транзакциям, отдельное управление
- ✅ Регулярные платежи: планирование повторяющихся расходов/доходов
- ✅ Аналитика: суммы за период, агрегация по категориям с процентами
- ✅ PWA: офлайн-кеш, установка на экран дома, заставка

## Быстрый старт

```bash
# 1. Backend
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan serve --port=8000

# 2. Frontend (отдельный терминал)
cd frontend
npm install
npm run dev -- --port=5173
```

Открыть `http://127.0.0.1:5173` в браузере.

Для теста на телефоне: `npm run build`, затем раздать `frontend/dist/` любым статическим сервером, либо использовать флаг Vite `--host`: `npm run dev -- --host --port=5173`.

## API endpoints

Всего 30 роутов, все за middleware `auth:sanctum` кроме register/login:

| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/api/register` | Регистрация |
| POST | `/api/login` | Вход |
| POST | `/api/logout` | Выход |
| GET | `/api/user` | Текущий пользователь |
| GET/POST/PUT/DELETE | `/api/categories` | Категории CRUD |
| GET/POST/PUT/DELETE | `/api/accounts` | Счета CRUD |
| GET/POST/PUT/DELETE | `/api/transactions` | Транзакции CRUD |
| GET | `/api/analytics` | Аналитика за период |
| GET/POST/PUT/DELETE | `/api/tags` | Теги CRUD |
| GET/POST/PUT/DELETE | `/api/recurring-payments` | Регулярные платежи CRUD |

Транзакции поддерживают фильтры: `type`, `account_id`, `category_id`, `date_from`, `date_to`, `per_page`.

## Безопасность

- **Токены**: API использует Bearer-токены (Sanctum). Токены хранятся в localStorage, не в cookies.
- **Изоляция**: каждый запрос фильтруется по `user_id` — пользователи видят только свои данные.
- **Валидация**: все входные данные проверяются через `Request::validate()`.
- **Секреты**: `.env`, `database.sqlite` и `storage/*.key` в `.gitignore` — никогда не попадают в репозиторий.
- **CORS**: в dev-режиме разрешены все источники. Для продакшна — ограничить в `bootstrap/app.php`.
- **SQL injection**: Eloquent ORM использует prepared statements.
- **XSS**: Vue.js автоматически экранирует вывод в шаблонах.
- **Бэкап**: файл SQLite лежит в `backend/database/` — делайте резервные копии если используете SQLite в продакшне.

## Структура проекта

```
kfinance/
├── backend/           # Laravel 11 API
│   └── app/
│       ├── Http/Controllers/  # 7 контроллеров
│       └── Models/            # 6 моделей
├── frontend/          # Vue 3 SPA
│   └── src/
│       ├── api/       # API-клиент (fetch + token)
│       ├── stores/    # Pinia store
│       ├── router/    # Vue Router
│       ├── views/     # 9 страниц
│       └── components/# Компоненты (Layout)
├── PLAN.md            # План разработки
└── README.md          # Этот файл
```

## Деплой на Timeweb

```bash
# Собрать фронт
cd frontend && npm run build

# Загрузить через SCP
scp -r backend/* user@timeweb:/path/to/backend/
scp -r frontend/dist/* user@timeweb:/path/to/public_html/

# На сервере
cd /path/to/backend
php artisan migrate --force
# Настроить nginx: / -> /path/to/backend/public
# public_html/ для статики фронтенда
```

## Лицензия

Личный проект — для личного использования.