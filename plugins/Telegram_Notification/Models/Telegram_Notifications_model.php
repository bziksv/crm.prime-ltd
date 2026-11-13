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

}
