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



}
