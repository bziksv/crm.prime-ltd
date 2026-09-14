<?php
$status_label = $task->status_key_name ? app_lang($task->status_key_name) : $task->status_title;
$deadline_html = "-";
if ($task->deadline && is_date_exists($task->deadline)) {
    $deadline_html = format_to_date($task->deadline, false);
}
$project_title = $task->project_title ?: "";

$people_rows = array();

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
            "name" => $name,
            "avatar" => get_avatar(get_array_value($parts, 2)),
        );
    }
    return $people;
};

$executors = $parse_people_list(isset($task->executors_list) ? $task->executors_list : "");
$collaborators = $parse_people_list(isset($task->collaborator_list) ? $task->collaborator_list : "");
$assigned = array();
if (!empty($task->assigned_to_user)) {
    $assigned[] = array(
        "name" => $task->assigned_to_user,
        "avatar" => get_avatar(isset($task->assigned_to_avatar) ? $task->assigned_to_avatar : ""),
    );
}

// In this CRM: executors = исполнители, collaborators = участники, assigned_to = аудитор/назначенный
if ($executors) {
    $people_rows[] = array("label" => app_lang("executors"), "class" => "is-executor", "people" => $executors);
}
if ($collaborators) {
    $people_rows[] = array("label" => app_lang("collaborators"), "class" => "is-collaborator", "people" => $collaborators);
}
if ($assigned) {
    $people_rows[] = array("label" => app_lang("task_control_auditor"), "class" => "is-auditor", "people" => $assigned);
}
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
            ?>
        </div>
        <?php if ($people_rows) { ?>
            <div class="task-control-people">
                <?php foreach ($people_rows as $row) { ?>
                    <div class="task-control-people-row <?php echo $row["class"]; ?>">
                        <span class="task-control-people-label"><?php echo htmlspecialchars($row["label"]); ?></span>
                        <div class="task-control-people-list">
                            <?php foreach ($row["people"] as $person) { ?>
                                <span class="task-control-person" title="<?php echo htmlspecialchars($person["name"]); ?>">
                                    <span class="avatar avatar-xs">
                                        <img src="<?php echo $person["avatar"]; ?>" alt="" />
                                    </span>
                                    <span class="task-control-person-name"><?php echo htmlspecialchars($person["name"]); ?></span>
                                </span>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php if ($kind === "overdue") { ?>
        <div class="task-control-row-deadline"><?php echo $deadline_html; ?></div>
    <?php } ?>
</div>
