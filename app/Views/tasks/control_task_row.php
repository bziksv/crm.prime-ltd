<?php
$status_label = $task->status_key_name ? app_lang($task->status_key_name) : $task->status_title;
$deadline_html = "-";
if ($task->deadline && is_date_exists($task->deadline)) {
    $deadline_html = format_to_date($task->deadline, false);
}
$project_title = $task->project_title ?: "";

$parse_people_list = function ($list) {
    $people = array();
    if (!$list) {
        return $people;
    }
    foreach (explode(",", $list) as $item) {
        $parts = explode("--::--", $item);
        $name = trim(get_array_value($parts, 1));
        if ($name === "") {
            continue;
        }
        $people[] = array(
            "id" => (int) get_array_value($parts, 0),
            "name" => $name,
            "avatar" => get_avatar(get_array_value($parts, 2)),
        );
    }
    return $people;
};

// Roles:
// Постановщик = создатель задачи (activity_logs.created)
// Исполнители = executors
// Участники = collaborators
// Аудитор = auditors (новое поле), иначе legacy assigned_to
$setter = array();
if (!empty($task->setter_user)) {
    $setter[] = array(
        "id" => isset($task->setter_user_id) ? (int) $task->setter_user_id : 0,
        "name" => $task->setter_user,
        "avatar" => get_avatar(isset($task->setter_avatar) ? $task->setter_avatar : ""),
    );
}

$executors = $parse_people_list(isset($task->executors_list) ? $task->executors_list : "");
$collaborators = $parse_people_list(isset($task->collaborator_list) ? $task->collaborator_list : "");
$auditors = $parse_people_list(isset($task->auditors_list) ? $task->auditors_list : "");

$auditor_ids = array();
foreach ($auditors as $auditor) {
    if (!empty($auditor["id"])) {
        $auditor_ids[] = (int) $auditor["id"];
    }
}
$has_auditor = count($auditor_ids) > 0;

$people_rows = array(
    array("label" => app_lang("task_control_setter"), "class" => "is-setter", "people" => $setter),
    array("label" => app_lang("executors"), "class" => "is-executor", "people" => $executors),
    array("label" => app_lang("collaborators"), "class" => "is-collaborator", "people" => $collaborators),
    array(
        "label" => count($auditors) > 1 ? app_lang("task_control_auditors") : app_lang("task_control_auditor"),
        "class" => "is-auditor",
        "people" => $auditors,
    ),
);
?>
<div class="task-control-row" data-task-id="<?php echo (int) $task->id; ?>" data-kind="<?php echo htmlspecialchars($kind); ?>">
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
            ?>
        </div>
        <div class="task-control-people">
            <?php foreach ($people_rows as $row) { ?>
                <div class="task-control-people-row <?php echo $row["class"]; ?>">
                    <span class="task-control-people-label"><?php echo htmlspecialchars($row["label"]); ?></span>
                    <div class="task-control-people-list">
                        <?php if (!empty($row["people"])) { ?>
                            <?php foreach ($row["people"] as $person) { ?>
                                <span class="task-control-person" title="<?php echo htmlspecialchars($person["name"]); ?>">
                                    <span class="avatar avatar-xs">
                                        <img src="<?php echo $person["avatar"]; ?>" alt="" />
                                    </span>
                                    <span class="task-control-person-name"><?php echo htmlspecialchars($person["name"]); ?></span>
                                </span>
                            <?php } ?>
                        <?php } else { ?>
                            <span class="task-control-person-empty">—</span>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php if ($kind === "overdue" || $kind === "review") { ?>
            <div class="task-control-row-actions">
                <button
                    type="button"
                    class="btn btn-default btn-sm task-control-release-btn"
                    data-task-id="<?php echo (int) $task->id; ?>"
                    data-task-kind="<?php echo htmlspecialchars($kind); ?>"
                    data-task-title="<?php echo htmlspecialchars("#" . $task->id . " — " . $task->title, ENT_QUOTES, "UTF-8"); ?>"
                    data-has-auditor="<?php echo $has_auditor ? "1" : "0"; ?>"
                    data-auditor-ids="<?php echo htmlspecialchars(implode(",", $auditor_ids), ENT_QUOTES, "UTF-8"); ?>"
                >
                    <i data-feather="user-minus" class="icon-14"></i>
                    <?php echo app_lang("task_control_release"); ?>
                </button>
            </div>
        <?php } ?>
    </div>
    <?php if ($kind === "overdue") { ?>
        <div class="task-control-row-deadline"><?php echo $deadline_html; ?></div>
    <?php } ?>
</div>
