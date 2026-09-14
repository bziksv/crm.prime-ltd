<?php
$status_label = $task->status_key_name ? app_lang($task->status_key_name) : $task->status_title;
$deadline_html = "-";
if ($task->deadline && is_date_exists($task->deadline)) {
    $deadline_html = format_to_date($task->deadline, false);
}
$project_title = $task->project_title ?: "";
?>
<div class="task-control-row">
    <div class="task-control-row-main">
        <div class="task-control-row-title">
            <?php
            echo modal_anchor(
                get_uri("tasks/view"),
                "#" . $task->id . " — " . $task->title,
                array(
                    "title" => app_lang("task_info") . " #" . $task->id,
                    "class" => "dark",
                    "data-post-id" => $task->id,
                    "data-modal-lg" => "1",
                )
            );
            ?>
        </div>
        <div class="task-control-row-meta">
            <?php
            echo htmlspecialchars($status_label);
            if ($project_title) {
                echo " · " . htmlspecialchars($project_title);
            }
            if ($task->assigned_to_user) {
                echo " · " . htmlspecialchars($task->assigned_to_user);
            }
            ?>
        </div>
    </div>
    <?php if ($kind === "overdue") { ?>
        <div class="task-control-row-deadline"><?php echo $deadline_html; ?></div>
    <?php } ?>
</div>
