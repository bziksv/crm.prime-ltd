<?php

namespace Config;

$routes = Services::routes();

$password_manager_namespace = ['namespace' => 'Password_manager\Controllers'];

$routes->get('password_manager', 'Password_manager::index', $password_manager_namespace);
$routes->post('password_manager/(:any)', 'Password_manager::$1', $password_manager_namespace);
$routes->get('password_manager/(:any)', 'Password_manager::$1', $password_manager_namespace);

$routes->get('password_manager_updates', 'Password_manager_Updates::index', $password_manager_namespace);
$routes->get('password_manager_updates/(:any)', 'Password_manager_Updates::$1', $password_manager_namespace);
