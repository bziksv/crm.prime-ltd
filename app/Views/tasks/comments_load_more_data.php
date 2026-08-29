<?php
echo view("projects/comments/comment_list", array(
    "comments" => $comments,
    "omit_comment_list_scripts" => true,
));

echo view("tasks/comments_pagination_loader", array(
    "comments_has_more" => $comments_has_more,
    "comments_loader_offset" => $comments_loader_offset,
    "task_id" => $task_id,
));
