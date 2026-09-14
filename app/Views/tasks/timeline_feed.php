<?php
$timeline_items = isset($timeline_items) && is_array($timeline_items) ? $timeline_items : array();
$omit_comment_list_scripts = !empty($omit_comment_list_scripts);

foreach ($timeline_items as $item) {
    $type = get_array_value($item, "type");
    if ($type === "comment") {
        $comment = get_array_value($item, "comment");
        if (!$comment) {
            continue;
        }
        echo view("projects/comments/comment_list", array(
            "comments" => array($comment),
            "omit_comment_list_scripts" => true,
        ));
    } else if ($type === "activity") {
        $log = get_array_value($item, "log");
        if (!$log) {
            continue;
        }
        echo view("activity_logs/activity_log_item_compact", array("log" => $log));
    }
}

if (!$omit_comment_list_scripts) {
    // Scripts once for pin/reply/copy handlers from comment_list
    echo view("projects/comments/comment_list", array(
        "comments" => array(),
        "omit_comment_list_scripts" => false,
    ));
}
