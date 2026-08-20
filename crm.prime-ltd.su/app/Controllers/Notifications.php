<?php

namespace App\Controllers;

use App\Libraries\NotificationGrouper;

class Notifications extends Security_Controller {

    public $notifications_filters;

    function __construct() {
        parent::__construct();

        helper('notifications');

        $this->notifications_filters = "user_" . $this->login_user->id . "_notifications_filters";
    }

    //load notifications view
    function index() {
        $view_data = $this->_prepare_notification_list();
        $view_data["notifications_filters"] = [];
        $view_data["event_dropdown"] = $this->event_dropdown();
        $view_data["is_read_dropdown"] = $this->is_read_dropdown();
        $view_data["grouped_dropdown"] = $this->grouped_dropdown();
        $view_data["order_by_dropdown"] = $this->order_by_dropdown();
        $view_data["projects_dropdown"] = $this->projects_dropdown();
        $view_data["team_members_dropdown"] = $this->team_members_dropdown();

        if ($notifications_filters = $this->Settings_model->get_setting($this->notifications_filters)) {
            $view_data["notifications_filters"] = unserialize($notifications_filters);
        }

        return $this->template->rander("notifications/index", $view_data);
    }

    function load_more($offset = 0) {
        validate_numeric_value($offset);
        $view_data = $this->_prepare_notification_list($offset);
        return $this->template->view("notifications/list_data", $view_data);
    }

    function count_notifications() {
        $notifiations = $this->Notifications_model->count_notifications($this->login_user->id, $this->login_user->notification_checked_at);
        echo json_encode(array("success" => true, 'total_notifications' => $notifiations));
    }

    function get_notifications() {
        $view_data = $this->_prepare_notification_list();
        $view_data["result_remaining"] = false; //don't show load more option in notification popop
        echo json_encode(array("success" => true, 'notification_list' => $this->template->view("notifications/list", $view_data, true)));
    }

    function update_notification_checking_status() {
        $now = get_current_utc_time();
        $data = array("notification_checked_at" => $now);
        $this->Users_model->ci_save($data, $this->login_user->id);
    }

    function set_notification_status_as_read($notification_id = 0) {
        if ($notification_id) {
            validate_numeric_value($notification_id);
            $this->Notifications_model->set_notification_status_as_read($notification_id, $this->login_user->id);
        } else {
            //mark all notification as read
            $this->Notifications_model->set_notification_status_as_read(0, $this->login_user->id);
            echo json_encode(array("success" => true, 'message' => app_lang('marked_all_notifications_as_read')));
        }
    }

    function set_notification_status_as_unread($notification_id = 0) {
        if ($notification_id) {
            validate_numeric_value($notification_id);
            $this->Notifications_model->set_notification_status_as_unread($notification_id, $this->login_user->id);
        }
    }

    function save_filter_modal_form() {
        $data_view["params"] = $this->request->getGet();

        return $this->template->view("notifications/save_filter_modal_form", $data_view);
    }

    function store_user_filter() {
        $title = $this->request->getPost("title");

        $new_filter = [
            "title" => $title,
            "params" => $this->request->getGet(),
        ];

        $filters = [];

        if ($settings = $this->Settings_model->get_setting($this->notifications_filters)) {
            $filters = unserialize($settings);
        }

        $filters[sha1($title)] = $new_filter;

        $this->Settings_model->save_setting($this->notifications_filters, serialize($filters), "user");

        echo json_encode(array("success" => true, 'message' => app_lang('success')));
    }

    function delete_user_filter() {
        $index = $this->request->getGet("index");

        if ($settings = $this->Settings_model->get_setting($this->notifications_filters)) {
            $filters = unserialize($settings);

            if (isset($filters[$index])) {
                unset($filters[$index]);
                $this->Settings_model->save_setting($this->notifications_filters, serialize($filters), "user");
            }
        }

        return redirect('notifications');
    }

    private function _prepare_notification_list($offset = 0) {
        $options = [
            "event" => $this->request->getGet('notification_event_filter'),
            "is_read" => $this->request->getGet('notification_is_read_filter'),
            "grouped" => $this->request->getGet('notification_grouped_filter'),
            "team_member" => $this->request->getGet('notification_team_members_filter'),
            "project_id" => $this->request->getGet('notification_projects_filter'),
            "order_by" => $this->request->getGet('notification_order_by_filter'),
        ];

        $notifiations = $this->Notifications_model->get_notifications($this->login_user->id, $offset, 100, $options);

        if (empty($options['grouped'])) {
            $notifiations->result = (new NotificationGrouper($notifiations->result))->get_grouped_unread_by_task();
        }

        $view_data['notifications'] = $notifiations->result;
        $view_data['found_rows'] = $notifiations->found_rows;
        $next_page_offset = $offset + 100;
        $view_data['next_page_offset'] = $next_page_offset;
        $view_data['result_remaining'] = $notifiations->found_rows > $next_page_offset;
        return $view_data;
    }

    function team_members_dropdown() {
        $team_members_dropdown = [
            ["id" => "", "text" => "- " . app_lang("team_member") . " -"]
        ];
        $members_list = $this->Users_model->get_dropdown_list(array("first_name", "last_name"), "id", array("deleted" => 0, "user_type" => "staff"));

        foreach ($members_list as $id => $text) {
            $team_members_dropdown[] = ["id" => $id, "text" => $text];
        }

        return $team_members_dropdown;
    }

    function projects_dropdown() {
        $projects_dropdown = [
            ["id" => "", "text" => "- " . app_lang("projects") . " -"]
        ];
        $projects_list = $this->Projects_model->get_projects_id_and_name()->getResult();

        foreach ($projects_list as $project) {
            $projects_dropdown[] = ["id" => $project->id, "text" => $project->title];
        }

        return $projects_dropdown;
    }

    function event_dropdown() {
        $event_dropdown = [];
        $events = $this->Notifications_model->get_notification_settings_filter();

        foreach ($events as $event) {
            $event_dropdown[] = ['id' => $event->event, 'text' => app_lang("notification_" . $event->event)];
        }

        return $event_dropdown;
    }

    function is_read_dropdown() {
        $is_read_dropdown = [
            ["id" => "", "text" => "- " . app_lang("status") . " -"],
            ["id" => "0", "text" => "Непрочитанные"],
            ["id" => "1", "text" => "Прочитанные"],
        ];

        return $is_read_dropdown;
    }

    function grouped_dropdown(): array
    {
        return [
            ["id" => "", "text" => "- " . app_lang("grouped") . " -"],
            ["id" => "0", "text" => "Да"],
            ["id" => "1", "text" => "Нет"],
        ];
    }

    function order_by_dropdown(): array
    {
        return [
            ["id" => "", "text" => "- " . app_lang("order_by") . " -"],
            ["id" => "DESC", "text" => "Новые"],
            ["id" => "ASC", "text" => "Старые"],
        ];
    }

}

/* End of file notifications.php */
/* Location: ./app/controllers/Notifications.php */
