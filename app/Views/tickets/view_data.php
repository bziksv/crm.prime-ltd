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
                <input type="hidden" name="schedule_at" id="ticket-schedule-at" value="">
                <input type="hidden" name="schedule_ts" id="ticket-schedule-ts" value="">
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
                        <button class="btn btn-default ticket-composer-schedule-btn comment-schedule-toggle" type="button" id="ticket-schedule-toggle" title="<?php echo app_lang("scheduled_comment_title"); ?>">
                            <i data-feather="clock" class="icon-16"></i>
                        </button>
                        <button id="save-ticket-comment-button" class="btn btn-primary ticket-composer-send-btn" type="submit">
                            <i data-feather="send" class="icon-16"></i>
                            <span>Отправить</span>
                        </button>
                    </div>
                </footer>
            </div>
            <div class="comment-schedule-panel hide" id="ticket-schedule-panel">
                <div class="comment-schedule-label"><?php echo app_lang("scheduled_comment_when"); ?></div>
                <div class="comment-schedule-presets" id="ticket-schedule-presets">
                    <div class="comment-schedule-preset-row">
                        <button type="button" class="comment-schedule-chip" data-preset="in" data-minutes="5">5 мин</button>
                        <button type="button" class="comment-schedule-chip" data-preset="in" data-minutes="10">10 мин</button>
                        <button type="button" class="comment-schedule-chip" data-preset="in" data-minutes="15">15 мин</button>
                        <button type="button" class="comment-schedule-chip" data-preset="in" data-minutes="30">30 мин</button>
                        <button type="button" class="comment-schedule-chip" data-preset="in" data-minutes="60">1 ч</button>
                        <button type="button" class="comment-schedule-chip" data-preset="in" data-minutes="120">2 ч</button>
                        <button type="button" class="comment-schedule-chip" data-preset="in" data-minutes="180">3 ч</button>
                    </div>
                    <div class="comment-schedule-preset-row">
                        <button type="button" class="comment-schedule-chip" data-preset="tomorrow" data-hour="8"><?php echo app_lang("scheduled_comment_tomorrow_08"); ?></button>
                        <button type="button" class="comment-schedule-chip" data-preset="tomorrow" data-hour="9"><?php echo app_lang("scheduled_comment_tomorrow_09"); ?></button>
                        <button type="button" class="comment-schedule-chip" data-preset="workday" data-hour="9"><?php echo app_lang("scheduled_comment_first_workday"); ?></button>
                    </div>
                </div>
                <div class="comment-schedule-row">
                    <input type="datetime-local" class="form-control" id="ticket-schedule-input" step="60">
                    <button type="button" class="btn btn-default" id="ticket-schedule-confirm"><?php echo app_lang("scheduled_comment_confirm"); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>

<div id="scheduled-ticket-comments-container" class="scheduled-comments-container">
    <?php echo view("tasks/scheduled_comments_list", array("scheduled_comments" => isset($scheduled_comments) ? $scheduled_comments : array())); ?>
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

<script type="text/javascript">
$(document).ready(function () {
    var $panel = $("#ticket-schedule-panel");
    var $input = $("#ticket-schedule-input");
    var $hidden = $("#ticket-schedule-at");
    var $hiddenTs = $("#ticket-schedule-ts");
    var $form = $("#comment-form");
    var $presets = $("#ticket-schedule-presets");
    var $textarea = $form.find("textarea[name=description]").first();
    var defaultDescription = $textarea.val() || "";
    var decending = <?php echo !empty($sort_as_decending) ? "true" : "false"; ?>;
    var ticketId = <?php echo (int) $ticket_info->id; ?>;
    var weekendDays = String((AppHelper.settings && AppHelper.settings.weekends) || "0,6")
        .split(",")
        .map(function (d) { return parseInt(d, 10); })
        .filter(function (d) { return !isNaN(d); });
    var timers = [];
    var flushing = false;

    function pad(n) {
        return n < 10 ? "0" + n : String(n);
    }

    function localDatetimeValue(date) {
        return date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate())
            + "T" + pad(date.getHours()) + ":" + pad(date.getMinutes());
    }

    function parseLocalDatetime(value) {
        var text = String(value || "").trim();
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
        if (match) {
            return new Date(+match[1], +match[2] - 1, +match[3], +match[4], +match[5], 0, 0);
        }
        match = text.match(/^(\d{2})\.(\d{2})\.(\d{4}),?\s*(\d{2}):(\d{2})/);
        if (match) {
            return new Date(+match[3], +match[2] - 1, +match[1], +match[4], +match[5], 0, 0);
        }
        return null;
    }

    function isWeekend(date) {
        return weekendDays.indexOf(date.getDay()) !== -1;
    }

    function atHour(base, hour) {
        var d = new Date(base.getTime());
        d.setHours(hour, 0, 0, 0);
        return d;
    }

    function nextWorkingMorning(hour) {
        var d = new Date();
        for (var i = 0; i < 14; i++) {
            var candidate = new Date(d.getFullYear(), d.getMonth(), d.getDate() + i, hour, 0, 0, 0);
            if (!isWeekend(candidate) && candidate.getTime() > Date.now()) {
                return candidate;
            }
        }
        return atHour(new Date(Date.now() + 24 * 60 * 60 * 1000), hour);
    }

    function presetDate($chip) {
        var preset = $chip.attr("data-preset");
        if (preset === "in") {
            return new Date(Date.now() + (parseInt($chip.attr("data-minutes"), 10) || 5) * 60 * 1000);
        }
        if (preset === "tomorrow") {
            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            return atHour(tomorrow, parseInt($chip.attr("data-hour"), 10) || 9);
        }
        return nextWorkingMorning(parseInt($chip.attr("data-hour"), 10) || 9);
    }

    function syncDescription() {
        if ($textarea.length && typeof $textarea.summernote === "function" && $textarea.next(".note-editor").length) {
            $textarea.val($textarea.summernote("code"));
        }
        var html = $.trim($textarea.val() || "");
        var text = $.trim($("<div>").html(html).text());
        return text.length > 0;
    }

    function resetComposer() {
        if ($textarea.length && typeof $textarea.summernote === "function" && $textarea.next(".note-editor").length) {
            $textarea.summernote("code", defaultDescription);
        } else {
            $textarea.val(defaultDescription);
        }
        $hidden.val("");
        $hiddenTs.val("");
        $panel.addClass("hide");
        if (window.formDropzone && window.formDropzone["ticket-comment-dropzone"]) {
            window.formDropzone["ticket-comment-dropzone"].removeAllFiles();
        }
    }

    function submitScheduled(date, $chip) {
        if (!date || isNaN(date.getTime()) || date.getTime() <= Date.now()) {
            appAlert.error(<?php echo json_encode(app_lang("scheduled_comment_future_required")); ?>);
            return;
        }
        if (!syncDescription()) {
            appAlert.error(<?php echo json_encode(app_lang("field_required")); ?>);
            return;
        }

        $presets.find(".comment-schedule-chip").removeClass("is-active");
        if ($chip) {
            $chip.addClass("is-active");
        }

        var value = localDatetimeValue(date);
        $input.val(value);
        $hidden.val(value);
        $hiddenTs.val(String(Math.floor(date.getTime() / 1000)));

        appLoader.show({container: $form, css: "top:2%; right:46%;"});
        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            dataType: "json",
            data: $form.serialize(),
            success: function (result) {
                appLoader.hide();
                if (result && result.success) {
                    if (result.data) {
                        $("#scheduled-ticket-comments-container").prepend(result.data);
                    }
                    resetComposer();
                    armScheduledComments();
                    appAlert.success(result.message || <?php echo json_encode(app_lang("scheduled_comment_saved")); ?>, {duration: 10000});
                    if (typeof feather !== "undefined") {
                        feather.replace();
                    }
                } else {
                    appAlert.error((result && result.message) ? result.message : <?php echo json_encode(app_lang("error_occurred")); ?>);
                }
            },
            error: function () {
                appLoader.hide();
                appAlert.error(<?php echo json_encode(app_lang("error_occurred")); ?>);
            }
        });
    }

    function applyPublished(result) {
        if (!result || !result.success || !result.published_ids || !result.published_ids.length) {
            return;
        }
        result.published_ids.forEach(function (id) {
            $("#scheduled-comment-" + id).remove();
        });
        if (result.data) {
            if ($("#ticket-comments-list").length) {
                if (decending) {
                    $("#ticket-comments-list").prepend(result.data);
                } else {
                    $("#ticket-comments-list").append(result.data);
                }
            } else if (decending) {
                $(result.data).insertAfter("#comment-form-container");
            } else {
                $(result.data).insertBefore("#comment-form-container");
            }
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }
    }

    function flushDue() {
        if (flushing || !$("#scheduled-ticket-comments-container .scheduled-comment-item").length) {
            return;
        }
        flushing = true;
        $.ajax({
            url: "<?php echo get_uri('tickets/flush_scheduled_comments'); ?>",
            type: "POST",
            dataType: "json",
            data: {ticket_id: ticketId},
            complete: function () {
                flushing = false;
            },
            success: applyPublished
        });
    }

    function armScheduledComments() {
        timers.forEach(function (id) { clearTimeout(id); });
        timers = [];
        $("#scheduled-ticket-comments-container .scheduled-comment-item").each(function () {
            var ts = parseInt($(this).attr("data-due-ts"), 10);
            if (!ts) {
                return;
            }
            timers.push(setTimeout(flushDue, Math.max(0, ts * 1000 - Date.now()) + 400));
        });
    }

    $("#ticket-schedule-toggle").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $panel.toggleClass("hide");
        if (!$panel.hasClass("hide")) {
            var current = parseLocalDatetime($input.val());
            if (!current || current.getTime() <= Date.now()) {
                $input.val(localDatetimeValue(new Date(Date.now() + 5 * 60 * 1000)));
            }
        }
    });

    $presets.on("click", ".comment-schedule-chip", function (e) {
        e.preventDefault();
        e.stopPropagation();
        submitScheduled(presetDate($(this)), $(this));
    });

    $("#ticket-schedule-confirm").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        submitScheduled(parseLocalDatetime($input.val()));
    });

    $("#save-ticket-comment-button, #save-as-note-button").on("click", function () {
        $hidden.val("");
        $hiddenTs.val("");
    });

    $(document).off("click.ticketScheduledCommentCancel").on("click.ticketScheduledCommentCancel", "#scheduled-ticket-comments-container .scheduled-comment-cancel", function () {
        var $item = $(this).closest(".scheduled-comment-item");
        var id = $(this).attr("data-id");
        if (!id) {
            return;
        }
        appLoader.show();
        $.ajax({
            url: "<?php echo get_uri('tickets/cancel_scheduled_comment'); ?>",
            type: "POST",
            dataType: "json",
            data: {id: id},
            success: function (result) {
                appLoader.hide();
                if (result && result.success) {
                    $item.fadeOut(200, function () { $(this).remove(); });
                    appAlert.success(result.message);
                } else {
                    appAlert.error((result && result.message) ? result.message : <?php echo json_encode(app_lang("error_occurred")); ?>);
                }
            },
            error: function () {
                appLoader.hide();
                appAlert.error(<?php echo json_encode(app_lang("error_occurred")); ?>);
            }
        });
    });

    armScheduledComments();
    setInterval(function () {
        var now = Math.floor(Date.now() / 1000);
        var due = false;
        $("#scheduled-ticket-comments-container .scheduled-comment-item").each(function () {
            var ts = parseInt($(this).attr("data-due-ts"), 10);
            if (ts && ts <= now) {
                due = true;
            }
        });
        if (due) {
            flushDue();
        }
    }, 20000);
});
</script>
