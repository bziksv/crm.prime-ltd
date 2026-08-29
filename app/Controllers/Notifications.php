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
        $filters = $this->_get_notification_filters();
        $view_data = array();
        $view_data["notifications_filters"] = [];
        $view_data["event_dropdown"] = $this->event_dropdown();
        $view_data["is_read_dropdown"] = $this->is_read_dropdown();
        $view_data["grouped_dropdown"] = $this->grouped_dropdown();
        $view_data["order_by_dropdown"] = $this->order_by_dropdown();
        $view_data["projects_dropdown"] = $this->projects_dropdown();
        $view_data["team_members_dropdown"] = $this->team_members_dropdown();
        $view_data["inbox_filters"] = $filters;
        $view_data["has_active_filters"] = !empty($filters);

        if ($notifications_filters = $this->Settings_model->get_setting($this->notifications_filters)) {
            $view_data["notifications_filters"] = unserialize($notifications_filters);
        }

        return $this->template->rander("notifications/index", $view_data);
    }

    function inbox_list_data() {
        $skip = (int) $this->request->getPost("skip");
        $limit = (int) $this->request->getPost("limit");
        if ($limit <= 0 || $limit > 50) {
            $limit = 40;
        }

        $filters = $this->_get_notification_filters();

        // Tabs own read-status; advanced GET is_read is ignored while tabs are used
        $tab = $this->request->getPost("tab");
        if ($tab === "unread") {
            $filters["notification_is_read_filter"] = "0";
        } else {
            unset($filters["notification_is_read_filter"]);
        }

        $options = array(
            "event" => get_array_value($filters, "notification_event_filter"),
            "is_read" => get_array_value($filters, "notification_is_read_filter"),
            "grouped" => get_array_value($filters, "notification_grouped_filter"),
            "team_member" => get_array_value($filters, "notification_team_members_filter"),
            "project_id" => get_array_value($filters, "notification_projects_filter"),
            "order_by" => get_array_value($filters, "notification_order_by_filter"),
            "search_by" => trim((string) $this->request->getPost("search_by")),
        );

        $notifications = $this->Notifications_model->get_notifications($this->login_user->id, $skip, $limit, $options);

        if ($this->should_group_notifications(get_array_value($options, "grouped"))) {
            $grouped = (new NotificationGrouper($notifications->result))->get_grouped_unread_by_task();
            $notifications->result = $this->_sort_notification_groups(
                array_values($grouped),
                get_array_value($options, "order_by")
            );
        }

        $items = array();
        foreach ($notifications->result as $notification) {
            $item = $this->_make_inbox_item($notification);
            if ($item) {
                $items[] = $item;
            }
        }

        $found_rows = (int) $notifications->found_rows;

        // Counts always for unread (independent of active tab), same filters otherwise
        $stats_options = $options;
        $stats = $this->Notifications_model->count_unread_inbox_stats($this->login_user->id, $stats_options);

        echo json_encode(array(
            "success" => true,
            "data" => $items,
            "recordsTotal" => $found_rows,
            "hasMore" => ($skip + count($items)) < $found_rows,
            "unread_total" => (int) get_array_value($stats, "unread_total"),
            "unread_unique" => (int) get_array_value($stats, "unread_unique"),
        ), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    function view_panel($notification_id = 0) {
        if (!$notification_id) {
            $notification_id = (int) $this->request->getPost("id");
        }
        validate_numeric_value($notification_id);

        $notification = $this->Notifications_model->get_email_notification($notification_id);
        if (!$notification) {
            echo "<div class='notification-panel-placeholder'>Уведомление не найдено</div>";
            return;
        }

        // Ensure this notification belongs to current user
        $notify_to = explode(",", (string) $notification->notify_to);
        if (!in_array((string) $this->login_user->id, $notify_to)) {
            echo "<div class='notification-panel-placeholder'>Нет доступа</div>";
            return;
        }

        $this->Notifications_model->set_notification_status_as_read($notification_id, $this->login_user->id);

        $url_attributes_array = get_notification_url_attributes($notification);
        $changes_array = array();
        if ($notification->activity_log_changes !== "") {
            if ($notification->event === "bitbucket_push_received" || $notification->event === "github_push_received") {
                $changes_array = get_change_logs_array($notification->activity_log_changes, $notification->activity_log_type, $notification->event, true);
            } else {
                $changes_array = get_change_logs_array($notification->activity_log_changes, $notification->activity_log_type, "all");
            }
        }

        $actor = $this->_notification_actor($notification);

        $view_data = array(
            "notification" => $notification,
            "changes_array" => $changes_array,
            "url" => get_array_value($url_attributes_array, "url"),
            "url_attributes" => get_array_value($url_attributes_array, "url_attributes"),
            "actor_name" => $actor["name"],
            "actor_avatar" => $actor["avatar"],
            "event_label" => sprintf(app_lang("notification_" . $notification->event), $notification->to_user_name),
        );

        return $this->template->view("notifications/panel_view", $view_data);
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

    function count_ticket_notifications() {
        $total = $this->Notifications_model->count_unread_by_events($this->login_user->id, array("ticket_created", "ticket_commented"));
        echo json_encode(array("success" => true, "total_notifications" => $total));
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
        $filters = $this->_get_notification_filters();
        $options = [
            "event" => get_array_value($filters, 'notification_event_filter'),
            "is_read" => get_array_value($filters, 'notification_is_read_filter'),
            "grouped" => get_array_value($filters, 'notification_grouped_filter'),
            "team_member" => get_array_value($filters, 'notification_team_members_filter'),
            "project_id" => get_array_value($filters, 'notification_projects_filter'),
            "order_by" => get_array_value($filters, 'notification_order_by_filter'),
        ];

        $notifiations = $this->Notifications_model->get_notifications($this->login_user->id, $offset, 100, $options);

        // Group only when explicitly requested. Default is off so each unread
        // comment is a separate row (empty("0") previously treated "Да" as on).
        if ($this->should_group_notifications($options['grouped'])) {
            $grouped = (new NotificationGrouper($notifiations->result))->get_grouped_unread_by_task();
            $notifiations->result = $this->_sort_notification_groups(
                array_values($grouped),
                get_array_value($options, 'order_by')
            );
        }

        $view_data['notifications'] = $notifiations->result;
        $view_data['found_rows'] = $notifiations->found_rows;
        $view_data['notification_filter_query'] = http_build_query($filters);
        $next_page_offset = $offset + 100;
        $view_data['next_page_offset'] = $next_page_offset;
        $view_data['result_remaining'] = $notifiations->found_rows > $next_page_offset;
        return $view_data;
    }

    /**
     * Keep filter params on "load more" requests (ajax POST with query string).
     */
    private function _get_notification_filters(): array {
        $names = [
            'notification_event_filter',
            'notification_is_read_filter',
            'notification_team_members_filter',
            'notification_projects_filter',
            'notification_grouped_filter',
            'notification_order_by_filter',
        ];

        $filters = [];
        foreach ($names as $name) {
            $value = $this->request->getGet($name);
            if ($value === null || $value === '') {
                $value = $this->request->getPost($name);
            }
            if ($value !== null && $value !== '') {
                $filters[$name] = $value;
            }
        }

        return $filters;
    }

    private function _sort_notification_groups(array $groups, $orderBy = 'DESC'): array
    {
        $asc = strtoupper((string) $orderBy) === 'ASC';

        usort($groups, function ($a, $b) use ($asc) {
            $aTs = strtotime($a->created_at ?? '') ?: 0;
            $bTs = strtotime($b->created_at ?? '') ?: 0;

            if ($aTs !== $bTs) {
                return $asc ? ($aTs <=> $bTs) : ($bTs <=> $aTs);
            }

            return $asc ? ((int) $a->id <=> (int) $b->id) : ((int) $b->id <=> (int) $a->id);
        });

        return $groups;
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
            ["id" => "yes", "text" => "Да"],
            ["id" => "no", "text" => "Нет"],
        ];
    }

    /**
     * Whether to collapse unread notifications by task/ticket.
     * Accepts new yes/no and legacy 0/1 query values.
     */
    private function should_group_notifications($grouped): bool
    {
        if ($grouped === null || $grouped === '') {
            return false;
        }

        $grouped = (string) $grouped;

        // Legacy: "0" = Да, "1" = Нет (was inverted vs empty() checks)
        if ($grouped === 'yes' || $grouped === '0') {
            return true;
        }

        return false;
    }

    function order_by_dropdown(): array
    {
        return [
            ["id" => "", "text" => "- " . app_lang("order_by") . " -"],
            ["id" => "DESC", "text" => "Новые"],
            ["id" => "ASC", "text" => "Старые"],
        ];
    }

    private function _notification_actor($notification) {
        $avatar = get_avatar("system_bot");
        $name = get_setting("app_title");

        if ($notification->user_id) {
            if ($notification->user_id == "999999998") {
                $avatar = get_avatar("bitbucket");
                $name = "Bitbucket";
            } else if ($notification->user_id == "999999997") {
                $avatar = get_avatar("github");
                $name = "GitHub";
            } else if ($notification->user_id == "999999996") {
                $signer_info = $notification->contract_meta_data ?? null;
                if (!empty($notification->estimate_id)) {
                    $signer_info = $notification->estimate_meta_data ?? null;
                } else if (!empty($notification->proposal_id)) {
                    $signer_info = $notification->proposal_meta_data ?? null;
                }

                $signer_info = @unserialize($signer_info);
                if (!($signer_info && is_array($signer_info))) {
                    $signer_info = array();
                }

                $signer_name = get_array_value($signer_info, "name");
                $name = $signer_name ?: app_lang("unknown_user");
                $avatar = get_avatar();
            } else {
                $avatar = get_avatar($notification->user_image ?? null);
                $name = $notification->user_name ?: get_setting("app_title");
            }
        }

        return array("name" => $name, "avatar" => $avatar);
    }

    private function _inbox_time_label($datetime) {
        if (!$datetime) {
            return "";
        }

        $ts = strtotime($datetime);
        if (!$ts) {
            return "";
        }

        $today = strtotime("today");
        $yesterday = strtotime("yesterday");

        if ($ts >= $today) {
            return date("H:i", $ts);
        }

        if ($ts >= $yesterday) {
            return "Вчера";
        }

        if ($ts >= strtotime("-6 days", $today)) {
            $days = array("Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб");
            return $days[(int) date("w", $ts)];
        }

        return date("d.m.Y", $ts);
    }

    private function _make_inbox_item($notification) {
        $changes_array = array();
        $activity_changes = $notification->activity_log_changes ?? "";
        if ($activity_changes !== "") {
            if ($notification->event === "bitbucket_push_received" || $notification->event === "github_push_received") {
                $changes_array = get_change_logs_array($activity_changes, $notification->activity_log_type, $notification->event, true);
            } else {
                $changes_array = get_change_logs_array($activity_changes, $notification->activity_log_type, "all");
            }

            if (!count($changes_array)) {
                return null;
            }
        }

        $actor = $this->_notification_actor($notification);
        $event_label = strip_tags(sprintf(app_lang("notification_" . $notification->event), $notification->to_user_name));

        $entity_title = "";
        if (!empty($notification->ticket_id) && !empty($notification->ticket_title)) {
            $entity_title = get_ticket_id($notification->ticket_id) . " — " . $notification->ticket_title;
        } else if (!empty($notification->task_id) && !empty($notification->task_title)) {
            $entity_title = "#" . $notification->task_id . " — " . $notification->task_title;
        } else if (!empty($notification->project_title)) {
            $entity_title = $notification->project_title;
        } else if (!empty($notification->announcement_title)) {
            $entity_title = $notification->announcement_title;
        } else if (!empty($notification->event_title)) {
            $entity_title = $notification->event_title;
        }

        $preview = "";
        if (!empty($notification->ticket_comment_description)) {
            $preview = $notification->ticket_comment_description;
        } else if (!empty($notification->project_comment_title)) {
            $preview = $notification->project_comment_title;
        } else if (!empty($notification->posts_title)) {
            $preview = $notification->posts_title;
        }

        $preview_html = (string) $preview;
        // Keep word breaks when tags are stripped (</p><a>, <br>, etc.)
        $preview_html = preg_replace('/<\s*(br|p|div|li|tr|h[1-6])\b[^>]*>/i', ' ', $preview_html);
        $preview_html = preg_replace('/<\/\s*(p|div|li|tr|h[1-6]|a)\s*>/i', ' ', $preview_html);
        $preview_html = preg_replace('/<\s*a\b[^>]*>/i', ' ', $preview_html);
        $preview = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($preview_html), ENT_QUOTES | ENT_HTML5, "UTF-8")));
        // Space before glued URLs: "группыhttps://"
        $preview = preg_replace('/([^\s\/])(https?:\/\/)/u', '$1 $2', $preview);
        if (function_exists("mb_convert_encoding")) {
            $preview = mb_convert_encoding($preview, "UTF-8", "UTF-8");
        }
        // Drop broken chars (�) that appear after mid-byte cuts / bad encodings
        $preview = preg_replace('/\x{FFFD}/u', '', $preview);
        $preview = trim((string) $preview);

        $max_preview = 520;
        if (function_exists("mb_strlen") && mb_strlen($preview, "UTF-8") > $max_preview) {
            $preview = rtrim(mb_substr($preview, 0, $max_preview - 1, "UTF-8"), " \t.,;:!-") . "…";
        } else if (strlen($preview) > $max_preview) {
            $cut = substr($preview, 0, $max_preview - 1);
            // Avoid cutting inside a UTF-8 multibyte sequence
            $cut = preg_replace('/[\xC0-\xFF][\x80-\xBF]*$/u', '', $cut);
            $preview = rtrim($cut, " \t.,;:!-") . "…";
        }

        $notification_ids = array((int) $notification->id);
        if (property_exists($notification, "notification_ids_in_group") && is_array($notification->notification_ids_in_group)) {
            $notification_ids = array_values(array_unique(array_merge($notification_ids, array_map("intval", $notification->notification_ids_in_group))));
        }

        $url_attributes_array = get_notification_url_attributes($notification);
        $avatar_url = $actor["avatar"];
        $avatar_initial = "";
        if ($actor["name"] && function_exists("mb_substr")) {
            $avatar_initial = mb_strtoupper(mb_substr($actor["name"], 0, 1));
        } else if ($actor["name"]) {
            $avatar_initial = strtoupper(substr($actor["name"], 0, 1));
        }

        return array(
            "id" => (int) $notification->id,
            "ids" => $notification_ids,
            "actor_name" => $actor["name"],
            "avatar_url" => $avatar_url,
            "avatar_initial" => $avatar_initial,
            "event_label" => $event_label,
            "entity_title" => $entity_title,
            "preview" => $preview,
            "project_title" => $notification->project_title ?: "",
            "created_at" => $notification->created_at,
            "time_label" => $this->_inbox_time_label($notification->created_at),
            "is_unread" => empty($notification->is_read),
            "ticket_id" => !empty($notification->ticket_id) ? (int) $notification->ticket_id : 0,
            "task_id" => !empty($notification->task_id) ? (int) $notification->task_id : 0,
            "url" => get_array_value($url_attributes_array, "url") ?: "#",
            "group_count" => count($notification_ids),
        );
    }

}

/* End of file notifications.php */
/* Location: ./app/controllers/Notifications.php */
