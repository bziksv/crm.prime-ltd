<?php


namespace App\Models;


class Task_personal_notes_models extends Crud_model
{
    protected $table = null;

    function __construct() {
        $this->table = 'task_personal_notes';
        parent::__construct($this->table);
    }

    function update_or_create($data = [], $where = []) {
        $note_id = 0;

        $personal_note = $this->get_one_where($where);

        if ($personal_note->id) {
            $note_id = $personal_note->id;
        }

        $this->ci_save($data, $note_id);
    }
}
