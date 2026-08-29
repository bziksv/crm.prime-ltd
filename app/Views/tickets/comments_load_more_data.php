<?php
echo view("tickets/comments_list", array(
    "comments" => $comments,
    "comment_recipients" => $comment_recipients,
));

echo view("tickets/comments_pagination_loader", array(
    "comments_has_more" => $comments_has_more,
    "comments_loader_offset" => $comments_loader_offset,
    "comments_total" => $comments_total,
    "comments" => $comments,
    "sort_as_decending" => $sort_as_decending,
    "ticket_id" => $ticket_id,
));
