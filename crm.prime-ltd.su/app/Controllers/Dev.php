<?php


namespace App\Controllers;


use App\Libraries\Imap;

class Dev extends App_Controller
{
    public function index() {
       // $this->send_mail_by_ticket_comment();
        $this->imap();
    }

    function send_mail_by_ticket_comment(): void {
        $notify = new Notification_processor();

        $notify->create_notification([
            "event" => encode_id("ticket_commented", "notification"),
            "user_id" => 1,
            "ticket_id" => 1227,
            "ticket_comment_id" => 8650,
        ]);
    }

    function insert_to_db() {
        $data = [
            "ticket_comment_id" => 1,
            "from_user_id" => 1,
            "to_user_id" => 1,
            "created_at" => get_current_utc_time()
        ];

        $data = clean_data($data);

        $this->Ticket_mails_model->ci_save($data, 2);
    }

    function imap() {
        $imap = new Imap();
        $imap->run_imap();
    }
}
