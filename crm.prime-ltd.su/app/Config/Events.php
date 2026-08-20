<?php

namespace Config;

use CodeIgniter\Events\Events;
use App\Controllers\App_Controller;
use CodeIgniter\Exceptions\FrameworkException;


/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('sent_app_mail', static function ($user_notify_to, $notification) {
    Events::trigger($notification->event . "_and_mail_sent", $user_notify_to, $notification);
});

Events::on('ticket_commented_and_mail_sent', static function ($user_notify_to, $notification) {

    $data = [
        "ticket_comment_id" => $notification->ticket_comment_id,
        "from_user_id" => $notification->user_id,
        "to_user_id" => $user_notify_to->id,
        "created_at" => get_current_utc_time()
    ];

    $data = clean_data($data);

    $ci = new App_Controller();

    $ci->Ticket_mails_model->ci_save($data);
});

Events::on('pre_system', static function () {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && !is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        Services::toolbar()->respond();
    }

    //load php hooks library
    require_once(APPPATH . "ThirdParty/PHP-Hooks/php-hooks.php");

    helper('plugin');

    define('PLUGINPATH', ROOTPATH . 'plugins/'); //define plugin path
    define('PLUGIN_URL_PATH', 'plugins/'); //define plugin path

    load_plugin_indexes();

    include APPPATH . 'Config/RiseHooks.php';
    include APPPATH . 'Config/RiseCustomHooks.php';
});

function load_plugin_indexes() {
    $plugins = file_get_contents(APPPATH . "Config/activated_plugins.json");
    $plugins = @json_decode($plugins);

    if (!($plugins && is_array($plugins) && count($plugins))) {
        return false;
    }

    foreach ($plugins as $plugin) {
        $index_file = PLUGINPATH . $plugin . '/index.php';

        if (file_exists($index_file)) {
            include $index_file;
        }
    }
}
