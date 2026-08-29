<?php
if (!empty($comments_has_more)) {
    $container_id = "task-comments-load-" . $task_id . "-" . $comments_loader_offset;
    ?>
    <div id="<?php echo $container_id; ?>"></div>
    <div id="loader-<?php echo $container_id; ?>" class="text-center">
        <?php
        echo ajax_anchor(
            get_uri("tasks/load_more_comments/" . $task_id . "/" . $comments_loader_offset),
            app_lang("load_more"),
            array(
                "class" => "btn btn-default w-100 mt15 mb15 spinning-btn",
                "title" => app_lang("load_more"),
                "data-remove-on-success" => "#loader-" . $container_id,
                "data-inline-loader" => "1",
                "data-real-target" => "#" . $container_id,
            )
        );
        ?>
    </div>
    <?php
}
