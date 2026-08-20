<?php

namespace Telegram_Notification\Controllers;

use App\Controllers\Security_Controller;

class Telegram_Notification_settings extends Security_Controller {

    protected $Telegram_Integration_settings_model;
    protected $Telegram_Notification_settings_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->Telegram_Integration_settings_model = new \Telegram_Notification\Models\Telegram_Integration_settings_model();
        $this->Telegram_Notification_settings_model = new \Telegram_Notification\Models\Telegram_Notification_settings_model();
    }

    function index() {
        return $this->template->rander("Telegram_Notification\Views\settings\index");
    }

    function save_telegram_integration_settings() {
        $settings = array("enable_telegram", "bot_token", "chat_id");

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = "";
            }

            $this->Telegram_Integration_settings_model->save_setting($setting, $value);
        }

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }

    function test_telegram_notification() {
        helper('notifications');
        if (send_telegram_notification("test_telegram_notification", $this->login_user->id, 0)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('telegram_notification_error_message')));
        }
    }

    //load telegram notification settings tab
    function notification_settings() {
        $category_suggestions = array(
            array("id" => "", "text" => "- " . app_lang('category') . " -"),
            array("id" => "announcement", "text" => app_lang("announcement")),
            array("id" => "client", "text" => app_lang("client")),
            array("id" => "contract", "text" => app_lang("contract")),
            array("id" => "event", "text" => app_lang("event")),
            array("id" => "estimate", "text" => app_lang("estimate")),
            array("id" => "invoice", "text" => app_lang("invoice")),
            array("id" => "leave", "text" => app_lang("leave")),
            array("id" => "lead", "text" => app_lang("lead")),
            array("id" => "message", "text" => app_lang("message")),
            array("id" => "order", "text" => app_lang("order")),
            array("id" => "project", "text" => app_lang("project")),
            array("id" => "proposal", "text" => app_lang("proposal")),
            array("id" => "ticket", "text" => app_lang("ticket")),
            array("id" => "timeline", "text" => app_lang("timeline"))
        );

        //get data from hook to show in filter
        try {
            $category_suggestions = app_hooks()->apply_filters('app_filter_notification_category_suggestion', $category_suggestions);
        } catch (\Exception $ex) {
            log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
        }

        $view_data['categories_dropdown'] = json_encode($category_suggestions);
        return $this->template->view("Telegram_Notification\Views\\notifications\index", $view_data);
    }

    /* list of telegram notification, prepared for datatable  */

    function notification_settings_list_data() {
        $options = array("category" => $this->request->getPost("category"));
        $list_data = $this->Telegram_Notification_settings_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_telegram_notification_settings_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    /* list of telegram notification  */

    private function _notification_list_data($id) {
        $options = array("id" => $id);
        $data = $this->Telegram_Notification_settings_model->get_details($options)->getRow();
        return $this->_make_telegram_notification_settings_row($data);
    }

    /* prepare a row of telegram notification list table */

    private function _make_telegram_notification_settings_row($data) {

        $yes = "<i data-feather='check-circle' class='icon-16'></i>";
        $no = "<i data-feather='check-circle' class='icon-16' style='opacity:0.2'></i>";

        return array(
            $data->sort,
            app_lang($data->event),
            app_lang($data->category),
            $data->enable_telegram ? $yes : $no,
            modal_anchor(get_uri("telegram_notification_settings/notification_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('notification'), "data-post-id" => $data->id))
        );
    }

    /* load telegram notification modal */

    function notification_modal_form() {
        $id = $this->request->getPost("id");

        if ($id) {
            $view_data["model_info"] = $this->Telegram_Notification_settings_model->get_details(array("id" => $id))->getRow();
        }
        return $this->template->view('Telegram_Notification\Views\notifications\modal_form', $view_data);
    }

    /* save telegram notification */

    function save_notification_settings() {
        $id = $this->request->getPost("id");
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $event = $this->request->getPost("event");
        $data = array(
            "event" => $event,
            "enable_telegram" => $this->request->getPost("enable_telegram")
        );

        $telegram_notification_setting = $this->Telegram_Notification_settings_model->get_one_where(array("event" => $event));
        $save_id = $this->Telegram_Notification_settings_model->ci_save($data, $telegram_notification_setting->id);

        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_notification_list_data($id), 'id' => $id, 'message' => app_lang('settings_updated')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

}
