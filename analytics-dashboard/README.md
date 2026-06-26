# Analytics Dashboard

Учебный проект: дашборд визитов, графики, экспорт Excel.

## Быстрый старт

```bash
cd ~/Projects/analytics-dashboard
composer install && npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

→ http://127.0.0.1:8000

## Как устроено (коротко)

```
Заход на сайт → Metric::incrementVisit() → дашборд читает через AnalyticsService
```

- **Писать визиты:** только `Metric::incrementVisit()` (сайт + команды)
- **Читать аналитику:** только `AnalyticsService` (дашборд + Excel)
- **Фильтры:** period + sourceId — одинаковые везде

## Команды

```bash
php artisan analytics:add-visits 20          # тестовые визиты
php artisan analytics:reset-today            # сброс «Сегодня»
php artisan migrate:fresh --seed             # полный сброс
```

## Документация

- **[POYASNENIE.md](POYASNENIE.md)** — полное пояснение для отчёта
- **[HOSTING.md](HOSTING.md)** — общая инструкция по хостингу
- **[BEGET.md](BEGET.md)** — **Mac + Beget по шагам**
- **`database/mysql.sql`** — SQL для импорта в phpMyAdmin
- **[OTCHET_PRAKTIKA.md](OTCHET_PRAKTIKA.md)** — **отчёт по практике (15 дней)**

## Стек

Laravel · Livewire · Blade · Chart.js · SQLite/MySQL · Laravel-Excel
