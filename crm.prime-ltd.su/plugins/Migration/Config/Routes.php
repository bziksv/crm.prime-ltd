<?php

namespace Config;

$routes = Services::routes();

$namespace = ['namespace' => 'Migration\Controllers'];

$routes->get('migration', 'Migration::index', $namespace);
$routes->get('migration/settings', 'Migration::settings', $namespace);
$routes->post('migration/settings/(:any)', 'Migration::$1', $namespace);
