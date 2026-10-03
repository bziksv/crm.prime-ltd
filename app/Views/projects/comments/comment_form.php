<?php
$url = "projects/save_comment";
$comment_type = "";
if (isset($task_id)) {
    $comment_type = "task";
    $url = "tasks/save_comment";
} else if (isset($file_id)) {
    $comment_type = "file";
} else if (isset($customer_feedback_id)) {
    $comment_type = "customer_feedback";
} else {
    $comment_type = "project";
}

$mention_source = get_uri("projects/get_member_suggestion_to_mention");
if (isset($model_info->context) && $model_info->context != "project") {
    $mention_source = get_uri("tasks/get_member_suggestion_to_mention");
}
?>
<div id="<?php echo $comment_type . "-comment-form-container"; ?>" class="mb-4">
    <?php echo form_open(get_uri($url), array("id" => $comment_type . "-comment-form", "class" => "general-form", "role" => "form")); ?>
    <div class="d-flex comment-form-container">
        <div class="flex-shrink-0 d-none d-sm-block">
            <div class="avatar avatar-sm pr15 d-table-cell">
                <img src="<?php echo get_avatar($login_user->image); ?>" alt="..." />
            </div>
        </div>
        <div class="w-100">
            <div id="<?php echo $comment_type . "-dropzone"; ?>" class="post-dropzone mb-3 form-group">
                <input type="hidden" name="project_id" value="<?php echo isset($project_id) ? $project_id : 0; ?>">
                <input type="hidden" name="file_id" value="<?php echo isset($file_id) ? $file_id : 0; ?>">
                <input type="hidden" name="task_id" value="<?php echo isset($task_id) ? $task_id : 0; ?>">
                <input type="hidden" name="customer_feedback_id" value="<?php echo isset($customer_feedback_id) ? $customer_feedback_id : 0; ?>">
                <input type="hidden" name="reload_list" value="1">
                <input type="hidden" name="comment_type" value="<?php echo $comment_type; ?>">
                <input type="hidden" name="schedule_at" id="<?php echo $comment_type; ?>-schedule-at" value="">
                <input type="hidden" name="schedule_ts" id="<?php echo $comment_type; ?>-schedule-ts" value="">
                <?php
                echo form_textarea(array(
                    "id" => "comment_description",
                    "name" => "description",
                    "class" => "form-control comment_description",
                    "placeholder" => app_lang('write_a_comment'),
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang("field_required"),
                    "data-rich-text-editor" => true,
                    "data-mention" => true,
                    "data-mention-source" => $mention_source,
                    "data-mention-project_id" => $project_id
                ));
                ?>
                <?php echo view("includes/dropzone_preview"); ?>
                <footer class="card-footer b-a clearfix">
                    <div class="float-start">
                        <?php
                        if ($comment_type != "file") {
                            echo view("includes/upload_button");
                        }
                        ?>
                    </div>
                    <button class="btn btn-primary float-end" type="submit"><i data-feather="send" class='icon-16'></i> <?php echo app_lang("post_comment"); ?></button>
                    <?php if ($comment_type === "task") { ?>
                        <button class="btn btn-default float-end mr5 comment-schedule-toggle" type="button" id="<?php echo $comment_type; ?>-schedule-toggle" title="<?php echo app_lang("scheduled_comment_title"); ?>">
                            <i data-feather="clock" class="icon-16"></i>
                        </button>
                    <?php } ?>
                </footer>
            </div>
            <?php if ($comment_type === "task") { ?>
                <div class="comment-schedule-panel hide" id="<?php echo $comment_type; ?>-schedule-panel">
                    <div class="comment-schedule-label"><?php echo app_lang("scheduled_comment_when"); ?></div>
                    <div class="comment-schedule-presets" id="<?php echo $comment_type; ?>-schedule-presets">
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
                        <input type="datetime-local" class="form-control" id="<?php echo $comment_type; ?>-schedule-input" step="60">
                        <button type="button" class="btn btn-default" id="<?php echo $comment_type; ?>-schedule-confirm"><?php echo app_lang("scheduled_comment_confirm"); ?></button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#comment_description').appMention({
            source: "<?php echo $mention_source; ?>",
            data: {project_id: <?php echo $project_id; ?>}
        });

        $("#<?php echo $comment_type; ?>-comment-form").appForm({
            isModal: false,
            onSuccess: function (result) {

                <?php if ($comment_type === "task") { ?>
                    window.location.hash = ""; //prevent highlighting here
                <?php } ?>

                $(".comment_description").val("");
                $("#<?php echo $comment_type; ?>-schedule-at").val("");
                $("#<?php echo $comment_type; ?>-schedule-ts").val("");
                $("#<?php echo $comment_type; ?>-schedule-panel").addClass("hide");

                if (result.scheduled) {
                    if ($("#scheduled-comments-container").length && result.data) {
                        $("#scheduled-comments-container").prepend(result.data);
                        if (typeof feather !== "undefined") {
                            feather.replace();
                        }
                    }
                } else if ($("#file-preview-comment-container").length) {
                    $("#file-preview-comment-container").prepend(result.data);
                } else if ($(".comment-list-container").length) {
                    $(".comment-list-container").prepend(result.data);
                } else {
                    $(result.data).insertAfter("#<?php echo $comment_type; ?>-comment-form-container");
                }

                appAlert.success(result.message, {duration: 10000});

                var dropzoneId = "<?php echo $comment_type . '-dropzone'; ?>";

                if (window.formDropzone && window.formDropzone[dropzoneId]) {
                    window.formDropzone[dropzoneId].removeAllFiles();
                }
            }
        });

        <?php if ($comment_type === "task") { ?>
        (function () {
            var $panel = $("#<?php echo $comment_type; ?>-schedule-panel");
            var $input = $("#<?php echo $comment_type; ?>-schedule-input");
            var $hidden = $("#<?php echo $comment_type; ?>-schedule-at");
            var $hiddenTs = $("#<?php echo $comment_type; ?>-schedule-ts");
            var $form = $("#<?php echo $comment_type; ?>-comment-form");
            var $presets = $("#<?php echo $comment_type; ?>-schedule-presets");
            var weekendDays = String((AppHelper.settings && AppHelper.settings.weekends) || "0,6")
                .split(",")
                .map(function (d) { return parseInt(d, 10); })
                .filter(function (d) { return !isNaN(d); });

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
                var $textarea = $form.find("textarea.comment_description, textarea[name=description]").first();
                if ($textarea.length && typeof $textarea.summernote === "function" && $textarea.next(".note-editor").length) {
                    $textarea.val($textarea.summernote("code"));
                }
                var html = $.trim($textarea.val() || "");
                var text = $.trim($("<div>").html(html).text());
                return text.length > 0;
            }

            function showScheduleError(message) {
                appAlert.error(message);
            }

            function submitScheduled(date, $chip) {
                if (!date || isNaN(date.getTime()) || date.getTime() <= Date.now()) {
                    showScheduleError(<?php echo json_encode(app_lang("scheduled_comment_future_required")); ?>);
                    return;
                }
                if (!syncDescription()) {
                    showScheduleError(<?php echo json_encode(app_lang("field_required")); ?>);
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
                            $form.triggerHandler("scheduleSuccess", [result]);
                            if ($("#scheduled-comments-container").length && result.data) {
                                $("#scheduled-comments-container").prepend(result.data);
                            }
                            $form.find("textarea.comment_description, textarea[name=description]").val("");
                            if ($form.find(".note-editable").length) {
                                $form.find(".note-editable").html("");
                            }
                            $hidden.val("");
                            $hiddenTs.val("");
                            $panel.addClass("hide");
                            var dropzoneId = "<?php echo $comment_type . '-dropzone'; ?>";
                            if (window.formDropzone && window.formDropzone[dropzoneId]) {
                                window.formDropzone[dropzoneId].removeAllFiles();
                            }
                            appAlert.success(result.message || <?php echo json_encode(app_lang("scheduled_comment_saved")); ?>, {duration: 10000});
                            if (typeof feather !== "undefined") {
                                feather.replace();
                            }
                            if (typeof window.primeArmScheduledComments === "function") {
                                window.primeArmScheduledComments();
                            }
                        } else {
                            showScheduleError((result && result.message) ? result.message : <?php echo json_encode(app_lang("error_occurred")); ?>);
                        }
                    },
                    error: function () {
                        appLoader.hide();
                        showScheduleError(<?php echo json_encode(app_lang("error_occurred")); ?>);
                    }
                });
            }

            $("#<?php echo $comment_type; ?>-schedule-toggle").on("click", function (e) {
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

            $("#<?php echo $comment_type; ?>-schedule-confirm").on("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                submitScheduled(parseLocalDatetime($input.val()));
            });

            $form.find("button[type=submit]").on("click", function () {
                $hidden.val("");
                $hiddenTs.val("");
            });
        })();
        <?php } ?>
    });
</script>
