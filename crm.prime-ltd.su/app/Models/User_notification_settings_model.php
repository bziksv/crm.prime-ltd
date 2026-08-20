<?php

namespace App\Models;

class User_notification_settings_model extends Crud_model {

    protected $table = 'user_notification_settings';

    protected $allowedFields = [
        "user_id",
        "notify_task_date_changed",
        "notify_task_assignees_changed",
        "notify_task_status_changed",
        "notify_task_comment_added",
        "notify_task_my_comment_added",
        "created_at",
        "updated_at"
    ];

    function __construct() {
        parent::__construct($this->table);
    }

    function get_by_user($user_id) {
        return $this->get_one_where(["user_id" => $user_id]);
    }

    function save_settings($user_id, $data) {
        $existing = $this->get_by_user($user_id);

        $data["updated_at"] = get_current_utc_time();
        $data["user_id"] = $user_id;

        if ($existing && $existing->id) {
            // явный update
            return $this->update($existing->id, $data);
        } else {
            $data["created_at"] = get_current_utc_time();
            return $this->insert($data);
        }
    }
}