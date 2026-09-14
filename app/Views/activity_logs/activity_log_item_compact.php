<?php
$changes_array = get_change_logs_array($log->changes, $log->log_type, $log->action);
$show_log = $log->action !== "updated" || (count($changes_array) && $log->changes !== "");

if ($show_log) {
    $label_class = "default";
    $action = $log->action;
    if ($action === "created") {
        $label_class = "success";
        $action = "added";
    } else if ($action === "updated") {
        $label_class = "warning";
    } else if ($action === "deleted") {
        $label_class = "danger";
    } else if ($action === "checklist_item_unchecked" || $action === "checklist_item_updated") {
        $label_class = "warning";
    } else if ($action === "checklist_item_deleted") {
        $label_class = "danger";
    } else if ($action === "checklist_item_added" || $action === "checklist_item_checked") {
        $label_class = "success";
    }

    $log_caption = app_lang($action);
    if (strpos((string) $log->action, "checklist_item_") === 0) {
        $log_caption = app_lang("activity_log_" . $log->action);
    }
    ?>
    <div class="task-timeline-item task-timeline-activity d-flex border-bottom mb-3">
        <div class="flex-shrink-0 me-2 mt-2">
            <span class="avatar avatar-xs">
                <?php if ($log->created_by_user) { ?>
                    <img src="<?php echo get_avatar($log->created_by_avatar); ?>" alt="..." />
                <?php } else { ?>
                    <img src="<?php echo get_avatar("system_bot"); ?>" alt="..." />
                <?php } ?>
            </span>
        </div>
        <div class="p-2 w-100">
            <div class="card-title mb5">
                <?php
                if ($log->created_by_user) {
                    if ($log->user_type === "staff") {
                        echo get_team_member_profile_link($log->created_by, $log->created_by_user, array("class" => "dark strong"));
                    } else {
                        echo get_client_contact_profile_link($log->created_by, $log->created_by_user, array("class" => "dark strong"));
                    }
                } else {
                    echo "<strong>" . get_setting("app_title") . "</strong>";
                }
                ?>
                <small><span class="text-off"><?php echo format_to_relative_time($log->created_at); ?></span></small>
            </div>
            <div class="task-timeline-activity-body">
                <span class="badge bg-<?php echo $label_class; ?>"><?php echo $log_caption; ?></span>
                <?php if (count($changes_array)) { ?>
                    <ul class="task-timeline-changes mb0 mt5">
                        <?php foreach ($changes_array as $change) {
                            echo process_images_from_content($change);
                        } ?>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
}
