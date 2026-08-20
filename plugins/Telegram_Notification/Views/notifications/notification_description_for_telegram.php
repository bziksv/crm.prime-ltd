<?php

if ($notification->task_id && $notification->task_title) {
    echo "\n<b>" . app_lang("task") . ":</b> #$notification->task_id - " . $notification->task_title;
}

if ($notification->payment_invoice_id) {
    echo "\n" . to_currency($notification->payment_amount, $notification->client_currency_symbol) . "  -  " . get_invoice_id($notification->payment_invoice_id);
}

if ($notification->ticket_id && $notification->ticket_title) {
    echo "\n" . get_ticket_id($notification->ticket_id) . " - " . $notification->ticket_title;
}

if ($notification->leave_id && $notification->leave_start_date) {
    $leave_date = format_to_date($notification->leave_start_date, FALSE);
    if ($notification->leave_start_date != $notification->leave_end_date) {
        $leave_date = sprintf(app_lang('start_date_to_end_date_format'), format_to_date($notification->leave_start_date, FALSE), format_to_date($notification->leave_end_date, FALSE));
    }
    echo "\n<b>" . app_lang("date") . ":</b> " . $leave_date;
}

if ($notification->project_comment_id) { 
    $text = '';
        
    if(isset($file) && $file) {
        $text .= "\n\n🖼 <b>Прикреплён файл</b>";
    }

    if($notification->project_comment_title && !strpos($notification->project_comment_title, "</a>")) {
        $text .= "\n\n<b>" . app_lang("comment") . ":</b> " . convert_mentions(convert_comment_link($notification->project_comment_title, false), false);
    } 

    echo $text;
}

if ($notification->project_file_id && $notification->project_file_title) {
    echo "\n<b>" . app_lang("file") . ":</b> " . remove_file_prefix($notification->project_file_title);
}

if ($notification->project_id && $notification->project_title) {
    echo "\n<b>" . app_lang("project") . ":</b> " . $notification->project_title;
}

if ($notification->project_id && $notification->task_description && $notification->event == 'project_task_created') {
    echo "\n<b>" . app_lang("decription") . ":</b> " . $notification->task_description;
}

if ($notification->estimate_id) {
    echo "\n" . get_estimate_id($notification->estimate_id);
}

if ($notification->contract_id && $notification->contract_title) {
    echo "\n" . get_contract_id($notification->contract_id) . ": " . $notification->contract_title;
}

if ($notification->proposal_id) {
    echo "\n" . get_proposal_id($notification->proposal_id);
}

if ($notification->order_id) {
    echo "\n" . get_order_id($notification->order_id);
}

if ($notification->event_title) {
    echo "\n<b>" . app_lang("event") . ":</b> " . $notification->event_title;
}

if ($notification->announcement_title) {
    echo "\n<b>" . app_lang("title") . ":</b> " . $notification->announcement_title;
}

if ($notification->post_id && $notification->posts_title) {
    echo "\n<b>" . app_lang("comment") . ":</b> " . $notification->posts_title;
}

//show data from hook
try {
    $notification_descriptions = array();
    $notification_descriptions = app_hooks()->apply_filters('app_filter_notification_description_for_telegram', $notification_descriptions, $notification);
    if ($notification_descriptions && is_array($notification_descriptions)) {
        foreach ($notification_descriptions as $notification_description) {
            echo $notification_description;
        }
    }
} catch (\Exception $ex) {
    log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
}
