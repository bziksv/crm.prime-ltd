<?php

/* Don't change or add any new config in this file */

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Rise extends BaseConfig {

    public $app_settings_array = array(
        "app_version" => "3.6.1",
        "app_update_url" => 'https://releases.fairsketch.com/rise/',
        "updates_path" => './updates/',
    );
    public $app_csrf_exclude_uris = array(
        "notification_processor/create_notification",
        "paypal_redirect", "paypal_redirect/index",
        "paytm_redirect", "paytm_redirect/index", "paytm_redirect.*+",
        "stripe_redirect", "stripe_redirect/index",
        "pay_invoice", "pay_invoice/*",
        "google_api/save_access_token", "google_api/save_access_token_of_calendar", "google_api/save_access_token_of_own_calendar",
        "webhooks_listener.*+",
        "external_tickets.*+",
        "collect_leads.*+",
        "upload_pasted_image.*+",
        "request_estimate.*+",
        "events/snooze_reminder", "events/reminder_view", "events/save_reminder_status",
        "cron",
        "notifications/count_notifications", "notifications/count_ticket_notifications", "notifications/get_notifications",
        "messages/count_notifications",
        "chat/count_unread", "chat/poll", "chat/panel", "chat/start_dm", "chat/create_group", "chat/send", "chat/conversation",
        "chat/toggle_pin", "chat/filter_messages", "chat/clear_history", "chat/delete_conversation", "chat/edit_message", "chat/staff_profile",
        "chat/mark_read", "chat/toggle_star", "chat/group_info", "chat/remove_member", "chat/add_members", "chat/set_group_avatar",
        "chat/save_auto_cleanup",
        "microsoft_api/save_outlook_smtp_access_token",
        "event_tracker.*+"
    );

    public function __construct() {
        $this->app_csrf_exclude_uris = app_hooks()->apply_filters('app_filter_app_csrf_exclude_uris', $this->app_csrf_exclude_uris);
    }

}
