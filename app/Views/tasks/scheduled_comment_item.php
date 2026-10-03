<?php
$preview = strip_tags((string) $item->description);
$preview = preg_replace('/\s+/', ' ', $preview);
if (function_exists('mb_strlen') && mb_strlen($preview) > 140) {
    $preview = mb_substr($preview, 0, 140) . "…";
} else if (strlen($preview) > 140) {
    $preview = substr($preview, 0, 140) . "...";
}
?>
<div class="scheduled-comment-item" id="scheduled-comment-<?php echo $item->id; ?>" data-due-ts="<?php echo (int) strtotime($item->scheduled_at . ' UTC'); ?>">
    <div class="d-flex">
        <div class="flex-shrink-0 comment-avatar">
            <span class="avatar avatar-xs">
                <img src="<?php echo get_avatar($item->created_by_avatar); ?>" alt="..." />
            </span>
        </div>
        <div class="w-100 ps-2">
            <div class="scheduled-comment-meta">
                <span class="scheduled-comment-badge"><?php echo app_lang("scheduled_comment"); ?></span>
                <span class="text-off"><?php echo sprintf(app_lang("scheduled_comment_will_send"), format_to_datetime($item->scheduled_at)); ?></span>
                <a href="javascript:;" class="scheduled-comment-cancel" data-id="<?php echo $item->id; ?>">
                    <?php echo app_lang("scheduled_comment_cancel"); ?>
                </a>
            </div>
            <div class="scheduled-comment-preview"><?php echo $preview ? $preview : "—"; ?></div>
        </div>
    </div>
</div>
