<?php
$clocked_in = isset($clock_status) && !empty($clock_status->id);
$started_ts = 0;
if ($clocked_in) {
    $started_ts = (int) strtotime($clock_status->in_time . " UTC");
    if ($started_ts <= 0) {
        $started_ts = (int) strtotime($clock_status->in_time);
    }
}
$elapsed = $clocked_in && $started_ts > 0 ? max(0, time() - $started_ts) : 0;
$hh = str_pad((string) floor($elapsed / 3600), 2, "0", STR_PAD_LEFT);
$mm = str_pad((string) floor(($elapsed % 3600) / 60), 2, "0", STR_PAD_LEFT);
$ss = str_pad((string) ($elapsed % 60), 2, "0", STR_PAD_LEFT);
$time_label = $hh . ":" . $mm . ":" . $ss;

/* Play triangle optically centered in 24×24 (centroid ≈ 12,12). */
$play_icon = '<svg class="attendance-topbar-glyph attendance-topbar-glyph-play" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path d="M9.5 6.8v10.4L18.2 12 9.5 6.8z"></path></svg>';
$stop_icon = '<svg class="attendance-topbar-glyph" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><rect x="8" y="8" width="8" height="8" rx="1.2"></rect></svg>';
?>
<li class="nav-item" id="js-attendance-topbar-item">
    <div class="attendance-topbar-control <?php echo $clocked_in ? "is-running" : "is-stopped"; ?>"
         id="js-attendance-topbar-control"
         <?php if ($clocked_in) { ?>data-started-ts="<?php echo $started_ts; ?>"<?php } ?>>
        <span class="attendance-topbar-dot" aria-hidden="true"></span>
        <span class="attendance-topbar-time" id="js-attendance-topbar-time"><?php echo $time_label; ?></span>
        <?php if ($clocked_in) { ?>
            <?php
            echo modal_anchor(
                get_uri("attendance/note_modal_form"),
                $stop_icon,
                array(
                    "class" => "attendance-topbar-btn attendance-topbar-stop",
                    "id" => "js-attendance-topbar-btn",
                    "title" => app_lang('clock_out'),
                    "data-bs-toggle" => "tooltip",
                    "data-bs-placement" => "bottom",
                    "data-post-id" => $clock_status->id,
                    "data-post-clock_out" => 1,
                )
            );
            ?>
        <?php } else { ?>
            <a href="javascript:;"
               id="js-attendance-topbar-btn"
               class="attendance-topbar-btn attendance-topbar-start"
               title="<?php echo app_lang('clock_in'); ?>"
               data-bs-toggle="tooltip"
               data-bs-placement="bottom"
               data-act="attendance-topbar-clock-in">
                <?php echo $play_icon; ?>
            </a>
        <?php } ?>
    </div>
</li>
