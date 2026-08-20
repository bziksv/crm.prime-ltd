# files/ — локально не храним

Загрузки CRM лежат на проде:

- путь: `/var/www/crm_prime_lt_usr/data/www/crm.prime-ltd.su/files`
- URL: https://crm.prime-ltd.su/files/

В локальном `.env` задано `files.baseURL = 'https://crm.prime-ltd.su/'`, поэтому `get_file_uri()` / превью тянут файлы с сервера.

Каталоги-заглушки (`timeline_files`, `temp`, …) нужны только чтобы PHP не падал при записи, если что-то всё же сохранится локально.
