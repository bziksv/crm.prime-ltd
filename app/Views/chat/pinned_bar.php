<?php
$pinned_messages = $pinned_messages ?? array();
$count = count($pinned_messages);
if (!$count) {
    return;
}

// Chronological order for cycling through the timeline
$pins_chrono = $pinned_messages;
usort($pins_chrono, function ($a, $b) {
    return ((int) $a->id) <=> ((int) $b->id);
});

$pin_ids = array();
foreach ($pins_chrono as $p) {
    $pin_ids[] = (int) $p->id;
}

$first = $pins_chrono[0];
$preview = trim(strip_tags((string) ($first->message ?? '')));
if ($preview === '' && !empty($first->files)) {
    $preview = '📎 Файл';
}
if (mb_strlen($preview) > 90) {
    $preview = mb_substr($preview, 0, 90) . '…';
}

$preview_map = array();
foreach ($pins_chrono as $p) {
    $t = trim(strip_tags((string) ($p->message ?? '')));
    if ($t === '' && !empty($p->files)) {
        $t = '📎 Файл';
    }
    if (mb_strlen($t) > 90) {
        $t = mb_substr($t, 0, 90) . '…';
    }
    $preview_map[(int) $p->id] = $t !== '' ? $t : 'Сообщение';
}
?>
<div class="prime-chat-pins" id="prime-chat-pins"
    data-pin-ids="<?php echo esc(implode(',', $pin_ids)); ?>"
    data-pin-previews="<?php echo esc(json_encode($preview_map, JSON_UNESCAPED_UNICODE)); ?>"
    data-pin-index="0">
    <button type="button" class="prime-chat-pins-main js-pm-cycle-pin" title="Перейти к закрепу в чате">
        <i data-feather="bookmark" class="icon-14"></i>
        <span class="prime-chat-pins-count"><?php echo $count; ?></span>
        <span class="prime-chat-pins-preview js-pm-pin-preview"><?php echo esc($preview); ?></span>
    </button>
    <button type="button" class="prime-chat-pins-jump js-pm-toggle-pin-list" title="Все закрепы" aria-expanded="false">
        <i data-feather="chevron-down" class="icon-14"></i>
    </button>
    <div class="prime-chat-pins-list hide" id="prime-chat-pins-list">
        <?php foreach ($pins_chrono as $idx => $pin) {
            $text = $preview_map[(int) $pin->id];
            $who = trim((string) ($pin->user_name ?? ''));
            ?>
            <button type="button"
                class="prime-chat-pins-list-item js-pm-jump-msg"
                data-message-id="<?php echo (int) $pin->id; ?>"
                data-pin-index="<?php echo (int) $idx; ?>">
                <span class="prime-chat-pins-list-who"><?php echo esc($who !== '' ? $who : 'Сообщение'); ?></span>
                <span class="prime-chat-pins-list-text"><?php echo esc($text); ?></span>
            </button>
        <?php } ?>
    </div>
</div>
