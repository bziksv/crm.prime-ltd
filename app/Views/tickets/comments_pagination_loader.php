<?php
if (!empty($comments_has_more)) {
    $container_id = "ticket-comments-load-" . $ticket_id . "-" . $comments_loader_offset;
    $remaining = 0;
    if ($sort_as_decending) {
        $remaining = max(0, $comments_total - $comments_loader_offset);
    } else {
        $remaining = $comments_loader_offset;
    }
    $load_more_label = app_lang("load_more");
    if ($remaining > 0) {
        $load_more_label .= " (" . $remaining . ")";
    }
    ?>
    <div id="<?php echo $container_id; ?>"></div>
    <div id="loader-<?php echo $container_id; ?>" class="text-center">
        <?php
        echo ajax_anchor(
            get_uri("tickets/load_more_comments/" . $ticket_id . "/" . $comments_loader_offset),
            $load_more_label,
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
