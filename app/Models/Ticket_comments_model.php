<?php

namespace App\Models;

use CodeIgniter\I18n\Time;

class Ticket_comments_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ticket_comments';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $tickets_table = $this->db->prefixTable('tickets');
        $ticket_comments_table = $this->db->prefixTable('ticket_comments');
        $ticket_mails_table = $this->db->prefixTable('ticket_mails');
        $users_table = $this->db->prefixTable('users');
        $pin_ticket_comments_table = $this->db->prefixTable('pin_ticket_comments');
        $where = "";
        $sort = "ASC";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ticket_comments_table.id=$id";
        }

        $ticket_id = $this->_get_clean_value($options, "ticket_id");
        if ($ticket_id) {
            $where .= " AND $ticket_comments_table.ticket_id=$ticket_id";
        }

        $sort_decending = $this->_get_clean_value($options, "sort_as_decending");
        if ($sort_decending) {
            $sort = "DESC";
        }

        $is_note = $this->_get_clean_value($options, "is_note");
        if (!is_null($is_note)) {
            $where .= " AND $ticket_comments_table.is_note=$is_note";
        }

        $mail_stats_from = "$ticket_mails_table";
        $mail_stats_join_filter = "";
        $where_mail_stats = "";
        if ($ticket_id) {
            $mail_stats_from = "$ticket_mails_table tm INNER JOIN $ticket_comments_table tc_stats ON tc_stats.id = tm.ticket_comment_id AND tc_stats.deleted=0 AND tc_stats.ticket_id=$ticket_id";
            $mail_stats_join_filter = "tm.";
        } else if ($id) {
            $mail_stats_from = "$ticket_mails_table tm";
            $mail_stats_join_filter = "tm.";
            $where_mail_stats = "WHERE tm.ticket_comment_id=$id";
        }

        $mail_stats_subquery = "(SELECT {$mail_stats_join_filter}ticket_comment_id,
            COUNT(*) AS sent_mails,
            SUM(CASE WHEN {$mail_stats_join_filter}read_at IS NOT NULL THEN 1 ELSE 0 END) AS read_mails
            FROM $mail_stats_from " . ($id && !$ticket_id ? $where_mail_stats : "") . "
            GROUP BY {$mail_stats_join_filter}ticket_comment_id) AS mail_stats";

        $pin_select = ", 0 AS pinned_comment_status";
        $pin_join = "";
        $login_user_id = $this->_get_clean_value($options, "login_user_id");
        if ($login_user_id) {
            $pin_select = ", COALESCE(pin_stats.pinned_comment_status, 0) AS pinned_comment_status";
            $pin_join = " LEFT JOIN (
                SELECT ticket_comment_id, COUNT(*) AS pinned_comment_status
                FROM $pin_ticket_comments_table
                WHERE deleted=0 AND pinned_by=$login_user_id
                GROUP BY ticket_comment_id
            ) AS pin_stats ON pin_stats.ticket_comment_id=$ticket_comments_table.id";
        }

        $sql = "SELECT $ticket_comments_table.*, CONCAT($users_table.first_name, ' ',$users_table.last_name) AS created_by_user,
        $users_table.image as created_by_avatar,
        $users_table.email as created_by_email,
        $users_table.user_type,
        $tickets_table.creator_name,
        $tickets_table.creator_email,
        COALESCE(mail_stats.sent_mails, 0) AS sent_mails,
        COALESCE(mail_stats.read_mails, 0) AS read_mails
        $pin_select
        FROM $ticket_comments_table
        LEFT JOIN $users_table ON $users_table.id= $ticket_comments_table.created_by
        LEFT JOIN $tickets_table ON $tickets_table.id= $ticket_comments_table.ticket_id
        LEFT JOIN $mail_stats_subquery ON mail_stats.ticket_comment_id=$ticket_comments_table.id
        $pin_join
        WHERE $ticket_comments_table.deleted=0 $where
        ORDER BY $ticket_comments_table.created_at $sort";

        $limit = $this->_get_clean_value($options, "limit");
        if ($limit) {
            $offset = (int) ($this->_get_clean_value($options, "offset") ?: 0);
            $limit = (int) $limit;
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        return $this->db->query($sql);
    }

    function count_all($options = array()) {
        $ticket_comments_table = $this->db->prefixTable('ticket_comments');
        $where = "WHERE $ticket_comments_table.deleted=0";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ticket_comments_table.id=$id";
        }

        $ticket_id = $this->_get_clean_value($options, "ticket_id");
        if ($ticket_id) {
            $where .= " AND $ticket_comments_table.ticket_id=$ticket_id";
        }

        $is_note = $this->_get_clean_value($options, "is_note");
        if (!is_null($is_note)) {
            $where .= " AND $ticket_comments_table.is_note=$is_note";
        }

        $sql = "SELECT COUNT($ticket_comments_table.id) AS total FROM $ticket_comments_table $where";

        return (int) $this->db->query($sql)->getRow()->total;
    }
}
