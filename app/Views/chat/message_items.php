<?php
$last_read_message_id = (int) ($last_read_message_id ?? 0);
$unread_separator_shown = false;

foreach ($messages as $message) {
    $is_system = !empty($message->is_system);
    $mine = !$is_system && ((int) $message->from_user_id === (int) $login_user->id);
    $files = array();
    if (!empty($message->files)) {
        $unserialized = @unserialize($message->files);
        if (is_array($unserialized)) {
            $files = $unserialized;
        }
    }
    $has_text = trim(strip_tags((string) $message->message)) !== '';
    $reply_to_id = (int) ($message->reply_to_message_id ?? 0);
    $is_pinned = !empty($message->pinned_at);
    $is_edited = !empty($message->edited_at);
    $can_edit = !$is_system && $mine && !empty($message->created_at)
        && (strtotime(get_current_utc_time()) - strtotime($message->created_at)) <= 3600;
    $is_unread = !$is_system && !$mine && ((int) $message->id > $last_read_message_id);
    $reply_preview = '';
    if ($reply_to_id && !empty($message->reply_user_name)) {
        $reply_preview = trim(strip_tags((string) ($message->reply_message ?? '')));
        if ($reply_preview === '' && !empty($message->reply_files)) {
            $rf = @unserialize($message->reply_files);
            if (is_array($rf) && $rf) {
                $reply_preview = '📎 Файл';
            }
        }
        if (mb_strlen($reply_preview) > 120) {
            $reply_preview = mb_substr($reply_preview, 0, 120) . '…';
        }
    }
    $preview_for_reply = $has_text
        ? trim(preg_replace('/\s+/', ' ', strip_tags((string) $message->message)))
        : ($files ? '📎 Файл' : '');
    if (mb_strlen($preview_for_reply) > 120) {
        $preview_for_reply = mb_substr($preview_for_reply, 0, 120) . '…';
    }
    $search_blob = mb_strtolower(($message->user_name ?? '') . ' ' . strip_tags((string) $message->message));

    if ($is_unread && !$unread_separator_shown) {
        $unread_separator_shown = true;
        ?>
        <div class="prime-chat-unread-sep js-pm-unread-start" role="separator">
            <span>Непрочитанные</span>
        </div>
        <?php
    }

    if ($is_system) {
        ?>
        <div class="prime-chat-system"
            data-message-id="<?php echo (int) $message->id; ?>"
            data-system="1"
            data-search="<?php echo esc($search_blob); ?>">
            <span><?php echo esc((string) $message->message); ?></span>
        </div>
        <?php
        continue;
    }
    ?>
    <div class="prime-chat-bubble <?php echo $mine ? 'is-mine' : ''; ?> <?php echo $is_pinned ? 'is-pinned' : ''; ?> <?php echo $is_unread ? 'is-unread-msg' : ''; ?>"
        data-message-id="<?php echo $message->id; ?>"
        data-author="<?php echo esc($message->user_name); ?>"
        data-preview="<?php echo esc($preview_for_reply); ?>"
        data-raw="<?php echo esc((string) $message->message); ?>"
        data-has-files="<?php echo $files ? '1' : '0'; ?>"
        data-pinned="<?php echo $is_pinned ? '1' : '0'; ?>"
        data-editable="<?php echo $can_edit ? '1' : '0'; ?>"
        data-unread="<?php echo $is_unread ? '1' : '0'; ?>"
        data-created-at="<?php echo esc((string) $message->created_at); ?>"
        data-search="<?php echo esc($search_blob); ?>">
        <?php if (!$mine) { ?>
            <div class="prime-chat-bubble-meta">
                <button type="button" class="prime-chat-bubble-author js-pm-open-profile" data-user-id="<?php echo (int) $message->from_user_id; ?>" title="Открыть профиль">
                    <img class="prime-chat-bubble-avatar" src="<?php echo get_avatar($message->user_image); ?>" alt="">
                    <span><?php echo esc($message->user_name); ?></span>
                </button>
                <?php if ($is_pinned) { ?>
                    <i data-feather="bookmark" class="icon-12 prime-chat-pin-mark"></i>
                <?php } ?>
            </div>
        <?php } else if ($is_pinned) { ?>
            <div class="prime-chat-bubble-meta is-self-pin">
                <i data-feather="bookmark" class="icon-12 prime-chat-pin-mark"></i>
                <span>Закреплено</span>
            </div>
        <?php } ?>
        <div class="prime-chat-bubble-body">
            <?php if ($reply_to_id && $reply_preview !== '') { ?>
                <button type="button" class="prime-chat-quote js-pm-jump-reply" data-reply-id="<?php echo $reply_to_id; ?>">
                    <strong><?php echo esc($message->reply_user_name); ?></strong>
                    <span><?php echo esc($reply_preview); ?></span>
                </button>
            <?php } ?>
            <?php if ($has_text) {
                $emoji_only = prime_chat_is_emoji_only_message($message->message);
                $emoji_count = $emoji_only ? prime_chat_count_emojis($message->message) : 0;
                $text_class = 'prime-chat-bubble-text';
                if ($emoji_only) {
                    $text_class .= ' pm-emoji-only';
                    if ($emoji_count === 1) {
                        $text_class .= ' pm-emoji-solo';
                    }
                }
                ?>
                <div class="<?php echo esc($text_class); ?>"><?php echo format_prime_chat_message($message->message); ?></div>
            <?php } ?>
            <?php if ($files) { ?>
                <div class="prime-chat-bubble-files">
                    <?php echo view("chat/message_files", array(
                        "files" => $files,
                        "message_id" => (int) $message->id,
                    )); ?>
                </div>
            <?php } ?>
        </div>
        <div class="prime-chat-bubble-footer">
            <div class="prime-chat-bubble-actions">
                <button type="button" class="prime-chat-reply-btn js-pm-reply" title="Ответить">
                    <i data-feather="corner-up-left" class="icon-14"></i>
                    <span>Ответить</span>
                </button>
                <?php if ($can_edit) { ?>
                    <button type="button" class="prime-chat-reply-btn js-pm-edit" title="Изменить">
                        <i data-feather="edit-2" class="icon-14"></i>
                        <span>Изменить</span>
                    </button>
                <?php } ?>
                <button type="button"
                    class="prime-chat-reply-btn js-pm-pin <?php echo $is_pinned ? 'is-active' : ''; ?>"
                    title="<?php echo $is_pinned ? 'Открепить' : 'Закрепить'; ?>">
                    <i data-feather="bookmark" class="icon-14"></i>
                    <span><?php echo $is_pinned ? 'Открепить' : 'Закрепить'; ?></span>
                </button>
            </div>
            <div class="prime-chat-bubble-time">
                <?php if ($is_edited) { ?><span class="prime-chat-edited" title="Изменено">изм.</span> <?php } ?>
                <?php echo format_to_relative_time($message->created_at); ?>
            </div>
        </div>
    </div>
<?php } ?>
