<?php

namespace App\Models;

use CodeIgniter\I18n\Time;

class Ticket_mails_model extends Crud_model
{
    protected $table = null;

    function __construct() {
        $this->table = 'ticket_mails';
        parent::__construct($this->table);
    }

    function get_recipients_by_comment_ids(array $comment_ids) {
        $comment_ids = array_values(array_filter(array_map('intval', $comment_ids)));
        if (!$comment_ids) {
            return array();
        }

        $ticket_mails_table = $this->db->prefixTable('ticket_mails');
        $users_table = $this->db->prefixTable('users');
        $ids_list = implode(',', $comment_ids);

        $sql = "SELECT $ticket_mails_table.ticket_comment_id, $users_table.id, $users_table.email,
            $users_table.is_admin, $users_table.first_name, $users_table.last_name
            FROM $ticket_mails_table
            INNER JOIN $users_table ON $users_table.id=$ticket_mails_table.to_user_id
            WHERE $ticket_mails_table.ticket_comment_id IN ($ids_list)
            AND $users_table.deleted=0
            ORDER BY $ticket_mails_table.ticket_comment_id, $users_table.first_name";

        $rows = $this->db->query($sql)->getResult();
        $map = array();

        foreach ($rows as $row) {
            if (!$row->email || $row->is_admin != '0') {
                continue;
            }

            if (!isset($map[$row->ticket_comment_id])) {
                $map[$row->ticket_comment_id] = array();
            }

            $map[$row->ticket_comment_id][$row->id] = $row;
        }

        foreach ($map as $comment_id => $users) {
            $map[$comment_id] = array_values($users);
        }

        return $map;
    }

}
