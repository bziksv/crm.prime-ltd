<?php if (!empty($scheduled_comments)) { ?>
    <?php foreach ($scheduled_comments as $item) {
        echo view("tasks/scheduled_comment_item", array("item" => $item));
    } ?>
<?php } ?>
