<?php
$comment_recipients = isset($comment_recipients) ? $comment_recipients : array();
$comments_total = isset($comments_total) ? $comments_total : count($comments);
$comments_has_more = !empty($comments_has_more);
$comments_loader_offset = isset($comments_loader_offset) ? $comments_loader_offset : 0;

$pagination_loader_data = array(
    "comments_has_more" => $comments_has_more,
    "comments_loader_offset" => $comments_loader_offset,
    "comments_total" => $comments_total,
    "comments" => $comments,
    "sort_as_decending" => $sort_as_decending,
    "ticket_id" => $ticket_info->id,
);

$list_data = array(
    "comments" => $comments,
    "comment_recipients" => $comment_recipients,
);

// ascending: older comments load above the visible list, then form below
if (!$sort_as_decending) {
    echo view("tickets/comments_pagination_loader", $pagination_loader_data);
    echo '<div id="ticket-comments-list">';
    echo view("tickets/comments_list", $list_data);
    echo '</div>';
}
?>

<div class="card" id="comment-form-container">
    <?php echo form_open(get_uri("tickets/save_comment"), array("id" => "comment-form", "class" => "general-form", "role" => "form")); ?>
    <div class="ticket-composer-body d-flex">
        <div class="flex-shrink-0 hidden-xs ticket-composer-avatar">
            <div class="avatar avatar-sm">
                <img src="<?php echo get_avatar($login_user->image); ?>" alt="..." />
            </div>
        </div>

        <div class="w-100">
            <div id="ticket-comment-dropzone" class="post-dropzone form-group mb0">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket_info->id; ?>">
                <input type="hidden" id="is-note" name="is_note" value="0">
                <?php
                echo form_textarea(array(
                    "id" => "description",
                    "name" => "description",
                    "class" => "form-control ticket-comment-textarea",
                    "style" => "height: 88px",
                    "value" => process_images_from_content(get_setting('user_' . $login_user->id . '_signature'), false),
                    "placeholder" => app_lang('write_a_comment'),
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang("field_required"),
                    "data-rich-text-editor" => true
                ));
                ?>
                <?php echo view("includes/dropzone_preview"); ?>
                <footer class="ticket-composer-bar">
                    <div class="ticket-composer-tools">
                        <?php echo view("includes/upload_button", array("upload_button_text" => "")); ?>
                        <?php
                        if ($login_user->user_type === "staff" && $view_type != "modal_view") {
                            echo modal_anchor(
                                get_uri("tickets/insert_template_modal_form"),
                                "<i data-feather='plus-circle' class='icon-16'></i>",
                                array(
                                    "class" => "btn btn-default ticket-composer-tool-btn",
                                    "title" => app_lang('insert_template'),
                                    "data-bs-toggle" => "tooltip",
                                    "data-post-ticket_type_id" => $ticket_info->ticket_type_id,
                                    "id" => "insert-template-btn"
                                )
                            );
                        }
                        ?>
                    </div>
                    <div class="ticket-composer-actions">
                        <?php if ($login_user->user_type === "staff") { ?>
                            <button id="save-as-note-button" class="btn btn-default ticket-composer-note-btn" type="button" data-bs-toggle="tooltip" title="<?php echo app_lang('client_will_not_see_any_notes') ?>">
                                <i data-feather="eye-off" class="icon-16"></i>
                                <span>Примечание</span>
                            </button>
                        <?php } ?>
                        <button id="save-ticket-comment-button" class="btn btn-primary ticket-composer-send-btn" type="submit">
                            <i data-feather="send" class="icon-16"></i>
                            <span>Отправить</span>
                        </button>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>

<div id="comment-pin-container" class="ticket-comment-pin-gap"></div>

<?php
// descending: comments below the form, older batches load further down
if ($sort_as_decending) {
    echo '<div id="ticket-comments-list">';
    echo view("tickets/comments_list", $list_data);
    echo '</div>';
    echo view("tickets/comments_pagination_loader", $pagination_loader_data);
}
?>
