<?php

namespace Password_manager\Models;

class Password_manager_categories_model extends \App\Models\Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'password_manager_categories';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $Password_manager_categories_table = $this->db->prefixTable('password_manager_categories');

        $where = "";
        $id = get_array_value($options, "id");
        if ($id) {
            $where = " AND $Password_manager_categories_table.id=$id";
        }

        $sql = "SELECT $Password_manager_categories_table.*
        FROM $Password_manager_categories_table
        WHERE $Password_manager_categories_table.deleted=0 $where";

        return $this->db->query($sql);
    }

}
