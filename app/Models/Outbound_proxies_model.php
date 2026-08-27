<?php

namespace App\Models;

class Outbound_proxies_model extends Crud_model {

    function __construct() {
        $this->table = 'outbound_proxies';
        parent::__construct($this->table);
        $this->ensure_table();
    }

    function ensure_table() {
        $table = $this->table;
        if ($this->db->tableExists($table)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `label` varchar(255) NOT NULL DEFAULT 'Proxy',
            `supplier` varchar(255) DEFAULT NULL,
            `url` varchar(512) NOT NULL,
            `priority` int(11) NOT NULL DEFAULT 50,
            `enabled` tinyint(1) NOT NULL DEFAULT 1,
            `deleted` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `enabled_priority` (`deleted`, `enabled`, `priority`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->db->query($sql);
    }

    function get_all_active() {
        $table = $this->db->prefixTable('outbound_proxies');
        $sql = "SELECT * FROM $table WHERE deleted=0 ORDER BY priority DESC, id ASC";
        return $this->db->query($sql)->getResult();
    }

    function get_enabled() {
        $rows = $this->get_all_active();
        $enabled = [];
        foreach ($rows as $row) {
            if ((int) $row->enabled === 1) {
                $enabled[] = $row;
            }
        }
        return $enabled;
    }
}
