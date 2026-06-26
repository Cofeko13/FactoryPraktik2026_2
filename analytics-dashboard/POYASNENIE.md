# Analytics Dashboard — полное пояснение проекта

> Учебный проект для колледжа.  
> Папка: `~/Projects/analytics-dashboard`  
> Запуск: `php artisan serve` → http://127.0.0.1:8000

---

## 1. Главная идея (одной строкой)

**Заход на сайт → +1 в базу → дашборд показывает сумму из базы.**

Всё приложение крутится вокруг одной таблицы `metrics` и одного фильтра (`period` + `sourceId`).

---

## 2. Схема — как всё связано

```
Посетитель открывает /
        │
        ▼
TrackVisit (middleware) ──► VisitTracker ──► Metric::incrementVisit()
        │                                          │
        │                                          ▼
        │                                    таблица metrics
        │                                          │
        ▼                                          │
AnalyticsService ◄──── Livewire дашборд ◄───────────┘
        │
        ├── карточки (сумма, сегодня, среднее)
        ├── графики Chart.js
        ├── таблица
        └── MetricsExport → Excel
```

**Один источник правды:** таблица `metrics`.  
**Один способ читать:** `AnalyticsService` + scope `filtered()`.  
**Один способ писать:** `Metric::incrementVisit()`.

---

## 3. База данных

### Таблица `sources` — справочник

| Поле | Зачем |
|------|-------|
| `name` | «Google», «Прямой заход» — для человека |
| `slug` | `google`, `direct` — для кода |

Файл: `database/migrations/2026_06_24_000001_create_sources_table.php`

### Таблица `metrics` — визиты

| Поле | Зачем |
|------|-------|
| `source_id` | Ссылка на `sources.id` |
| `date` | День (2026-06-26) |
| `visits` | Сколько заходов за этот день |

Уникально: одна строка на пару `(source_id + date)`.

Файл: `database/migrations/2026_06_24_000002_create_metrics_table.php`

---

## 4. Все файлы проекта — что делает каждый

### Запись визитов (пишем в БД)

| Файл | Что делает |
|------|------------|
| `app/Http/Middleware/TrackVisit.php` | На GET-запросах вызывает трекер. Пропускает `/export`, `/livewire/*` |
| `app/Services/VisitTracker.php` | Определяет источник → вызывает `Metric::incrementVisit()` |
| `app/Models/Source.php` → `resolveFromReferrer()` | Referer → slug (google/direct/…) |
| `app/Models/Metric.php` → `incrementVisit()` | **Единственное место +1 визита** |

### Чтение аналитики (читаем из БД)

| Файл | Что делает |
|------|------------|
| `app/Services/AnalyticsService.php` | Суммы, графики, таблица — **всё через один класс** |
| `app/Models/Metric.php` → `scopeFiltered()` | Фильтр period + source — **одинаковый везде** |

### Интерфейс

| Файл | Что делает |
|------|------------|
| `resources/views/components/⚡analytics-dashboard.blade.php` | Livewire: фильтры, карточки, графики, таблица |
| `resources/views/components/stat-card.blade.php` | Переиспользуемая карточка цифры |
| `resources/views/components/chart-card.blade.php` | Обёртка для canvas |
| `resources/js/app.js` | Chart.js: bar + doughnut |
| `resources/views/layouts/app.blade.php` | HTML-оболочка, header, footer |

### Экспорт

| Файл | Что делает |
|------|------------|
| `app/Exports/MetricsExport.php` | Те же фильтры → Excel |
| `app/Http/Controllers/ExportController.php` | Маршрут `/export` |
| `routes/web.php` | `/` дашборд, `/export` файл |

### Команды для теста

| Команда | Файл |
|---------|------|
| `php artisan analytics:add-visits 20` | `AddTestVisits.php` |
| `php artisan analytics:reset-today` | `ResetTodayMetrics.php` |

---

## 5. Как понимает «сегодня»

```
.env → APP_TIMEZONE=Europe/Moscow
         ↓
today() → 2026-06-26
         ↓
metrics.date = 2026-06-26  →  попадает в карточку «Сегодня»
```

- Запись: `Metric::incrementVisit()` → `today()->toDateString()`
- Показ: `AnalyticsService::todayVisits()` → `whereDate('date', today())`

---

## 6. Что упростили (рефакторинг)

| Было | Стало |
|------|-------|
| Логика upsert в 2 местах | Одна функция `Metric::incrementVisit()` |
| Referer-логика в VisitTracker | `Source::resolveFromReferrer()` |
| Запросы в Livewire + Export отдельно | Общий `AnalyticsService` |
| Два метода `updatedPeriod` / `updatedSourceId` | Один `updated()` |
| Повторяющийся HTML карточек | Компонент `<x-stat-card>` |
| Статичная страница | `wire:poll.15s` — цифры обновляются сами |

---

## 7. Команды — шпаргалка

```bash
# Запуск
php artisan serve

# Первый раз / сброс всего
php artisan migrate:fresh --seed

# Тестовые визиты (+20 прямых заходов)
php artisan analytics:add-visits 20

# +50 от Google за сегодня
php artisan analytics:add-visits 50 --source=google

# Визиты за прошлый день (для графика)
php artisan analytics:add-visits 80 --source=social --date=2026-06-20

# Сбросить только сегодня
php artisan analytics:reset-today

# Сборка CSS/JS
npm run build
```

---

## 8. Стек и ссылки для отчёта

| Технология | Зачем | Документация |
|-----------|-------|--------------|
| Laravel | Бэкенд, маршруты, ORM | [laravel.com/docs](https://laravel.com/docs) |
| Livewire | Фильтры без перезагрузки | [livewire.laravel.com](https://livewire.laravel.com/docs) |
| Blade | HTML-шаблоны | [laravel.com/docs/blade](https://laravel.com/docs/blade) |
| Chart.js | Графики | [chartjs.org/docs](https://www.chartjs.org/docs/latest/) |
| SQLite / MySQL | База | [laravel.com/docs/database](https://laravel.com/docs/database) |
| Laravel-Excel | Экспорт .xlsx | [docs.laravel-excel.com](https://docs.laravel-excel.com) |

---

## 9. MySQL для сдачи

В `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=analytics_dashboard
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate:fresh --seed
```

---

## 10. Что ещё можно сделать (для отчёта / улучшений)

### Легко (1–2 дня)

- [ ] Страница «О проекте» — цель, стек, автор
- [ ] Уникальные посетители (cookie или IP — не считать одного человека 10 раз подряд)
- [ ] Переключить `.env` на MySQL и проверить на хостинге

### Средне (3–5 дней)

- [ ] Авторизация (Laravel Breeze) — только админ видит дашборд
- [ ] CRUD: добавлять/редактировать источники вручную
- [ ] Страница истории экспортов

### Сложно (для «перспектив развития» в отчёте, не обязательно делать)

- [ ] Google Analytics API (GA4)
- [ ] Redis-кэш для тяжёлых запросов
- [ ] Laravel Horizon — экспорт больших файлов в фоне

---

## 11. Типичные вопросы на защите

**Откуда цифры?**  
Из таблицы `metrics`. Каждый заход на `/` → +1 через middleware.

**Почему F5 увеличивает счётчик?**  
Каждый F5 = новый GET-запрос = новый визит. Это заходы, не уникальные пользователи.

**Как фильтры связаны с Excel?**  
Один `AnalyticsService` — те же `period` и `sourceId`.

**Почему график не ломается при фильтре?**  
`wire:ignore` на canvas + событие `charts-updated` перерисовывает Chart.js.

**Как сбросить?**  
`analytics:reset-today` — только сегодня. `migrate:fresh --seed` — всё.

---

## 12. Структура папок

```
app/
├── Console/Commands/     ← команды add-visits, reset-today
├── Exports/              ← Excel
├── Http/
│   ├── Controllers/      ← ExportController
│   └── Middleware/       ← TrackVisit
├── Models/               ← Source, Metric
└── Services/             ← AnalyticsService, VisitTracker
resources/views/
├── components/
│   ├── ⚡analytics-dashboard.blade.php
│   ├── stat-card.blade.php
│   └── chart-card.blade.php
├── dashboard.blade.php
└── layouts/app.blade.php
database/
├── migrations/
└── seeders/AnalyticsSeeder.php   ← только 4 источника, без фейков
routes/web.php
```

---

## 13. Где хранится база данных

### Сейчас (SQLite)

| | |
|---|---|
| **Файл** | `database/database.sqlite` |
| **Полный путь** | `~/Projects/analytics-dashboard/database/database.sqlite` |
| **Настройка** | `.env` → `DB_CONNECTION=sqlite` |
| **Конфиг** | `config/database.php` |

Это **один файл на диске**. Можно открыть в **DB Browser for SQLite** и увидеть таблицы `sources`, `metrics`.

### Как определяется источник (Google / Email / Соцсети / Прямой)

Файл: `app/Models/Source.php` → `resolveFromRequest()`

**1. Параметр в URL** (удобно для теста локально):

| Ссылка | Источник |
|--------|----------|
| `http://127.0.0.1:8000/?utm_source=google` | Google |
| `http://127.0.0.1:8000/?utm_source=email` | Email |
| `http://127.0.0.1:8000/?utm_source=social` | Соцсети |
| `http://127.0.0.1:8000/` (просто открыть) | Прямой заход |

**2. Заголовок Referer** (на реальном сайте):

- Перешли из Google → `google` в referer → **Google**
- Из VK, Telegram, Facebook → **Соцсети**
- Из Gmail, Outlook → **Email**
- Вбили адрес вручную / F5 → **Прямой заход**

> На localhost почти все заходы = «Прямой», потому что ты открываешь сайт напрямую.  
> Для проверки других источников используй ссылки с `?utm_source=...` выше.

**Запись:** `Metric::incrementVisit()`  
**Чтение:** `AnalyticsService`

### MySQL на хостинге

Меняется только `.env` (`DB_CONNECTION=mysql`). Код приложения тот же.

### Посмотреть данные

```bash
sqlite3 database/database.sqlite "SELECT s.name, m.date, m.visits FROM metrics m JOIN sources s ON s.id=m.source_id;"
```
