# Документация: CRM Prime Ltd

## Обзор

Внутренняя CRM на базе **RISE CRM** (CodeIgniter 4) для команды [Prime Ltd](https://prime-ltd.su).

| Параметр | Значение |
|----------|----------|
| Боевой URL | https://crm.prime-ltd.su |
| GitHub | https://github.com/bziksv/crm.prime-ltd |
| Сервер | `217.28.220.186` (SSH alias `vilmed`) |
| Document root | `/var/www/crm_prime_lt_usr/data/www/crm.prime-ltd.su` |
| PHP | ≥ 8.1 |
| БД | MySQL 8, схема `crm_prime_lt`, префикс таблиц `rise_` |
| Фреймворк | CodeIgniter 4 / RISE CRM |

---

## Структура репозитория

```
crm.prime-ltd/
├── DOCUMENTATION.md
├── .gitignore
├── .cursorignore
├── db/                          # локальные дампы БД (не в git)
│   └── crm_prime_lt-YYYYMMDD.sql.gz
└── crm.prime-ltd.su/            # корень сайта (= document root на сервере)
    ├── index.php
    ├── .env                     # локально / на сервере (не в git)
    ├── app/                     # Controllers, Models, Views, Config, Helpers…
    ├── assets/                  # CSS/JS/картинки фронта
    ├── plugins/                 # кастомные плагины
    ├── system/                  # ядро CI4 / RISE
    ├── files/                   # пользовательские загрузки (не в git, ~13 ГБ на проде)
    ├── writable/                # cache/logs/session (не в git)
    └── app/Config/Database.php.example
```

На проде каталог `files/timeline_files` занимает основную часть диска (~13 ГБ). В репозиторий не входит.

---

## Плагины

| Плагин | Назначение |
|--------|------------|
| `Telegram_Notification` | Уведомления в Telegram по событиям CRM |
| `Password_manager` | Менеджер паролей внутри CRM |
| `Migration` | SQL-миграции (в т.ч. `2026-07-03.sql`) |

---

## База данных

- **Имя БД / пользователь:** `crm_prime_lt`
- **Хост на сервере:** `localhost`
- **Префикс:** `rise_`
- **Пароль:** только в `app/Config/Database.php` на сервере / локально (файл в `.gitignore`)

### Свежий дамп (локально)

После выгрузки с прода:

```bash
ls -lh db/crm_prime_lt-*.sql.gz
```

Импорт в локальный MySQL:

```bash
gunzip -c db/crm_prime_lt-YYYYMMDD.sql.gz | mysql -u ROOT_USER -p crm_prime_lt
```

Повторная выгрузка с сервера:

```bash
ssh vilmed 'mysqldump --single-transaction --routines --triggers --default-character-set=utf8mb4 crm_prime_lt | gzip -c > /tmp/crm_prime_lt.sql.gz'
# пауза 3–5 сек, затем:
ssh vilmed 'cat /tmp/crm_prime_lt.sql.gz' > db/crm_prime_lt-$(date +%Y%m%d).sql.gz
ssh vilmed 'rm -f /tmp/crm_prime_lt.sql.gz'
```

SSH: `Host vilmed` → `217.28.220.186`, ключ `~/.ssh/id_ed25519`.

---

## Локальный запуск

### Подготовка (один раз)

1. PHP ≥ 8.1, локальный MySQL **или** SSH-туннель к боевой БД (см. ниже).
2. `cp crm.prime-ltd.su/app/Config/Database.php.example crm.prime-ltd.su/app/Config/Database.php` — прописать доступ к БД.
3. В `.env` (локально):

```
CI_ENVIRONMENT = development
files.baseURL = 'https://crm.prime-ltd.su/'
```

4. Каталоги `writable/{cache,logs,session,uploads}` и заглушки `files/` — с правами на запись.

### Вариант A: удалённая БД напрямую

На сервере у `crm_prime_lt` есть хост `%` — с Mac можно ходить на `217.28.220.186:3306`.

**Минус:** высокий round-trip (~20–30 мс на запрос + ~200–300 мс на коннект). Страница логина легко уходит в **десятки секунд**; встроенный PHP-сервер однопоточный и из‑за этого «встаёт» целиком. Для повседневной локальной работы лучше **вариант B**.

В `app/Config/Database.php` (не в git):

```php
'hostname' => '217.28.220.186',
'port'     => 3306,
```

Запасной туннель, если `%` закроют: `ssh -N -L 3308:127.0.0.1:3306 vilmed` → `127.0.0.1:3308`.

### Вариант B: локальная копия БД (быстро, рекомендуется для dev)

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS crm_prime_lt CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
gunzip -c db/crm_prime_lt-YYYYMMDD.sql.gz | mysql -u root crm_prime_lt
```

В `Database.php`: `hostname` = `localhost`, `port` = `3306`. Ожидаемый TTFB логина ~0.2–0.5 с.

### PHP built-in server (экономный режим по RAM)

Документ root = `crm.prime-ltd.su`. Роутер: `crm.prime-ltd.su/router.php` (для ЧПУ на встроенном сервере PHP).

```bash
cd crm.prime-ltd.su

# остановить предыдущий инстанс на порту
pkill -f "php -S 127.0.0.1:8099" 2>/dev/null || true

# низкий memory_limit, без opcache — меньше RSS на Mac
php \
  -d memory_limit=96M \
  -d opcache.enable=0 \
  -d opcache.enable_cli=0 \
  -d realpath_cache_size=16K \
  -d realpath_cache_ttl=60 \
  -S 127.0.0.1:8099 router.php
```

| | |
|--|--|
| URL | http://127.0.0.1:8099/ → `/index.php/signin` |
| Ожидаемый RSS | ~25–40 МБ (один процесс PHP) |
| Порт | `8099` (если занят — сменить в команде) |
| БД | `217.28.220.186:3306` (удалённая) |

Остановка PHP: `pkill -f "php -S 127.0.0.1:8099"`.

Альтернатива: vhost / OpenServer / Docker с document root на `crm.prime-ltd.su` — те же `.env` и БД.

---

## Деплой на прод

Документ root уже настроен на путь выше. Обычно достаточно обновить код без `files/` и `writable/`:

```bash
# пример: выкладка из git на сервер (после push в origin)
ssh vilmed 'cd /var/www/crm_prime_lt_usr/data/www/crm.prime-ltd.su && git pull'
```

Если на сервере ещё нет remote git — синхронизировать через `scp`/`tar` только каталоги `app/`, `assets/`, `plugins/`, `system/` и корневые PHP-файлы. **Не затирать** `files/`, `writable/`, `app/Config/Database.php`, `.env`.

После SQL из `plugins/Migration/install/migrations/` — применять вручную или через плагин Migration.

---


## Локальные files/ (лёгкая копия)

Локальный каталог `crm.prime-ltd.su/files/` **пустой** (~заглушки). Полные загрузки (~12 ГБ, в основном `timeline_files`) только на сервере.

В `.env` (не в git):

```
files.baseURL = 'https://crm.prime-ltd.su/'
```

Тогда URL вложений и превью строятся на прод (`get_file_uri()`). На боевом сервере эту переменную **не** задавать.

Вложенный `.git` внутри `crm.prime-ltd.su/` удалён — репозиторий только в корне проекта.

## Что не коммитим

- `app/Config/Database.php`, `.env` — секреты
- `db/*.sql.gz` — данные клиентов
- `files/`, содержимое `writable/` — тяжёлые/временные данные
- `crm.prime-ltd.su.old-*` — локальные архивы

---

## Полезные пути на сервере

| Что | Путь |
|-----|------|
| Код сайта | `/var/www/crm_prime_lt_usr/data/www/crm.prime-ltd.su` |
| Загрузки | `…/files/` (`timeline_files`, `general`, `profile_images`, …) |
| Логи CI | `…/writable/logs/` |
