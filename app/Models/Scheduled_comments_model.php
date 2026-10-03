<?php

namespace App\Models;

class Scheduled_comments_model extends Crud_model {

    function __construct() {
        $this->table = 'scheduled_comments';
        parent::__construct($this->table);
        $this->ensure_table();
        $this->ensure_columns();
    }

    function ensure_table() {
        if ($this->db->tableExists($this->table_without_prefix)) {
            return;
        }

        $table = $this->table;
        $this->db->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `created_by` int(11) NOT NULL,
            `task_id` int(11) NOT NULL DEFAULT 0,
            `ticket_id` int(11) NOT NULL DEFAULT 0,
            `project_id` int(11) NOT NULL DEFAULT 0,
            `description` text,
            `files` longtext,
            `is_note` tinyint(1) NOT NULL DEFAULT 0,
            `scheduled_at` datetime NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `sent_comment_id` int(11) DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `deleted` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `idx_due` (`deleted`, `status`, `scheduled_at`),
            KEY `idx_task` (`task_id`, `deleted`, `status`),
            KEY `idx_ticket` (`ticket_id`, `deleted`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    function ensure_columns() {
        if (!$this->db->tableExists($this->table_without_prefix)) {
            return;
        }

        $table = $this->table;
        if (!$this->db->fieldExists("ticket_id", $this->table_without_prefix)) {
            $this->db->query("ALTER TABLE `$table` ADD `ticket_id` int(11) NOT NULL DEFAULT 0 AFTER `task_id`, ADD KEY `idx_ticket` (`ticket_id`, `deleted`, `status`)");
        }
        if (!$this->db->fieldExists("is_note", $this->table_without_prefix)) {
            $this->db->query("ALTER TABLE `$table` ADD `is_note` tinyint(1) NOT NULL DEFAULT 0 AFTER `files`");
        }
    }

    function get_pending_for_task($task_id, $user_id) {
        return $this->get_pending_for("task_id", $task_id, $user_id);
    }

    function get_pending_for_ticket($ticket_id, $user_id) {
        return $this->get_pending_for("ticket_id", $ticket_id, $user_id);
    }

    private function get_pending_for($column, $id, $user_id) {
        $table = $this->table;
        $users_table = $this->db->prefixTable("users");
        $id = (int) $id;
        $user_id = (int) $user_id;
        $column = $column === "ticket_id" ? "ticket_id" : "task_id";

        $sql = "SELECT $table.*,
                    CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_user,
                    $users_table.image AS created_by_avatar
                FROM $table
                LEFT JOIN $users_table ON $users_table.id = $table.created_by
                WHERE $table.deleted = 0
                  AND $table.status = 'pending'
                  AND $table.$column = $id
                  AND $table.created_by = $user_id
                ORDER BY $table.scheduled_at ASC";

        return $this->db->query($sql)->getResult();
    }

    function get_due_pending($now_utc) {
        $table = $this->table;
        $now_utc = $this->db->escape($now_utc);
        $sql = "SELECT * FROM $table
                WHERE deleted = 0
                  AND status = 'pending'
                  AND scheduled_at <= $now_utc
                ORDER BY scheduled_at ASC, id ASC
                LIMIT 50";
        return $this->db->query($sql)->getResult();
    }

    function mark_sent($id, $comment_id) {
        $data = array(
            "status" => "sent",
            "sent_comment_id" => (int) $comment_id,
        );
        return $this->ci_save($data, (int) $id);
    }

    function cancel($id, $user_id) {
        $row = $this->get_one((int) $id);
        if (!$row || !$row->id || (int) $row->deleted === 1) {
            return false;
        }
        if ((int) $row->created_by !== (int) $user_id) {
            return false;
        }
        if ($row->status !== "pending") {
            return false;
        }
        $data = array("status" => "cancelled");
        return $this->ci_save($data, (int) $id);
    }

    function publish_due() {
        $due = $this->get_due_pending(get_current_utc_time());
        $published = 0;
        foreach ($due as $row) {
            if ($this->publish_one($row)) {
                $published++;
            }
        }
        return $published;
    }

    function publish_one($row) {
        if (!$row || $row->status !== "pending") {
            return false;
        }

        if (!empty($row->ticket_id)) {
            return $this->publish_ticket($row);
        }

        return $this->publish_task($row);
    }

    private function publish_task($row) {
        $comments_model = model("App\Models\Project_comments_model");
        $data = array(
            "created_by" => $row->created_by,
            "created_at" => get_current_utc_time(),
            "project_id" => $row->project_id ? $row->project_id : 0,
            "file_id" => 0,
            "task_id" => $row->task_id,
            "customer_feedback_id" => 0,
            "comment_id" => 0,
            "description" => $row->description,
            "files" => $row->files ? $row->files : serialize(array()),
        );

        $save_id = $comments_model->save_comment($data);
        if (!$save_id) {
            return false;
        }

        $this->mark_sent($row->id, $save_id);

        $comment_info = $comments_model->get_one($save_id);
        $task_info = model("App\Models\Tasks_model")->get_one($comment_info->task_id);
        $notification_options = array("task_id" => $comment_info->task_id, "project_comment_id" => $save_id);

        if ($comment_info->project_id) {
            $notification_options["project_id"] = $comment_info->project_id;
            log_notification("project_task_commented", $notification_options, $row->created_by);
        } else if ($task_info && $task_info->context) {
            $context_id_key = $task_info->context . "_id";
            $context_id_value = $task_info->{$task_info->context . "_id"};
            $notification_options[$context_id_key] = $context_id_value;
            log_notification("general_task_commented", $notification_options, $row->created_by);
        }

        return $save_id;
    }

    private function publish_ticket($row) {
        $now = get_current_utc_time();
        $is_note = !empty($row->is_note) ? 1 : 0;
        $ticket_id = (int) $row->ticket_id;

        $comment_data = array(
            "description" => $row->description,
            "ticket_id" => $ticket_id,
            "created_by" => $row->created_by,
            "created_at" => $now,
            "files" => $row->files ? $row->files : serialize(array()),
            "is_note" => $is_note,
        );

        $comments_model = model("App\Models\Ticket_comments_model");
        $save_id = $comments_model->ci_save($comment_data);
        if (!$save_id) {
            return false;
        }

        $author = model("App\Models\Users_model")->get_one($row->created_by);
        $ticket_data = array(
            "status" => ($author && $author->user_type === "client") ? "client_replied" : "open",
            "last_activity_at" => $now,
        );
        model("App\Models\Tickets_model")->ci_save($ticket_data, $ticket_id);

        $this->mark_sent($row->id, $save_id);

        if (!$is_note) {
            log_notification("ticket_commented", array("ticket_id" => $ticket_id, "ticket_comment_id" => $save_id), $row->created_by);
        } else {
            helper("notifications");
            $string = json_encode(array(
                "ticket_id" => $ticket_id,
                "ticket_comment_id" => $save_id,
                "ticket_comment_description" => $row->description,
            ));
            send_telegram_notification("ticket_commented_note|$string");
        }

        return $save_id;
    }
}
