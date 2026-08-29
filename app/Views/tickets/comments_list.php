<?php
$comment_recipients = isset($comment_recipients) ? $comment_recipients : array();

foreach ($comments as $comment) {
    echo view("tickets/comment_row", array(
        "comment" => $comment,
        "comment_recipients" => get_array_value($comment_recipients, $comment->id) ?: array()
    ));
}
