<?php

namespace App\Models;

class Chat_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'chat_conversations';
        parent::__construct($this->table);
        $this->ensure_schema();
    }

    private function ensure_schema() {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $c = $this->conversations_table();
        $m = $this->members_table();
        $msg = $this->messages_table();

        $this->db->query("CREATE TABLE IF NOT EXISTS `$c` (
          id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          type ENUM('dm','group') NOT NULL DEFAULT 'dm',
          title VARCHAR(255) NULL,
          created_by INT NOT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          last_message TEXT NULL,
          last_message_at DATETIME NULL,
          last_message_user_id INT NULL,
          deleted TINYINT(1) NOT NULL DEFAULT 0,
          KEY idx_updated (updated_at),
          KEY idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS `$m` (
          id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          conversation_id INT NOT NULL,
          user_id INT NOT NULL,
          is_admin TINYINT(1) NOT NULL DEFAULT 0,
          last_read_message_id INT NOT NULL DEFAULT 0,
          joined_at DATETIME NOT NULL,
          deleted TINYINT(1) NOT NULL DEFAULT 0,
          UNIQUE KEY uq_conv_user (conversation_id, user_id),
          KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS `$msg` (
          id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          conversation_id INT NOT NULL,
          from_user_id INT NOT NULL,
          is_system TINYINT(1) NOT NULL DEFAULT 0,
          message TEXT NOT NULL,
          files TEXT NULL,
          created_at DATETIME NOT NULL,
          edited_at DATETIME NULL DEFAULT NULL,
          deleted TINYINT(1) NOT NULL DEFAULT 0,
          legacy_id INT NULL DEFAULT NULL,
          reply_to_message_id INT NULL DEFAULT NULL,
          pinned_at DATETIME NULL DEFAULT NULL,
          pinned_by INT NULL DEFAULT NULL,
          KEY idx_conv_id (conversation_id, id),
          KEY idx_from (from_user_id),
          KEY idx_reply (reply_to_message_id),
          KEY idx_pinned (conversation_id, pinned_at),
          UNIQUE KEY uq_legacy (legacy_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $legacy_col = $this->db->query("SHOW COLUMNS FROM `$msg` LIKE 'legacy_id'")->getResult();
        if (!$legacy_col) {
            $this->db->query("ALTER TABLE `$msg` ADD COLUMN legacy_id INT NULL DEFAULT NULL");
            $this->db->query("ALTER TABLE `$msg` ADD UNIQUE KEY uq_legacy (legacy_id)");
        }

        $reply_col = $this->db->query("SHOW COLUMNS FROM `$msg` LIKE 'reply_to_message_id'")->getResult();
        if (!$reply_col) {
            $this->db->query("ALTER TABLE `$msg` ADD COLUMN reply_to_message_id INT NULL DEFAULT NULL");
            $this->db->query("ALTER TABLE `$msg` ADD KEY idx_reply (reply_to_message_id)");
        }

        $pin_col = $this->db->query("SHOW COLUMNS FROM `$msg` LIKE 'pinned_at'")->getResult();
        if (!$pin_col) {
            $this->db->query("ALTER TABLE `$msg` ADD COLUMN pinned_at DATETIME NULL DEFAULT NULL");
            $this->db->query("ALTER TABLE `$msg` ADD COLUMN pinned_by INT NULL DEFAULT NULL");
            $this->db->query("ALTER TABLE `$msg` ADD KEY idx_pinned (conversation_id, pinned_at)");
        }

        $edited_col = $this->db->query("SHOW COLUMNS FROM `$msg` LIKE 'edited_at'")->getResult();
        if (!$edited_col) {
            $this->db->query("ALTER TABLE `$msg` ADD COLUMN edited_at DATETIME NULL DEFAULT NULL");
        }

        $star_col = $this->db->query("SHOW COLUMNS FROM `$m` LIKE 'is_starred'")->getResult();
        if (!$star_col) {
            $this->db->query("ALTER TABLE `$m` ADD COLUMN is_starred TINYINT(1) NOT NULL DEFAULT 0 AFTER last_read_message_id");
            $this->db->query("ALTER TABLE `$m` ADD KEY idx_starred (user_id, is_starred)");
        }

        $avatar_col = $this->db->query("SHOW COLUMNS FROM `$c` LIKE 'avatar'")->getResult();
        if (!$avatar_col) {
            $this->db->query("ALTER TABLE `$c` ADD COLUMN avatar TEXT NULL AFTER title");
        }

        $sys_col = $this->db->query("SHOW COLUMNS FROM `$msg` LIKE 'is_system'")->getResult();
        if (!$sys_col) {
            $this->db->query("ALTER TABLE `$msg` ADD COLUMN is_system TINYINT(1) NOT NULL DEFAULT 0 AFTER from_user_id");
            $this->db->query("ALTER TABLE `$msg` ADD KEY idx_system (conversation_id, is_system)");
        }

        $this->import_legacy_messages();
    }

    /**
     * One-shot: fold old rise_messages (subject threads) into DM conversations.
     * Idempotent via chat_messages.legacy_id.
     */
    function import_legacy_messages() {
        $msg_table = $this->messages_table();
        $conv_table = $this->conversations_table();
        $members_table = $this->members_table();
        $legacy_table = $this->db->prefixTable('messages');

        $pending = $this->db->query("SELECT COUNT(*) AS c FROM `$legacy_table` old
            WHERE old.deleted=0
              AND NOT EXISTS (
                SELECT 1 FROM `$msg_table` nm WHERE nm.legacy_id = old.id
              )")->getRow();
        if (!$pending || !(int) $pending->c) {
            return;
        }

        $dm_map = array();
        $dm_rows = $this->db->query("SELECT c.id, GROUP_CONCAT(m.user_id ORDER BY m.user_id) AS members
            FROM `$conv_table` c
            JOIN `$members_table` m ON m.conversation_id=c.id AND m.deleted=0
            WHERE c.type='dm' AND c.deleted=0
            GROUP BY c.id")->getResult();
        foreach ($dm_rows as $row) {
            $parts = array_filter(explode(',', (string) $row->members));
            if (count($parts) === 2) {
                $a = (int) $parts[0];
                $b = (int) $parts[1];
                $key = $a < $b ? "$a:$b" : "$b:$a";
                $dm_map[$key] = (int) $row->id;
            }
        }

        $rows = $this->db->query("SELECT id, subject, message, from_user_id, to_user_id, message_id, created_at
            FROM `$legacy_table`
            WHERE deleted=0
            ORDER BY id ASC")->getResult();

        $last_by_conv = array();

        foreach ($rows as $old) {
            $legacy_id = (int) $old->id;
            $exists = $this->db->query("SELECT id FROM `$msg_table` WHERE legacy_id=$legacy_id LIMIT 1")->getRow();
            if ($exists) {
                continue;
            }

            $from = (int) $old->from_user_id;
            $to = (int) $old->to_user_id;
            if (!$from || !$to || $from === $to) {
                continue;
            }

            $key = $from < $to ? "$from:$to" : "$to:$from";
            if (!isset($dm_map[$key])) {
                $created = $this->db->escape($old->created_at);
                $this->db->query("INSERT INTO `$conv_table` (type, title, created_by, created_at, updated_at, deleted)
                    VALUES ('dm', NULL, $from, $created, $created, 0)");
                $cid = (int) $this->db->insertID();
                $this->db->query("INSERT INTO `$members_table` (conversation_id, user_id, is_admin, last_read_message_id, joined_at, deleted)
                    VALUES ($cid, $from, 1, 0, $created, 0), ($cid, $to, 0, 0, $created, 0)");
                $dm_map[$key] = $cid;
            }
            $cid = $dm_map[$key];

            $text = $this->clean_legacy_message_text($old->message);
            if ((int) $old->message_id === 0 && trim((string) $old->subject) !== '') {
                $subj = $this->clean_legacy_message_text($old->subject);
                if ($subj !== '') {
                    $text = $text !== '' ? ($subj . "\n\n" . $text) : $subj;
                }
            }
            if ($text === '') {
                $text = '—';
            }

            $esc = $this->db->escape($text);
            $created = $this->db->escape($old->created_at);
            $this->db->query("INSERT INTO `$msg_table` (conversation_id, from_user_id, message, files, created_at, deleted, legacy_id)
                VALUES ($cid, $from, $esc, '', $created, 0, $legacy_id)");
            $new_id = (int) $this->db->insertID();
            if ($new_id) {
                $last_by_conv[$cid] = array(
                    'id' => $new_id,
                    'text' => $text,
                    'from' => $from,
                    'at' => $old->created_at,
                );
            }
        }

        foreach ($last_by_conv as $cid => $last) {
            $preview = $this->db->escape(mb_substr($last['text'], 0, 200));
            $at = $this->db->escape($last['at']);
            $from = (int) $last['from'];
            $mid = (int) $last['id'];
            $this->db->query("UPDATE `$conv_table`
                SET last_message=$preview, last_message_at=$at, last_message_user_id=$from, updated_at=$at
                WHERE id=$cid");
            $this->db->query("UPDATE `$members_table`
                SET last_read_message_id=$mid
                WHERE conversation_id=$cid AND user_id=$from");
        }
    }

    private function clean_legacy_message_text($html) {
        $t = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('/[ \t]+/', ' ', $t);
        $t = preg_replace("/\n{3,}/", "\n\n", trim($t));
        return $t;
    }

    private function conversations_table() {
        return $this->db->prefixTable('chat_conversations');
    }

    private function members_table() {
        return $this->db->prefixTable('chat_conversation_members');
    }

    private function messages_table() {
        return $this->db->prefixTable('chat_messages');
    }

    private function users_table() {
        return $this->db->prefixTable('users');
    }

    function get_staff_users($exclude_user_id = 0) {
        $users = $this->users_table();
        $exclude_user_id = intval($exclude_user_id);

        $sql = "SELECT $users.id, $users.first_name, $users.last_name, $users.image, $users.last_online, $users.job_title
            FROM $users
            WHERE $users.deleted=0 AND $users.status='active' AND $users.user_type='staff'
                AND $users.id!=$exclude_user_id
            ORDER BY $users.first_name, $users.last_name";

        return $this->db->query($sql)->getResult();
    }

    function get_staff_profile($user_id) {
        $users = $this->users_table();
        $user_id = intval($user_id);
        if (!$user_id) {
            return null;
        }

        return $this->db->query(
            "SELECT id, first_name, last_name, image, last_online, job_title, email, phone, created_at
             FROM $users
             WHERE id=$user_id AND deleted=0 AND status='active' AND user_type='staff'
             LIMIT 1"
        )->getRow();
    }

    function get_staff_job_titles($exclude_user_id = 0) {
        $users = $this->users_table();
        $exclude_user_id = intval($exclude_user_id);

        $sql = "SELECT DISTINCT TRIM($users.job_title) AS job_title
            FROM $users
            WHERE $users.deleted=0 AND $users.status='active' AND $users.user_type='staff'
                AND $users.id!=$exclude_user_id
                AND TRIM($users.job_title) != ''
                AND TRIM($users.job_title) != 'Untitled'
            ORDER BY job_title";

        $rows = $this->db->query($sql)->getResult();
        $titles = array();
        foreach ($rows as $row) {
            if ($row->job_title) {
                $titles[] = $row->job_title;
            }
        }
        return $titles;
    }

    function user_is_member($conversation_id, $user_id) {
        $members = $this->members_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);

        $row = $this->db->query(
            "SELECT id FROM $members WHERE conversation_id=$conversation_id AND user_id=$user_id AND deleted=0 LIMIT 1"
        )->getRow();

        return $row ? true : false;
    }

    function user_had_membership($conversation_id, $user_id) {
        $members = $this->members_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);

        $row = $this->db->query(
            "SELECT id, deleted FROM $members WHERE conversation_id=$conversation_id AND user_id=$user_id LIMIT 1"
        )->getRow();

        return $row ?: null;
    }

    function restore_membership($conversation_id, $user_id) {
        $m = $this->members_table();
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);
        if (!$conversation_id || !$user_id) {
            return false;
        }

        $row = $this->user_had_membership($conversation_id, $user_id);
        if (!$row) {
            return false;
        }

        $now = $this->db->escape(get_current_utc_time());
        $this->db->query("UPDATE $c SET deleted=0, updated_at=$now WHERE id=$conversation_id");
        $this->db->query(
            "UPDATE $m SET deleted=0, joined_at=$now WHERE id=" . intval($row->id)
        );

        return true;
    }

    function get_conversation($conversation_id, $user_id) {
        $c = $this->conversations_table();
        $m = $this->members_table();
        $u = $this->users_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);

        $sql = "SELECT $c.*, my.last_read_message_id,
            (SELECT COUNT(*) FROM $m om WHERE om.conversation_id=$c.id AND om.deleted=0) AS members_count
            FROM $c
            INNER JOIN $m my ON my.conversation_id=$c.id AND my.user_id=$user_id AND my.deleted=0
            WHERE $c.id=$conversation_id AND $c.deleted=0
            LIMIT 1";

        $conversation = $this->db->query($sql)->getRow();
        if (!$conversation) {
            return null;
        }

        if ($conversation->type === 'dm') {
            $peer_sql = "SELECT $u.id, $u.first_name, $u.last_name, $u.image, $u.last_online, $u.job_title
                FROM $m
                INNER JOIN $u ON $u.id=$m.user_id
                WHERE $m.conversation_id=$conversation_id AND $m.user_id!=$user_id AND $m.deleted=0
                LIMIT 1";
            $peer = $this->db->query($peer_sql)->getRow();
            $conversation->peer = $peer;
            $conversation->display_title = $peer
                ? trim($peer->first_name . ' ' . $peer->last_name)
                : app_lang('chat');
            $conversation->display_image = $peer ? $peer->image : '';
        } else {
            $conversation->peer = null;
            $conversation->display_title = $conversation->title ?: app_lang('group');
            $conversation->display_image = $conversation->avatar ?? '';
        }

        return $conversation;
    }

    function get_inbox($user_id, $limit = 50) {
        $c = $this->conversations_table();
        $m = $this->members_table();
        $u = $this->users_table();
        $msg = $this->messages_table();
        $user_id = intval($user_id);
        $limit = intval($limit);

        $sql = "SELECT $c.*, my.last_read_message_id, IFNULL(my.is_starred, 0) AS is_starred,
            (SELECT COUNT(*) FROM $msg cm
                WHERE cm.conversation_id=$c.id AND cm.deleted=0
                  AND cm.id > IFNULL(my.last_read_message_id, 0)
                  AND cm.from_user_id!=$user_id) AS unread_count,
            peer.id AS peer_id,
            peer.first_name AS peer_first_name,
            peer.last_name AS peer_last_name,
            peer.image AS peer_image,
            peer.last_online AS peer_last_online,
            sender.first_name AS last_sender_first_name,
            sender.last_name AS last_sender_last_name
            FROM $c
            INNER JOIN $m my ON my.conversation_id=$c.id AND my.user_id=$user_id AND my.deleted=0
            LEFT JOIN $m peer_m ON peer_m.conversation_id=$c.id AND peer_m.deleted=0 AND peer_m.user_id!=$user_id AND $c.type='dm'
            LEFT JOIN $u peer ON peer.id=peer_m.user_id
            LEFT JOIN $u sender ON sender.id=$c.last_message_user_id
            WHERE $c.deleted=0
            ORDER BY IFNULL(my.is_starred, 0) DESC, COALESCE($c.last_message_at, $c.updated_at) DESC
            LIMIT $limit";

        $rows = $this->db->query($sql)->getResult();

        foreach ($rows as $row) {
            if ($row->type === 'dm') {
                $row->display_title = trim(($row->peer_first_name ?? '') . ' ' . ($row->peer_last_name ?? ''));
                $row->display_image = $row->peer_image ?? '';
            } else {
                $row->display_title = $row->title ?: app_lang('group');
                $row->display_image = $row->avatar ?? '';
            }

            $preview = $row->last_message ?: '';
            $preview = strip_tags($preview);
            if (mb_strlen($preview) > 80) {
                $preview = mb_substr($preview, 0, 80) . '…';
            }
            $row->preview = $preview;
        }

        return $rows;
    }

    function find_dm_between($user_a, $user_b) {
        $c = $this->conversations_table();
        $m = $this->members_table();
        $user_a = intval($user_a);
        $user_b = intval($user_b);

        $sql = "SELECT $c.id
            FROM $c
            INNER JOIN $m m1 ON m1.conversation_id=$c.id AND m1.user_id=$user_a AND m1.deleted=0
            INNER JOIN $m m2 ON m2.conversation_id=$c.id AND m2.user_id=$user_b AND m2.deleted=0
            WHERE $c.type='dm' AND $c.deleted=0
              AND (SELECT COUNT(*) FROM $m mx WHERE mx.conversation_id=$c.id AND mx.deleted=0)=2
            LIMIT 1";

        $row = $this->db->query($sql)->getRow();
        return $row ? (int) $row->id : 0;
    }

    function find_dm_including_deleted($user_a, $user_b) {
        $c = $this->conversations_table();
        $m = $this->members_table();
        $user_a = intval($user_a);
        $user_b = intval($user_b);

        $sql = "SELECT $c.id
            FROM $c
            INNER JOIN $m m1 ON m1.conversation_id=$c.id AND m1.user_id=$user_a
            INNER JOIN $m m2 ON m2.conversation_id=$c.id AND m2.user_id=$user_b
            WHERE $c.type='dm'
              AND (SELECT COUNT(*) FROM $m mx WHERE mx.conversation_id=$c.id)=2
            ORDER BY $c.deleted ASC, $c.id DESC
            LIMIT 1";

        $row = $this->db->query($sql)->getRow();
        return $row ? (int) $row->id : 0;
    }

    function create_dm($user_a, $user_b) {
        $existing = $this->find_dm_between($user_a, $user_b);
        if ($existing) {
            return $existing;
        }

        $c = $this->conversations_table();
        $user_a = intval($user_a);
        $user_b = intval($user_b);
        $now = $this->db->escape(get_current_utc_time());

        // Restore previously left/deleted DM instead of creating a duplicate.
        $restorable = $this->find_dm_including_deleted($user_a, $user_b);
        if ($restorable) {
            $this->db->query("UPDATE $c SET deleted=0, updated_at=$now WHERE id=$restorable");
            $this->add_member($restorable, $user_a, 1);
            $this->add_member($restorable, $user_b, 0);
            return $restorable;
        }

        $this->db->query(
            "INSERT INTO $c (type, title, created_by, created_at, updated_at, deleted)
             VALUES ('dm', NULL, $user_a, $now, $now, 0)"
        );
        $conversation_id = (int) $this->db->insertID();
        if (!$conversation_id) {
            return 0;
        }

        $this->add_member($conversation_id, $user_a, 1);
        $this->add_member($conversation_id, $user_b, 0);

        return $conversation_id;
    }

    function clear_history($conversation_id) {
        $msg = $this->messages_table();
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        if (!$conversation_id) {
            return false;
        }

        $now = $this->db->escape(get_current_utc_time());
        $this->db->query(
            "UPDATE $msg SET deleted=1, pinned_at=NULL, pinned_by=NULL
             WHERE conversation_id=$conversation_id AND deleted=0"
        );
        $this->db->query(
            "UPDATE $c SET last_message=NULL, last_message_at=NULL, last_message_user_id=NULL, updated_at=$now
             WHERE id=$conversation_id"
        );

        return true;
    }

    function leave_conversation($conversation_id, $user_id) {
        $m = $this->members_table();
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);
        if (!$conversation_id || !$user_id) {
            return false;
        }

        $conv = $this->db->query(
            "SELECT id, type FROM $c WHERE id=$conversation_id AND deleted=0 LIMIT 1"
        )->getRow();

        if ($conv && $conv->type === 'group') {
            $name = $this->get_user_display_name($user_id);
            if ($name !== '') {
                $this->send_system_message($conversation_id, $user_id, $name . ' покинул(а) чат');
            }
        }

        $this->db->query(
            "UPDATE $m SET deleted=1
             WHERE conversation_id=$conversation_id AND user_id=$user_id AND deleted=0"
        );

        $active = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM $m WHERE conversation_id=$conversation_id AND deleted=0"
        )->getRow();

        if (!$active || !(int) $active->cnt) {
            $now = $this->db->escape(get_current_utc_time());
            $this->db->query("UPDATE $c SET deleted=1, updated_at=$now WHERE id=$conversation_id");
        }

        return true;
    }

    function toggle_star($conversation_id, $user_id) {
        $m = $this->members_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);
        if (!$conversation_id || !$user_id) {
            return null;
        }

        $row = $this->db->query(
            "SELECT id, IFNULL(is_starred, 0) AS is_starred
             FROM $m
             WHERE conversation_id=$conversation_id AND user_id=$user_id AND deleted=0
             LIMIT 1"
        )->getRow();

        if (!$row) {
            return null;
        }

        $next = ((int) $row->is_starred) ? 0 : 1;
        $this->db->query(
            "UPDATE $m SET is_starred=$next WHERE id=" . intval($row->id)
        );

        return array('starred' => (bool) $next);
    }

    function create_group($title, $created_by, array $member_ids) {
        $c = $this->conversations_table();
        $created_by = intval($created_by);
        $title = trim($title);
        $escaped_title = $this->db->escape($title);
        $now = $this->db->escape(get_current_utc_time());

        $this->db->query(
            "INSERT INTO $c (type, title, created_by, created_at, updated_at, deleted)
             VALUES ('group', $escaped_title, $created_by, $now, $now, 0)"
        );
        $conversation_id = (int) $this->db->insertID();
        if (!$conversation_id) {
            return 0;
        }

        $member_ids = array_unique(array_map('intval', $member_ids));
        if (!in_array($created_by, $member_ids, true)) {
            $member_ids[] = $created_by;
        }

        foreach ($member_ids as $uid) {
            if ($uid > 0) {
                $this->add_member($conversation_id, $uid, $uid === $created_by ? 1 : 0);
            }
        }

        $creator_name = $this->get_user_display_name($created_by);
        $this->send_system_message($conversation_id, $created_by, $creator_name . ' создал(а) группу');

        $added_names = array();
        foreach ($member_ids as $uid) {
            if ($uid > 0 && $uid !== $created_by) {
                $n = $this->get_user_display_name($uid);
                if ($n !== '') {
                    $added_names[] = $n;
                }
            }
        }
        if ($added_names) {
            $this->send_system_message(
                $conversation_id,
                $created_by,
                $creator_name . ' добавил(а) в чат: ' . implode(', ', $added_names)
            );
        }

        return $conversation_id;
    }

    function get_user_display_name($user_id) {
        $u = $this->users_table();
        $user_id = intval($user_id);
        if (!$user_id) {
            return '';
        }
        $row = $this->db->query(
            "SELECT first_name, last_name FROM $u WHERE id=$user_id LIMIT 1"
        )->getRow();
        if (!$row) {
            return '';
        }
        return trim($row->first_name . ' ' . $row->last_name);
    }

    function send_system_message($conversation_id, $actor_user_id, $text) {
        $msg = $this->messages_table();
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        $actor_user_id = intval($actor_user_id);
        $text = trim((string) $text);
        if (!$conversation_id || $text === '') {
            return 0;
        }

        $now = get_current_utc_time();
        $escaped_message = $this->db->escape($text);
        $escaped_now = $this->db->escape($now);
        $escaped_preview = $this->db->escape(mb_strlen($text) > 200 ? mb_substr($text, 0, 200) : $text);

        $this->db->query(
            "INSERT INTO $msg (conversation_id, from_user_id, is_system, message, files, created_at, deleted, reply_to_message_id)
             VALUES ($conversation_id, $actor_user_id, 1, $escaped_message, '', $escaped_now, 0, NULL)"
        );
        $message_id = (int) $this->db->insertID();
        if (!$message_id) {
            return 0;
        }

        $this->db->query(
            "UPDATE $c SET last_message=$escaped_preview, last_message_at=$escaped_now,
                last_message_user_id=$actor_user_id, updated_at=$escaped_now
             WHERE id=$conversation_id"
        );

        if ($actor_user_id) {
            $this->mark_read($conversation_id, $actor_user_id, $message_id);
        }

        return $message_id;
    }

    function add_member($conversation_id, $user_id, $is_admin = 0) {
        $m = $this->members_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);
        $is_admin = $is_admin ? 1 : 0;
        $now = get_current_utc_time();

        $existing = $this->db->query(
            "SELECT id, deleted FROM $m WHERE conversation_id=$conversation_id AND user_id=$user_id LIMIT 1"
        )->getRow();

        if ($existing) {
            $this->db->query(
                "UPDATE $m SET deleted=0, is_admin=$is_admin, joined_at='$now' WHERE id=" . intval($existing->id)
            );
            return $existing->id;
        }

        $this->db->query(
            "INSERT INTO $m (conversation_id, user_id, is_admin, last_read_message_id, joined_at, deleted)
             VALUES ($conversation_id, $user_id, $is_admin, 0, '$now', 0)"
        );

        return $this->db->insertID();
    }

    function get_messages($conversation_id, $after_id = 0, $before_id = 0, $limit = 40) {
        $msg = $this->messages_table();
        $u = $this->users_table();
        $conversation_id = intval($conversation_id);
        $after_id = intval($after_id);
        $before_id = intval($before_id);
        $limit = intval($limit);

        $where = " AND $msg.conversation_id=$conversation_id AND $msg.deleted=0 ";
        if ($after_id) {
            $where .= " AND $msg.id>$after_id ";
        }
        if ($before_id) {
            $where .= " AND $msg.id<$before_id ";
        }

        $sql = "SELECT * FROM (
            SELECT $msg.*,
                CONCAT($u.first_name, ' ', $u.last_name) AS user_name,
                $u.image AS user_image,
                reply.message AS reply_message,
                reply.files AS reply_files,
                reply.from_user_id AS reply_from_user_id,
                CONCAT(ru.first_name, ' ', ru.last_name) AS reply_user_name
            FROM $msg
            LEFT JOIN $u ON $u.id=$msg.from_user_id
            LEFT JOIN $msg reply ON reply.id=$msg.reply_to_message_id AND reply.deleted=0
            LEFT JOIN $u ru ON ru.id=reply.from_user_id
            WHERE 1=1 $where
            ORDER BY $msg.id DESC
            LIMIT $limit
        ) t ORDER BY id ASC";

        return $this->db->query($sql)->getResult();
    }

    /**
     * Window of messages around a target id (for jump-to-pin in context).
     */
    function get_messages_around($conversation_id, $message_id, $before = 35, $after = 35) {
        $msg = $this->messages_table();
        $u = $this->users_table();
        $conversation_id = intval($conversation_id);
        $message_id = intval($message_id);
        $before = max(5, intval($before));
        $after = max(5, intval($after));

        $exists = $this->db->query(
            "SELECT id FROM $msg
             WHERE id=$message_id AND conversation_id=$conversation_id AND deleted=0
             LIMIT 1"
        )->getRow();
        if (!$exists) {
            return array();
        }

        $select = "SELECT $msg.*,
                CONCAT($u.first_name, ' ', $u.last_name) AS user_name,
                $u.image AS user_image,
                reply.message AS reply_message,
                reply.files AS reply_files,
                reply.from_user_id AS reply_from_user_id,
                CONCAT(ru.first_name, ' ', ru.last_name) AS reply_user_name
            FROM $msg
            LEFT JOIN $u ON $u.id=$msg.from_user_id
            LEFT JOIN $msg reply ON reply.id=$msg.reply_to_message_id AND reply.deleted=0
            LEFT JOIN $u ru ON ru.id=reply.from_user_id
            WHERE $msg.conversation_id=$conversation_id AND $msg.deleted=0";

        $older = $this->db->query(
            "$select AND $msg.id <= $message_id ORDER BY $msg.id DESC LIMIT " . ($before + 1)
        )->getResult();
        $newer = $this->db->query(
            "$select AND $msg.id > $message_id ORDER BY $msg.id ASC LIMIT $after"
        )->getResult();

        $older = array_reverse($older);
        return array_merge($older, $newer);
    }

    function get_message_enriched($message_id) {
        $msg = $this->messages_table();
        $u = $this->users_table();
        $message_id = intval($message_id);

        $sql = "SELECT $msg.*,
                CONCAT($u.first_name, ' ', $u.last_name) AS user_name,
                $u.image AS user_image,
                reply.message AS reply_message,
                reply.files AS reply_files,
                reply.from_user_id AS reply_from_user_id,
                CONCAT(ru.first_name, ' ', ru.last_name) AS reply_user_name
            FROM $msg
            LEFT JOIN $u ON $u.id=$msg.from_user_id
            LEFT JOIN $msg reply ON reply.id=$msg.reply_to_message_id AND reply.deleted=0
            LEFT JOIN $u ru ON ru.id=reply.from_user_id
            WHERE $msg.id=$message_id AND $msg.deleted=0
            LIMIT 1";

        return $this->db->query($sql)->getRow();
    }

    function find_messages($conversation_id, $options = array()) {
        $msg = $this->messages_table();
        $u = $this->users_table();
        $conversation_id = intval($conversation_id);
        $limit = intval(get_array_value($options, 'limit') ?: 80);
        $q = trim((string) get_array_value($options, 'q'));
        $files_only = !empty($options['files']);
        $pinned_only = !empty($options['pinned']);

        $where = " AND $msg.conversation_id=$conversation_id AND $msg.deleted=0 ";
        if ($files_only) {
            $where .= " AND $msg.files IS NOT NULL AND $msg.files!='' AND $msg.files!='a:0:{}' ";
        }
        if ($pinned_only) {
            $where .= " AND $msg.pinned_at IS NOT NULL ";
        }
        if ($q !== '') {
            $escaped = $this->db->escape('%' . $q . '%');
            $where .= " AND $msg.message LIKE $escaped ";
        }

        $order = $pinned_only
            ? "ORDER BY $msg.pinned_at DESC, $msg.id DESC"
            : "ORDER BY $msg.id DESC";

        $sql = "SELECT * FROM (
            SELECT $msg.*,
                CONCAT($u.first_name, ' ', $u.last_name) AS user_name,
                $u.image AS user_image,
                reply.message AS reply_message,
                reply.files AS reply_files,
                reply.from_user_id AS reply_from_user_id,
                CONCAT(ru.first_name, ' ', ru.last_name) AS reply_user_name
            FROM $msg
            LEFT JOIN $u ON $u.id=$msg.from_user_id
            LEFT JOIN $msg reply ON reply.id=$msg.reply_to_message_id AND reply.deleted=0
            LEFT JOIN $u ru ON ru.id=reply.from_user_id
            WHERE 1=1 $where
            $order
            LIMIT $limit
        ) t ORDER BY " . ($pinned_only ? "pinned_at DESC, id DESC" : "id ASC");

        return $this->db->query($sql)->getResult();
    }

    function get_pinned_messages($conversation_id, $limit = 20) {
        return $this->find_messages($conversation_id, array('pinned' => true, 'limit' => $limit));
    }

    function toggle_pin($conversation_id, $message_id, $user_id) {
        $msg = $this->messages_table();
        $conversation_id = intval($conversation_id);
        $message_id = intval($message_id);
        $user_id = intval($user_id);

        $row = $this->db->query(
            "SELECT id, pinned_at FROM $msg
             WHERE id=$message_id AND conversation_id=$conversation_id AND deleted=0
             LIMIT 1"
        )->getRow();
        if (!$row) {
            return null;
        }

        if (!empty($row->pinned_at)) {
            $this->db->query(
                "UPDATE $msg SET pinned_at=NULL, pinned_by=NULL WHERE id=$message_id"
            );
            return array('pinned' => false, 'message_id' => $message_id);
        }

        $now = $this->db->escape(get_current_utc_time());
        $this->db->query(
            "UPDATE $msg SET pinned_at=$now, pinned_by=$user_id WHERE id=$message_id"
        );
        return array('pinned' => true, 'message_id' => $message_id);
    }

    function can_edit_message($message, $user_id, $window_seconds = 3600) {
        if (!$message || empty($message->id)) {
            return false;
        }
        if (!empty($message->deleted)) {
            return false;
        }
        if ((int) $message->from_user_id !== (int) $user_id) {
            return false;
        }
        $created = strtotime((string) $message->created_at);
        if (!$created) {
            return false;
        }
        $now = strtotime(get_current_utc_time());
        return ($now - $created) <= intval($window_seconds);
    }

    function get_message($message_id) {
        $msg = $this->messages_table();
        $message_id = intval($message_id);
        return $this->db->query(
            "SELECT * FROM $msg WHERE id=$message_id AND deleted=0 LIMIT 1"
        )->getRow();
    }

    function edit_message($message_id, $user_id, $new_text) {
        $msg = $this->messages_table();
        $c = $this->conversations_table();
        $message_id = intval($message_id);
        $user_id = intval($user_id);
        $new_text = trim((string) $new_text);

        $row = $this->get_message($message_id);
        if (!$row || !$this->can_edit_message($row, $user_id)) {
            return null;
        }

        $has_files = false;
        if (!empty($row->files)) {
            $file_list = @unserialize($row->files);
            $has_files = is_array($file_list) && count($file_list) > 0;
        }
        if ($new_text === '' && !$has_files) {
            return false;
        }

        $now = get_current_utc_time();
        $escaped_message = $this->db->escape($new_text);
        $escaped_now = $this->db->escape($now);

        $this->db->query(
            "UPDATE $msg SET message=$escaped_message, edited_at=$escaped_now
             WHERE id=$message_id AND from_user_id=$user_id AND deleted=0"
        );

        $preview = trim(strip_tags($new_text));
        if ($preview === '' && $has_files) {
            $file_list = @unserialize($row->files);
            $first = is_array($file_list) ? get_array_value($file_list, 0) : null;
            $name = is_array($first) ? (string) get_array_value($first, "file_name") : '';
            if ($name && strpos($name, 'recording') !== false) {
                $preview = '🎤 Голосовое сообщение';
            } else {
                $preview = '📎 Файл';
            }
        }
        if (mb_strlen($preview) > 200) {
            $preview = mb_substr($preview, 0, 200);
        }
        $escaped_preview = $this->db->escape($preview);

        // Refresh inbox preview if this is still the latest message in the conversation.
        $latest = $this->db->query(
            "SELECT id FROM $msg
             WHERE conversation_id=" . intval($row->conversation_id) . " AND deleted=0
             ORDER BY id DESC LIMIT 1"
        )->getRow();
        if ($latest && (int) $latest->id === $message_id) {
            $this->db->query(
                "UPDATE $c SET last_message=$escaped_preview, updated_at=$escaped_now
                 WHERE id=" . intval($row->conversation_id)
            );
        }

        return $this->get_message($message_id);
    }

    function send_message($conversation_id, $from_user_id, $message, $files = '', $reply_to_message_id = 0) {
        $msg = $this->messages_table();
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        $from_user_id = intval($from_user_id);
        $reply_to_message_id = intval($reply_to_message_id);
        $now = get_current_utc_time();

        if ($reply_to_message_id) {
            $parent = $this->db->query(
                "SELECT id FROM $msg
                 WHERE id=$reply_to_message_id AND conversation_id=$conversation_id AND deleted=0
                 LIMIT 1"
            )->getRow();
            if (!$parent) {
                $reply_to_message_id = 0;
            }
        }

        $message = $message ?? '';
        $escaped_message = $this->db->escape($message);
        $escaped_files = $this->db->escape($files);
        $escaped_now = $this->db->escape($now);
        $reply_sql = $reply_to_message_id ? $reply_to_message_id : 'NULL';

        $this->db->query(
            "INSERT INTO $msg (conversation_id, from_user_id, is_system, message, files, created_at, deleted, reply_to_message_id)
             VALUES ($conversation_id, $from_user_id, 0, $escaped_message, $escaped_files, $escaped_now, 0, $reply_sql)"
        );

        $message_id = $this->db->insertID();

        $preview = trim(strip_tags((string) $message));
        if ($preview === '' && $files) {
            $file_list = @unserialize($files);
            if (is_array($file_list) && $file_list) {
                $first = get_array_value($file_list, 0);
                $name = is_array($first) ? (string) get_array_value($first, "file_name") : '';
                if ($name && strpos($name, 'recording') !== false) {
                    $preview = '🎤 Голосовое сообщение';
                } else {
                    $preview = '📎 ' . (count($file_list) > 1
                        ? sprintf(app_lang('download_files'), count($file_list))
                        : 'Файл');
                }
            }
        }
        if (mb_strlen($preview) > 200) {
            $preview = mb_substr($preview, 0, 200);
        }
        $escaped_preview = $this->db->escape($preview);

        $this->db->query(
            "UPDATE $c SET last_message=$escaped_preview, last_message_at=$escaped_now,
                last_message_user_id=$from_user_id, updated_at=$escaped_now
             WHERE id=$conversation_id"
        );

        $this->mark_read($conversation_id, $from_user_id, $message_id);

        return $message_id;
    }

    function mark_read($conversation_id, $user_id, $last_message_id = 0) {
        $m = $this->members_table();
        $msg = $this->messages_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);
        $last_message_id = intval($last_message_id);

        if (!$last_message_id) {
            $row = $this->db->query(
                "SELECT MAX(id) AS max_id FROM $msg WHERE conversation_id=$conversation_id AND deleted=0"
            )->getRow();
            $last_message_id = $row && $row->max_id ? (int) $row->max_id : 0;
        }

        $this->db->query(
            "UPDATE $m SET last_read_message_id=GREATEST(last_read_message_id, $last_message_id)
             WHERE conversation_id=$conversation_id AND user_id=$user_id AND deleted=0"
        );
    }

    function count_unread($user_id) {
        $c = $this->conversations_table();
        $m = $this->members_table();
        $msg = $this->messages_table();
        $user_id = intval($user_id);

        $sql = "SELECT COUNT(*) AS total FROM $msg cm
            INNER JOIN $m my ON my.conversation_id=cm.conversation_id AND my.user_id=$user_id AND my.deleted=0
            INNER JOIN $c ON $c.id=cm.conversation_id AND $c.deleted=0
            WHERE cm.deleted=0 AND cm.is_system=0 AND cm.from_user_id!=$user_id AND cm.id > IFNULL(my.last_read_message_id, 0)";

        $row = $this->db->query($sql)->getRow();
        return $row ? (int) $row->total : 0;
    }

    function get_members($conversation_id) {
        $m = $this->members_table();
        $u = $this->users_table();
        $conversation_id = intval($conversation_id);

        $sql = "SELECT $u.id, $u.first_name, $u.last_name, $u.image, $u.job_title, $u.last_online, $m.is_admin
            FROM $m
            INNER JOIN $u ON $u.id=$m.user_id
            WHERE $m.conversation_id=$conversation_id AND $m.deleted=0
            ORDER BY $m.is_admin DESC, $u.first_name";

        return $this->db->query($sql)->getResult();
    }

    function is_group_admin($conversation_id, $user_id) {
        $m = $this->members_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);
        $row = $this->db->query(
            "SELECT is_admin FROM $m
             WHERE conversation_id=$conversation_id AND user_id=$user_id AND deleted=0
             LIMIT 1"
        )->getRow();
        return $row && (int) $row->is_admin === 1;
    }

    function remove_member($conversation_id, $member_user_id, $by_user_id) {
        $m = $this->members_table();
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        $member_user_id = intval($member_user_id);
        $by_user_id = intval($by_user_id);

        if (!$conversation_id || !$member_user_id || !$by_user_id) {
            return array('ok' => false, 'message' => app_lang('error_occurred'));
        }
        if ($member_user_id === $by_user_id) {
            return array('ok' => false, 'message' => 'Чтобы выйти, используйте «Покинуть чат»');
        }

        $conv = $this->db->query(
            "SELECT id, type FROM $c WHERE id=$conversation_id AND deleted=0 LIMIT 1"
        )->getRow();
        if (!$conv || $conv->type !== 'group') {
            return array('ok' => false, 'message' => app_lang('error_occurred'));
        }
        if (!$this->is_group_admin($conversation_id, $by_user_id)) {
            return array('ok' => false, 'message' => 'Только администратор может исключать участников');
        }

        $target = $this->db->query(
            "SELECT id, is_admin FROM $m
             WHERE conversation_id=$conversation_id AND user_id=$member_user_id AND deleted=0
             LIMIT 1"
        )->getRow();
        if (!$target) {
            return array('ok' => false, 'message' => 'Участник не найден');
        }

        if ((int) $target->is_admin === 1) {
            $admins = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM $m
                 WHERE conversation_id=$conversation_id AND deleted=0 AND is_admin=1"
            )->getRow();
            if ($admins && (int) $admins->cnt <= 1) {
                return array('ok' => false, 'message' => 'Нельзя исключить последнего администратора');
            }
        }

        $this->db->query(
            "UPDATE $m SET deleted=1 WHERE id=" . intval($target->id)
        );

        $actor_name = $this->get_user_display_name($by_user_id);
        $target_name = $this->get_user_display_name($member_user_id);
        $text = ($actor_name && $target_name)
            ? ($actor_name . ' исключил(а) ' . $target_name . ' из чата')
            : ($target_name ? ($target_name . ' исключён(а) из чата') : 'Участник исключён из чата');
        $message_id = $this->send_system_message($conversation_id, $by_user_id, $text);

        return array(
            'ok' => true,
            'message_id' => $message_id,
            'system_text' => $text,
            'target_name' => $target_name,
        );
    }

    /**
     * Add members to an existing group. Returns names that were newly connected.
     */
    function add_members($conversation_id, array $user_ids, $by_user_id) {
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        $by_user_id = intval($by_user_id);
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids))));

        if (!$conversation_id || !$by_user_id || !$user_ids) {
            return array('ok' => false, 'message' => app_lang('error_occurred'));
        }

        $conv = $this->db->query(
            "SELECT id, type FROM $c WHERE id=$conversation_id AND deleted=0 LIMIT 1"
        )->getRow();
        if (!$conv || $conv->type !== 'group') {
            return array('ok' => false, 'message' => app_lang('error_occurred'));
        }
        if (!$this->is_group_admin($conversation_id, $by_user_id)) {
            return array('ok' => false, 'message' => 'Только администратор может добавлять участников');
        }

        $added_names = array();
        foreach ($user_ids as $uid) {
            if ($uid <= 0 || !$this->is_staff_user($uid)) {
                continue;
            }
            if ($this->user_is_member($conversation_id, $uid)) {
                continue;
            }
            $this->add_member($conversation_id, $uid, 0);
            $n = $this->get_user_display_name($uid);
            if ($n !== '') {
                $added_names[] = $n;
            }
        }

        if (!$added_names) {
            return array('ok' => false, 'message' => 'Некого добавлять — все уже в чате');
        }

        $actor_name = $this->get_user_display_name($by_user_id);
        $text = $actor_name
            ? ($actor_name . ' добавил(а) в чат: ' . implode(', ', $added_names))
            : ('В чат добавлен(ы): ' . implode(', ', $added_names));
        $message_id = $this->send_system_message($conversation_id, $by_user_id, $text);

        return array(
            'ok' => true,
            'message_id' => $message_id,
            'system_text' => $text,
            'added_names' => $added_names,
            'added_count' => count($added_names),
        );
    }

    function set_group_avatar($conversation_id, $user_id, $avatar_value) {
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        $user_id = intval($user_id);
        if (!$conversation_id || !$user_id) {
            return false;
        }

        $conv = $this->db->query(
            "SELECT id, type, avatar FROM $c WHERE id=$conversation_id AND deleted=0 LIMIT 1"
        )->getRow();
        if (!$conv || $conv->type !== 'group') {
            return false;
        }
        if (!$this->is_group_admin($conversation_id, $user_id)) {
            return false;
        }

        $avatar_value = trim((string) $avatar_value);
        $escaped = $avatar_value === '' ? 'NULL' : $this->db->escape($avatar_value);
        $now = $this->db->escape(get_current_utc_time());
        $this->db->query(
            "UPDATE $c SET avatar=$escaped, updated_at=$now WHERE id=$conversation_id"
        );

        return true;
    }

    function get_message_for_user($message_id, $user_id) {
        $msg = $this->messages_table();
        $m = $this->members_table();
        $message_id = intval($message_id);
        $user_id = intval($user_id);

        return $this->db->query(
            "SELECT cm.*
             FROM $msg cm
             INNER JOIN $m ON $m.conversation_id=cm.conversation_id AND $m.user_id=$user_id AND $m.deleted=0
             WHERE cm.id=$message_id AND cm.deleted=0
             LIMIT 1"
        )->getRow();
    }

    function is_staff_user($user_id) {
        $u = $this->users_table();
        $user_id = intval($user_id);
        $row = $this->db->query(
            "SELECT id FROM $u WHERE id=$user_id AND deleted=0 AND status='active' AND user_type='staff' LIMIT 1"
        )->getRow();
        return $row ? true : false;
    }

    /**
     * Soft-delete own (non-system) messages older than $days.
     * Returns number of deleted rows.
     */
    function auto_cleanup_own_messages($user_id, $days) {
        $msg = $this->messages_table();
        $user_id = intval($user_id);
        $days = intval($days);
        if (!$user_id || $days < 1) {
            return 0;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime(get_current_utc_time() . " -{$days} days"));
        $escaped_cutoff = $this->db->escape($cutoff);

        $affected = $this->db->query(
            "SELECT DISTINCT conversation_id
             FROM $msg
             WHERE from_user_id=$user_id
               AND is_system=0
               AND deleted=0
               AND created_at < $escaped_cutoff"
        )->getResult();

        if (!$affected) {
            return 0;
        }

        $this->db->query(
            "UPDATE $msg
             SET deleted=1, pinned_at=NULL, pinned_by=NULL
             WHERE from_user_id=$user_id
               AND is_system=0
               AND deleted=0
               AND created_at < $escaped_cutoff"
        );
        $deleted = (int) $this->db->affectedRows();

        foreach ($affected as $row) {
            $this->refresh_conversation_last_message((int) $row->conversation_id);
        }

        return $deleted;
    }

    function refresh_conversation_last_message($conversation_id) {
        $msg = $this->messages_table();
        $c = $this->conversations_table();
        $conversation_id = intval($conversation_id);
        if (!$conversation_id) {
            return;
        }

        $latest = $this->db->query(
            "SELECT id, from_user_id, message, files, created_at, is_system
             FROM $msg
             WHERE conversation_id=$conversation_id AND deleted=0
             ORDER BY id DESC
             LIMIT 1"
        )->getRow();

        $now = $this->db->escape(get_current_utc_time());
        if (!$latest) {
            $this->db->query(
                "UPDATE $c
                 SET last_message=NULL, last_message_at=NULL, last_message_user_id=NULL, updated_at=$now
                 WHERE id=$conversation_id"
            );
            return;
        }

        $preview = trim(strip_tags((string) $latest->message));
        if ($preview === '' && !empty($latest->files)) {
            $file_list = @unserialize($latest->files);
            if (is_array($file_list) && $file_list) {
                $first = get_array_value($file_list, 0);
                $name = is_array($first) ? (string) get_array_value($first, "file_name") : '';
                if ($name && strpos($name, 'recording') !== false) {
                    $preview = '🎤 Голосовое сообщение';
                } else {
                    $preview = '📎 ' . (count($file_list) > 1
                        ? sprintf(app_lang('download_files'), count($file_list))
                        : 'Файл');
                }
            }
        }
        if ($preview === '' && !empty($latest->is_system)) {
            $preview = 'Системное сообщение';
        }
        if (mb_strlen($preview) > 200) {
            $preview = mb_substr($preview, 0, 200);
        }

        $escaped_preview = $this->db->escape($preview);
        $escaped_at = $this->db->escape($latest->created_at);
        $from_id = intval($latest->from_user_id);

        $this->db->query(
            "UPDATE $c
             SET last_message=$escaped_preview,
                 last_message_at=$escaped_at,
                 last_message_user_id=$from_id,
                 updated_at=$now
             WHERE id=$conversation_id"
        );
    }
}
