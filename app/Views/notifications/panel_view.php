<?php
$open_url = $url && $url !== "#" ? $url : "";
?>
<div class="notification-panel-view" data-notification-id="<?php echo (int) $notification->id; ?>">
    <div class="notification-panel-header">
        <div class="notification-panel-heading">
            <div class="notification-panel-actor">
                <span class="avatar avatar-sm">
                    <img src="<?php echo $actor_avatar; ?>" alt="">
                </span>
                <div>
                    <div class="notification-panel-actor-name"><?php echo $actor_name; ?></div>
                    <div class="notification-panel-time text-off">
                        <small><?php echo format_to_relative_time($notification->created_at); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="notification-panel-toolbar">
            <button type="button" class="btn btn-default btn-sm js-notification-mark-unread" title="Отметить как непрочитанное">
                Отметить как непрочитанное
            </button>
            <?php if ($open_url): ?>
                <a href="<?php echo $open_url; ?>" class="btn btn-default btn-sm" title="Открыть" <?php echo (strpos($url_attributes, "data-act=") !== false || strpos($url_attributes, "data-toggle=") !== false) ? "" : 'target="_blank"'; ?>>
                    <i data-feather="external-link" class="icon-16"></i>
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-default btn-sm js-notification-panel-close" title="<?php echo app_lang('close'); ?>">
                <i data-feather="x" class="icon-16"></i>
            </button>
        </div>
    </div>

    <div class="notification-panel-body">
        <div class="notification-panel-event">
            <?php echo $event_label; ?>
        </div>
        <div class="notification-panel-description">
            <?php echo view("notifications/notification_description", array("notification" => $notification, "changes_array" => $changes_array)); ?>
        </div>

        <?php if ($open_url || (strpos($url_attributes, "data-act=") !== false) || (strpos($url_attributes, "data-toggle=") !== false)): ?>
            <div class="notification-panel-cta">
                <a class="btn btn-primary" <?php echo $url_attributes; ?>>
                    Открыть
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    $(function () {
        if (typeof feather !== "undefined") {
            feather.replace();
        }
    });
</script>
