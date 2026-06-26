# Запуск на Mac + заливка на Beget

---

## Часть 1. Запуск на Mac (без AMPPS, без phpMyAdmin)

### AMPPS не нужен

На Mac у тебя уже стоит **Laravel Herd** — PHP 8.4.  
phpMyAdmin на Mac **не нужен**: локально база — это файл `database/database.sqlite`.

### Способ 1 — через Terminal (самый простой)

Открой **Terminal** (Терминал) и вставь:

```bash
cd ~/Projects/analytics-dashboard
php artisan serve
```

Открой в браузере: **http://127.0.0.1:8000**

Остановить: `Ctrl + C` в терминале.

### Способ 2 — через Herd (красивый адрес)

```bash
cd ~/Projects/analytics-dashboard
herd link analytics-dashboard
```

Открой: **http://analytics-dashboard.test**

Приложение **Herd** — иконка в меню Mac (вверху экрана). Там можно включить/выключить PHP, смотреть сайты.

Скачать Herd если нет: https://herd.laravel.com

### Если «не работает»

```bash
cd ~/Projects/analytics-dashboard
composer install
php artisan migrate --force
npm install
npm run build
php artisan serve
```

### Где «база данных» на Mac

| | |
|---|---|
| Файл | `~/Projects/analytics-dashboard/database/database.sqlite` |
| phpMyAdmin | **Не нужен** — это SQLite, один файл |
| Посмотреть таблицы | Программа **DB Browser for SQLite** (бесплатно) |

---

## Часть 2. Beget — заливка по шагам

### Что где на Beget

| Где | Для чего |
|-----|----------|
| **Панель Beget** → MySQL | Создать базу |
| **phpMyAdmin** (ссылка в панели Beget) | Импорт `mysql.sql` |
| **FTP / Файловый менеджер** | Залить файлы |
| **Сайты → PHP** | Версия PHP 8.2 или 8.3 |

> phpMyAdmin на Beget — **только для MySQL на сервере**.  
> На Mac его искать не надо.

---

### Шаг 1. Подготовка на Mac (перед заливкой)

```bash
cd ~/Projects/analytics-dashboard
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Убедись что есть папка `public/build/` — без неё сайт без стилей.

---

### Шаг 2. MySQL на Beget

1. Войти в **cp.beget.com**
2. **MySQL** → **Создать базу**
3. Записать:
   - Имя базы (например `u123456_analytics`)
   - Логин (часто такой же)
   - Пароль
   - Хост: **`localhost`**

4. Нажать **phpMyAdmin** рядом с базой
5. Выбрать свою базу слева
6. **Импорт** → файл `database/mysql.sql` → **Вперёд**

---

### Шаг 3. Залить файлы

**FTP** (FileZilla) или **Файловый менеджер** в панели Beget.

Залить **весь проект** в папку домена, например:

```
/home/ВАШ_ЛОГИН/ваш-домен.ru/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/        ← сюда должен смотреть сайт
├── resources/
├── routes/
├── storage/
├── vendor/        ← если заливал composer install локально
├── .env
└── ...
```

**Важно:** залить папку `vendor/` — на Beget без SSH composer может быть недоступен.  
Выполни `composer install --no-dev` на Mac и залей `vendor/` по FTP.

---

### Шаг 4. Файл `.env` на Beget

На Mac создай `.env` для сервера (скопируй `.env.hosting.example`):

```env
APP_NAME="Analytics Dashboard"
APP_ENV=production
APP_KEY=base64:СЮДА_КЛЮЧ_С_MAC
APP_DEBUG=false
APP_URL=https://ваш-домен.ru
APP_TIMEZONE=Europe/Moscow

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456_analytics
DB_USERNAME=u123456_analytics
DB_PASSWORD=ваш_пароль_из_beget

SESSION_DRIVER=database
CACHE_STORE=database
```

**APP_KEY** — скопируй с Mac:

```bash
grep APP_KEY ~/Projects/analytics-dashboard/.env
```

Или на Beget по SSH: `php artisan key:generate`

---

### Шаг 5. Document root → папка `public`

На Beget:

1. **Сайты** → твой домен → **Настройки**
2. **Корневая папка сайта** → указать `public`  
   Пример: `/home/логин/домен.ru/public`

Если нельзя изменить — в `public_html` положи содержимое папки `public/`, а в `index.php` поправь пути (сложнее). Лучше — корень на `public`.

---

### Шаг 6. Права и кэш (SSH на Beget)

Если есть SSH:

```bash
cd ~/ваш-домен.ru
chmod -R 775 storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Без SSH — в файловом менеджере Beget: `storage/` и `bootstrap/cache/` → права **755** или **775**.

---

### Шаг 7. Проверка

Открыть `https://ваш-домен.ru`

---

## Как считать Google, соцсети, Email на Beget

### Почему всё «Прямой заход»

Если ты просто вбиваешь адрес в браузере — это **всегда прямой заход**.  
Google / VK / Email считаются только когда человек **перешёл по ссылке**.

### 3 способа (на Beget работает так же, как локально)

**1. Автоматически — Referer (переход с другого сайта)**

| Откуда перешли | Что считается |
|----------------|---------------|
| Google / Yandex | Google |
| VK, Telegram, Instagram, Facebook | Соцсети |
| Gmail, Outlook, Mail.ru | Email |
| Вбил адрес сам | Прямой заход |

**2. Ссылки с меткой utm (лучший способ для соцсетей и рассылок)**

Вставляй в постах и письмах **эти ссылки** (замени `ваш-домен.ru`):

```
https://ваш-домен.ru/?utm_source=social
https://ваш-домен.ru/?utm_source=google
https://ваш-домен.ru/?utm_source=email
```

Кто кликнет — попадёт в нужный источник.  
Instagram и Telegram часто **не передают Referer** — поэтому для них **utm обязателен**.

**3. Сессия**

Если зашли по ссылке с `?utm_source=social`, следующие страницы на сайте  
(в течение ~2 часов) тоже считаются как «Соцсети».

### Проверка на Beget

1. Открой `https://ваш-домен.ru/?utm_source=social` → +1 в Соцсети  
2. Открой `https://ваш-домен.ru/?utm_source=email` → +1 в Email  
3. На дашборде выбери **«Все источники»** — круговая диаграмма покажет все сегменты  

### Важно для `.env` на Beget

```env
SESSION_DRIVER=database
```

Без сессий utm-метки не запомнятся. Таблица `sessions` должна быть в MySQL (есть в `mysql.sql`).

---

## Частые ошибки на Beget

| Ошибка | Решение |
|--------|---------|
| Белая страница | `APP_DEBUG=true` временно, смотри `storage/logs/laravel.log` |
| 500 error | Нет `APP_KEY`, нет прав на `storage/` |
| Нет стилей | Не залита `public/build/`, сделай `npm run build` на Mac |
| Ошибка БД | Проверь `.env` — имя базы, пароль, `DB_HOST=localhost` |
| PHP старый | В панели Beget → PHP 8.2 или 8.3 |

---

## Что использовать где — шпаргалка

| | Mac (локально) | Beget (интернет) |
|--|----------------|------------------|
| Запуск | `php artisan serve` | Открыть домен |
| База | `database.sqlite` (файл) | MySQL |
| phpMyAdmin | Не нужен | В панели Beget |
| AMPPS / XAMPP | Не нужен | — |
| Herd | Опционально | — |
