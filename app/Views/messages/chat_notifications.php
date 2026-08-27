<div class="card mb0">
    <div class="page-title clearfix notificatio-plate-title-area">
        <span class="float-start"><strong><?php echo app_lang('messages'); ?></strong></span>
    </div>

    <div class="list-group" id="messages-popup-list">
        <?php
        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                $title = $notification->display_title ?? '';
                $preview = $notification->preview ?? ($notification->last_message ?? '');
                $preview = trim(strip_tags((string) $preview));
                if (mb_strlen($preview) > 80) {
                    $preview = mb_substr($preview, 0, 80) . '…';
                }
                $unread = (int) ($notification->unread_count ?? 0);
                $when = $notification->last_message_at ?? ($notification->updated_at ?? '');
                $href = get_uri("messages/inbox/" . (int) $notification->id);
                ?>
                <a class="list-group-item d-flex unread-notification" href="<?php echo $href; ?>">
                    <div class="flex-shrink-0">
                        <span class="avatar avatar-xs">
                            <?php if (($notification->type ?? '') === 'group') { ?>
                                <?php echo prime_chat_group_avatar_html($notification->display_image ?? '', 'sm'); ?>
                            <?php } else { ?>
                                <img src="<?php echo get_avatar($notification->display_image ?? ''); ?>" alt="..." />
                            <?php } ?>
                        </span>
                    </div>
                    <div class="w-100 ps-2 text-wrap-ellipsis">
                        <div class="mb5">
                            <strong><?php echo esc($title); ?></strong>
                            <?php if ($unread > 0) { ?>
                                <span class="badge bg-primary rounded-pill ms-1"><?php echo $unread; ?></span>
                            <?php } ?>
                            <?php if ($when) { ?>
                                <span class="text-off float-end"><small><?php echo format_to_relative_time($when); ?></small></span>
                            <?php } ?>
                        </div>
                        <div class="text-wrap-ellipsis"><?php echo esc($preview !== '' ? $preview : '…'); ?></div>
                    </div>
                </a>
                <?php
            }
        } else {
            ?>
            <span class="list-group-item"><?php echo app_lang("no_new_messages"); ?></span>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if ($(window).width() > 640) {
            if ($('#messages-popup-list').height() >= 400) {
                initScrollbar('#messages-popup-list', {
                    setHeight: 400
                });
            } else {
                $('#messages-popup-list').css({"overflow-y": "auto"});
            }
        }
        if (typeof window.primeFeatherReplace === 'function') {
            window.primeFeatherReplace('#messages-popup-list');
        } else if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
