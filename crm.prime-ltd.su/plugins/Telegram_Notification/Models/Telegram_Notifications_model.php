<?php

namespace Telegram_Notification\Models;

class Telegram_Notifications_model extends \App\Models\Crud_model {

    protected $table = null;
    private $Notifications_model;

    function __construct() {
        $this->table = 'telegram_notification_settings';
        $this->Notifications_model = model("App\Models\Notifications_model");
        parent::__construct($this->table);
    }

    function create_notification($notification_id) {
        $this->testLog('Вызов события уведомления');
        $notification_info = $this->Notifications_model->get_one_where(array("id" => $notification_id));
        $telegram_notification_settings_table = $this->db->prefixTable('telegram_notification_settings');
        $query = "SELECT * FROM $telegram_notification_settings_table 
                WHERE $telegram_notification_settings_table.event='$notification_info->event' 
                AND $telegram_notification_settings_table.enable_telegram";

        $telegram_notification_settings = $this->db->query($query)->getRow();

        if (!$telegram_notification_settings) {
            return false;
        }

        send_telegram_notification($notification_info->event, $notification_info->user_id, $notification_id);
    }

    public function testLog($data)
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
