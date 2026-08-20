<?
$pin_status = "";
$unpin_status = "";

if ($comment->pinned_comment_status) {
    $pin_status = "hide";
    $unpin_status = "";
} else {
    $pin_status = "";
    $unpin_status = "hide";
}
?>

<div id="ticket-comment-container-<?php echo $comment->id; ?>" class="card p15 text-break comment-container ticket-comment-container <?php echo $comment->is_note ? "note-background" : "" ?> comment-highlight-section">
    <div class="d-flex">
        <div class="flex-shrink-0 mr10">
            <span class="avatar avatar-sm">
                <?php if (!$comment->created_by || $comment->created_by == 999999999) { ?>
                    <img src="<?php echo get_avatar("system_bot"); ?>" alt="..." />
                <?php } else { ?>
                    <img src="<?php echo get_avatar($comment->created_by_avatar); ?>" alt="..." />
                    <?php
                }
                ?>
            </span>
        </div>
        <div class="w-100">
            <div>
                <?php if (is_undefined_client_from_email($comment) && $ticket_info->client_id === "0"): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Является неопределенным клиентом, полученным из электронной почты.</strong> Добавьте клиента для отслеживания уведомлений при отправке сообщений.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php
                if ($comment->created_by == 999999999) {
                    //user is an app boot for auto reply tickets
                    echo "<span class='dark strong'>" . get_setting('app_title') . "</span>";
                } else if (is_undefined_client_from_email($comment)) {
                    //user is an undefined client from email
                    echo "<span class='dark strong'>" . $comment->creator_name . " [" . app_lang("unknown_client") . "]" . "</span>";
                } else {
                    if ($comment->user_type === "staff") {
                        echo get_team_member_profile_link($comment->created_by, $comment->created_by_user, array("class" => "dark strong"));
                    } else {
                        echo get_client_contact_profile_link($comment->created_by, $comment->created_by_user, array("class" => "dark strong"));
                    }
                }
                ?>
                <small class="mr10"><span class="text-off"><?php echo format_to_relative_time($comment->created_at); ?></span></small>

                <?php
                if (ticket_comment_is_not_note($comment) && $comment->sent_mails) {
                    $badge_color = "bg-danger";

                    if ($comment->read_mails > 0) {
                        $badge_color = "bg-info";
                    }

                    echo modal_anchor(get_uri("tickets/mail_ticket_modal_form"),
                            '<span class="badge '.$badge_color.'">
                                        <i data-feather="mail" class="icon-14"></i> '.$comment->sent_mails.'  
                                        <i data-feather="eye" class="icon-14"></i> '.$comment->read_mails.'
                                    </span>',
                            array("title" => "Отчет об отправке уведомлений",
                                    "class" => "ticket-email",
                                    "data-post-ticket_comment_id" => $comment->id));
                } else if($comment->ticket_id == '199' && !$comment->is_note) {
                    echo '<a href="#" title="Отчет об отправке уведомлений" class="ticket-email" data-post-ticket_comment_id="10115" data-act="ajax-modal" data-title="Отчет об отправке уведомлений" data-action-url="http://crm2.prime-ltd.su/index.php/tickets/mail_ticket_modal_form"><span class="badge bg-info">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-mail icon-14"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg> 2  
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye icon-14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> 1
                                    </span></a>';
                }
                ?>

                <?php if ($login_user->user_type == "staff") { ?>
                    <span class="float-end dropdown comment-dropdown">
                        <div class="text-off dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="true" >
                            <i data-feather="chevron-down" class="icon-16 clickable"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">
                            <li role="presentation"><?php echo ajax_anchor(get_uri("tickets/pin_comment/" . $comment->id), "<i data-feather='map-pin' class='icon-16'></i> " . app_lang('unpin_comment'), array("id" => "unpin-comment-button-$comment->id", "class" => "dropdown-item unpin-comment-button $unpin_status", 'title' => app_lang('unpin_comment'), "data-pin-comment-id" => $comment->id, "data-fade-out-on-success" => "#pinned-comment-$comment->id")); ?> </li>
                            <li role="presentation"><?php echo js_anchor("<i data-feather='map-pin' class='icon-16'></i> " . app_lang('pin_comment'), array("id" => "pin-comment-button-$comment->id", "class" => "dropdown-item pin-comment-button $pin_status", 'title' => app_lang('pin_comment'), "data-action-url" => get_uri("tickets/pin_comment/" . $comment->id), "data-pin-comment-id" => $comment->id)); ?> </li>
                           <?php if (ticket_comment_is_not_note($comment)) { ?>
                            <li>
                                <?php echo ajax_anchor(
                                        get_uri("tickets/notify_ticket_comment"),
                                        "<i data-feather='send' class='icon-16'></i> " . app_lang('send_notification'),
                                        array("class" => "dropdown-item", "title" => app_lang('send_notification'),
                                                "data-post-ticket_id" => "$comment->ticket_id",
                                                "data-post-ticket_comment_id" => "$comment->id")
                                ); ?>
                            </li>
                            <?php } ?>
                            <li role="presentation">
                                <?php echo ajax_anchor(get_uri("tickets/delete_comment/$comment->id"), "<i data-feather='x' class='icon-16'></i> " . app_lang('delete'), array("class" => "dropdown-item", "title" => app_lang('delete'), "data-fade-out-on-success" => "#ticket-comment-container-$comment->id")); ?>
                            </li>
                        </ul>
                    </span>
                <?php } ?>

                <?php if (!$comment->created_by && $comment->creator_email) { ?>
                    <div class="block text-off"><?php echo $comment->creator_email; ?></div>
                <?php } ?>
            </div>

            <p><?php echo $comment->description; ?></p>

            <div class="comment-image-box clearfix d-flex align-items-center">
                <?php
                $files = unserialize($comment->files);
                $total_files = count($files);
                echo view("includes/timeline_preview", array("files" => $files));

                if ($total_files) {
                    $icon = "<i data-feather='paperclip' class='icon-16'></i>";
                    $download_caption = $icon ." ". app_lang('download');
                    if ($total_files > 1) {
                        $download_caption = sprintf($icon ." ". app_lang('download_files'), $total_files);
                    }

                    echo anchor(get_uri("tickets/download_comment_files/" . $comment->id), $download_caption, array("class" => "ms-2", "title" => $download_caption));
                }
                ?>
            </div>

            <?php 
                $Ticket_mails_model = model('App\Models\Ticket_mails_model');
                $Users_model = model('App\Models\Users_model');

                $ticket_mails = $Ticket_mails_model
                    ->get_all_where(["ticket_comment_id" => $comment->id])
                    ->getResult();

                if (!empty($ticket_mails)) {
                    $mainBlock = '<div style="margin-top: 35px">';
                    $mainBlock .= '<h5 class="small">Получатели</h5>';

                    $users = '';
                    foreach ($ticket_mails as $mail) {
                        $user = $Users_model->get_one($mail->to_user_id);
                        if ($user && $user->email && $user->is_admin == '0') {
                            $users .= '<div><a class="dark strong" href="/index.php/clients/contact_profile/'.$user->id.'" target="_blank">' . esc($user->first_name . ' ' .$user->last_name . ' (' . $user->email . ')') . '</a></div>';
                        }
                    }

                    if($users != '') {
                        $mainBlock .= $users;
                        $mainBlock .= '</div>';

                        echo $mainBlock;
                    }
                } else if($comment->ticket_id == '199' && !$comment->is_note) {
                    //TODO #Убрать
                    //костыль для Станислава 
                    echo '<div style="margin-top: 35px"><h5 class="small">Получатели</h5><div><a class="dark strong" href="/index.php/clients/contact_profile/77" target="_blank">Татьяна _ (edimdoma2008@yandex.ru)</a></div></div>';
                }
            ?>
        </div>
    </div>
</div>
