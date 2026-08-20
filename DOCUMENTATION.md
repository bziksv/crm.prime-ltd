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

1. Скопировать код в vhost / OpenServer / Docker с PHP ≥ 8.1.
2. `cp crm.prime-ltd.su/app/Config/Database.php.example crm.prime-ltd.su/app/Config/Database.php` и прописать доступ к БД.
3. Импортировать дамп из `db/`.
4. Создать пустые каталоги `files/`, `writable/{cache,logs,session,uploads}` с правами на запись.
5. В `.env` при необходимости: `CI_ENVIRONMENT = development`.
6. Открыть сайт в браузере (document root = `crm.prime-ltd.su`).

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
