<?php

namespace Config;

$routes = Services::routes();

$routes->get('telegram_notification_settings', 'Telegram_Notification_settings::index', ['namespace' => 'Telegram_Notification\Controllers']);
$routes->get('telegram_notification_settings/(:any)', 'Telegram_Notification_settings::$1', ['namespace' => 'Telegram_Notification\Controllers']);
$routes->post('telegram_notification_settings/(:any)', 'Telegram_Notification_settings::$1', ['namespace' => 'Telegram_Notification\Controllers']);

$routes->get('telegram_notification_updates', 'Telegram_Notification_Updates::index', ['namespace' => 'Telegram_Notification\Controllers']);
$routes->get('telegram_notification_updates/(:any)', 'Telegram_Notification_Updates::$1', ['namespace' => 'Telegram_Notification\Controllers']);
