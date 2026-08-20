<?php

namespace Telegram_Notification\Models;

class Telegram_Notification_settings_model extends \App\Models\Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'telegram_notification_settings';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $telegram_notification_settings_table = $this->db->prefixTable('telegram_notification_settings');
        $notification_settings_table = $this->db->prefixTable('notification_settings');

        $where = "";
        $id = get_array_value($options, "id");
        if ($id) {
            $where = " AND $notification_settings_table.id=$id";
        }

        $category = get_array_value($options, "category");
        if ($category) {
            $where .= " AND $notification_settings_table.category='$category'";
        }

        $sql = "SELECT $notification_settings_table.*, $telegram_notification_settings_table.enable_telegram AS enable_telegram
        FROM $notification_settings_table
        LEFT JOIN $telegram_notification_settings_table ON $telegram_notification_settings_table.event=$notification_settings_table.event
        WHERE $notification_settings_table.deleted=0 $where 
        ORDER BY $notification_settings_table.sort ASC";

        return $this->db->query($sql);
    }

}
