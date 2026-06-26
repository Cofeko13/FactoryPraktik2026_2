# Заливка на хостинг

## Нужно ли это для колледжа?

| Где показываешь | Что нужно |
|-----------------|-----------|
| **На своём Mac** (Herd / `php artisan serve`) | SQLite уже работает — **хостинг не нужен** |
| **Ссылка в интернете** (преподаватель открывает сайт) | **Да** — хостинг + MySQL |

---

## Что подготовлено

| Файл | Зачем |
|------|-------|
| `database/mysql.sql` | Импорт всех таблиц в MySQL через phpMyAdmin |
| `.env.hosting.example` | Пример настроек для сервера |

---

## Пошагово: залить на хостинг

### 1. Купить/взять хостинг

Нужно:
- **PHP 8.2+**
- **MySQL 8+**
- **Composer** (или SSH)
- Document root → папка **`public`**

Подойдут: Timeweb, Beget, Reg.ru, InfinityFree и т.п.

### 2. Создать базу MySQL

В панели хостинга:
1. Создать БД (например `analytics_dashboard`)
2. Создать пользователя и пароль
3. Запомнить: **хост, имя БД, логин, пароль**

### 3. Импортировать SQL

**Вариант А — phpMyAdmin (проще):**
1. Открыть phpMyAdmin
2. Выбрать свою базу
3. Вкладка **Импорт**
4. Выбрать файл `database/mysql.sql`
5. Нажать **Вперёд**

**Вариант Б — через SSH (если есть):**
```bash
php artisan migrate --force
php artisan db:seed --force
```

### 4. Загрузить файлы проекта

Залить **весь проект** на сервер (FTP / файловый менеджер / git).

Структура на сервере:
```
/home/user/analytics-dashboard/
├── app/
├── public/          ← сюда должен смотреть домен
├── ...
```

### 5. Настроить `.env` на сервере

Скопировать `.env.hosting.example` → `.env` и заполнить:

```env
APP_URL=https://твой-домен.ru
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=analytics_dashboard
DB_USERNAME=твой_логин
DB_PASSWORD=твой_пароль
```

По SSH:
```bash
cp .env.hosting.example .env
php artisan key:generate
```

### 6. Собрать фронтенд

**На своём Mac перед заливкой:**
```bash
npm install
npm run build
```

Залить папку `public/build/` на сервер.

Или на сервере (если есть Node):
```bash
npm install && npm run build
```

### 7. Права на папки

```bash
chmod -R 775 storage bootstrap/cache
```

### 8. Проверить

Открыть `https://твой-домен.ru` — должен открыться дашборд.

Тест источников:
- `https://твой-домен.ru/?utm_source=google`
- `https://твой-домен.ru/?utm_source=social`

---

## Чеклист «всё готово»

- [ ] Проект работает локально (`php artisan serve`)
- [ ] `npm run build` выполнен
- [ ] MySQL импортирован (`mysql.sql` или `migrate`)
- [ ] `.env` на сервере настроен
- [ ] `APP_KEY` сгенерирован
- [ ] Домен смотрит на `public/`
- [ ] Дашборд открывается, визиты считаются
- [ ] Excel скачивается
- [ ] `POYASNENIE.md` — для отчёта по практике

---

## Что ещё можно (не обязательно)

- Страница «О проекте»
- Авторизация (Laravel Breeze)
- Свой домен + HTTPS (обычно бесплатно на хостинге)
- Уникальные посетители (не считать 10 F5 как 10 человек)
