<?php

if (!function_exists('telegram_write_log')) {

    function telegram_write_log($data)
    {
        $log_file = '/var/www/crm_prime_lt_usr/data/www/crm2.prime-ltd.su/mylog.txt';

        $date = date('Y-m-d H:i:s');

        if (is_array($data) || is_object($data)) {
            $data = print_r($data, true);
        }

        $message = "[$date] " . $data . PHP_EOL;

        file_put_contents($log_file, $message, FILE_APPEND);
    }
}
