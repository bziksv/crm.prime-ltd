<div id="page-content" class="page-wrapper clearfix">
    <div class="task-control-page">
        <div class="task-control-header">
            <div>
                <h4 class="mb5"><?php echo app_lang("task_control"); ?></h4>
                <div class="text-off"><?php echo app_lang("task_control_lead"); ?></div>
            </div>
            <div class="task-control-header-actions">
                <?php echo anchor(get_uri("tasks/all_tasks"), "<i data-feather='arrow-left' class='icon-16'></i> " . app_lang("tasks"), array("class" => "btn btn-default")); ?>
                <button type="button" class="btn btn-danger" id="task-control-nudge-btn" <?php echo empty($overdue_tasks) ? "disabled" : ""; ?>>
                    <i data-feather="bell" class="icon-16"></i>
                    <?php echo app_lang("task_control_nudge_overdue"); ?>
                    <?php if (!empty($overdue_tasks)) { ?>
                        <span class="badge bg-light text-dark ms-1"><?php echo count($overdue_tasks); ?></span>
                    <?php } ?>
                </button>
                <button type="button" class="btn btn-warning" id="task-control-nudge-setters-btn" <?php echo empty($review_others_tasks) ? "disabled" : ""; ?>>
                    <i data-feather="bell" class="icon-16"></i>
                    <?php echo app_lang("task_control_nudge_setters"); ?>
                    <?php if (!empty($review_others_tasks)) { ?>
                        <span class="badge bg-light text-dark ms-1"><?php echo count($review_others_tasks); ?></span>
                    <?php } ?>
                </button>
            </div>
        </div>

        <div class="task-control-stats">
            <div class="task-control-stat is-overdue">
                <div class="task-control-stat-value"><?php echo count($overdue_tasks); ?></div>
                <div class="task-control-stat-label"><?php echo app_lang("task_control_overdue"); ?></div>
            </div>
            <div class="task-control-stat is-review">
                <div class="task-control-stat-value"><?php echo count($review_tasks); ?></div>
                <div class="task-control-stat-label"><?php echo app_lang("task_control_on_review"); ?></div>
            </div>
            <div class="task-control-stat is-review-others">
                <div class="task-control-stat-value"><?php echo count($review_others_tasks); ?></div>
                <div class="task-control-stat-label"><?php echo app_lang("task_control_on_review_others"); ?></div>
            </div>
        </div>

        <div class="task-control-nudge-previews">
            <div class="task-control-nudge-preview is-overdue">
                <strong><?php echo app_lang("task_control_nudge_preview"); ?> (<?php echo app_lang("task_control_nudge_overdue"); ?>):</strong>
                <div class="task-control-nudge-text"><?php echo nl2br(htmlspecialchars($nudge_message)); ?></div>
            </div>
            <div class="task-control-nudge-preview is-setters">
                <strong><?php echo app_lang("task_control_nudge_preview"); ?> (<?php echo app_lang("task_control_nudge_setters"); ?>):</strong>
                <div class="task-control-nudge-text"><?php echo nl2br(htmlspecialchars($nudge_setters_message)); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card task-control-card">
                    <div class="card-header">
                        <strong><?php echo app_lang("task_control_overdue"); ?></strong>
                        <span class="badge bg-danger"><?php echo count($overdue_tasks); ?></span>
                    </div>
                    <div class="card-body p0">
                        <?php if (empty($overdue_tasks)) { ?>
                            <div class="task-control-empty"><?php echo app_lang("no_data"); ?></div>
                        <?php } else { ?>
                            <div class="task-control-list">
                                <?php foreach ($overdue_tasks as $task) {
                                    echo view("tasks/control_task_row", array("task" => $task, "kind" => "overdue"));
                                } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card task-control-card">
                    <div class="card-header">
                        <strong><?php echo app_lang("task_control_on_review"); ?></strong>
                        <span class="badge bg-warning text-dark"><?php echo count($review_tasks); ?></span>
                    </div>
                    <div class="card-body p0">
                        <?php if (empty($review_tasks)) { ?>
                            <div class="task-control-empty"><?php echo app_lang("no_data"); ?></div>
                        <?php } else { ?>
                            <div class="task-control-list">
                                <?php foreach ($review_tasks as $task) {
                                    echo view("tasks/control_task_row", array("task" => $task, "kind" => "review"));
                                } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card task-control-card">
                    <div class="card-header">
                        <strong><?php echo app_lang("task_control_on_review_others"); ?></strong>
                        <span class="badge bg-info text-dark"><?php echo count($review_others_tasks); ?></span>
                    </div>
                    <div class="card-body p0">
                        <?php if (empty($review_others_tasks)) { ?>
                            <div class="task-control-empty"><?php echo app_lang("no_data"); ?></div>
                        <?php } else { ?>
                            <div class="task-control-list">
                                <?php foreach ($review_others_tasks as $task) {
                                    echo view("tasks/control_task_row", array("task" => $task, "kind" => "review_others"));
                                } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="task-control-release-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content task-control-release-modal">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo app_lang("task_control_release_title"); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="task-control-release-task" id="task-control-release-task-title"></div>
                <p class="task-control-release-lead"><?php echo app_lang("task_control_release_lead"); ?></p>
                <div class="task-control-release-note is-ok" id="task-control-release-has-auditor">
                    <?php echo app_lang("task_control_release_has_auditor"); ?>
                </div>
                <div class="task-control-release-note is-warn" id="task-control-release-need-auditor">
                    <?php echo app_lang("task_control_release_need_auditor"); ?>
                </div>
                <div class="form-group mb0" id="task-control-release-auditors-wrap">
                    <label for="task-control-release-auditors" class="font-weight-bold"><?php echo app_lang("task_control_release_select_auditor"); ?></label>
                    <input type="text" id="task-control-release-auditors" class="form-control" placeholder="<?php echo app_lang("task_control_release_select_auditor"); ?>" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang("cancel"); ?></button>
                <button type="button" class="btn btn-warning" id="task-control-release-confirm">
                    <i data-feather="user-minus" class="icon-16"></i>
                    <?php echo app_lang("task_control_release_confirm"); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.task-control-page { padding: 16px 18px 28px; }
.task-control-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.task-control-header h4 { margin: 0; font-weight: 700; }
.task-control-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.task-control-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.task-control-stat {
    min-width: 160px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px 14px;
}
.task-control-stat-value { font-size: 28px; font-weight: 700; line-height: 1; }
.task-control-stat.is-overdue .task-control-stat-value { color: #b42318; }
.task-control-stat.is-review .task-control-stat-value { color: #b54708; }
.task-control-stat.is-review-others .task-control-stat-value { color: #026aa2; }
.task-control-stat-label { margin-top: 4px; color: #667085; font-size: 13px; }
.task-control-nudge-previews {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.task-control-nudge-preview {
    border-radius: 10px;
    padding: 12px 14px;
}
.task-control-nudge-preview.is-overdue {
    background: #fff8f3;
    border: 1px solid #f9dbaf;
    color: #7a2e0e;
}
.task-control-nudge-preview.is-setters {
    background: #eff8ff;
    border: 1px solid #b2ddff;
    color: #175cd3;
}
.task-control-nudge-text { margin-top: 6px; white-space: pre-wrap; }
.task-control-card { border-radius: 10px; overflow: hidden; margin-bottom: 16px; }
.task-control-card .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    background: #fff;
}
.task-control-list { max-height: 70vh; overflow: auto; }
.task-control-row {
    display: flex;
    gap: 10px;
    padding: 12px 14px;
    border-top: 1px solid #eef0f3;
    align-items: flex-start;
    transition: opacity .2s ease, transform .2s ease, max-height .25s ease, padding .25s ease, margin .25s ease;
}
.task-control-row.is-leaving {
    opacity: 0;
    transform: translateX(-12px);
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin: 0;
    overflow: hidden;
    border-top-color: transparent;
}
.task-control-row:hover { background: #fafbfc; }
.task-control-row-main { min-width: 0; flex: 1; }
.task-control-row-title { font-weight: 600; color: #1d2939; }
.task-control-row-meta { margin-top: 3px; font-size: 12px; color: #667085; }
.task-control-row-deadline { color: #b42318; font-weight: 600; white-space: nowrap; font-size: 12px; padding-top: 2px; }
.task-control-row-actions { margin-top: 10px; }
.task-control-release-btn {
    border-radius: 8px;
    border-color: #e4e7ec;
    color: #667085;
    font-size: 12px;
    padding: 4px 10px;
}
.task-control-release-btn:hover {
    border-color: #f9dbaf;
    background: #fff8f3;
    color: #b54708;
}
.task-control-people { margin-top: 8px; display: grid; gap: 5px; }
.task-control-people-row {
    display: grid;
    grid-template-columns: 110px minmax(0, 1fr);
    gap: 8px;
    align-items: start;
}
.task-control-people-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: #98a2b3;
    padding-top: 3px;
    text-transform: none;
}
.task-control-people-row.is-executor .task-control-people-label { color: #027a48; }
.task-control-people-row.is-collaborator .task-control-people-label { color: #3538cd; }
.task-control-people-row.is-auditor .task-control-people-label { color: #b54708; }
.task-control-people-row.is-setter .task-control-people-label { color: #026aa2; }
.task-control-people-list { display: flex; flex-wrap: wrap; gap: 4px 8px; min-width: 0; }
.task-control-person-empty {
    font-size: 12px;
    color: #d0d5dd;
    padding-top: 2px;
}
.task-control-person {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    max-width: 100%;
    min-width: 0;
    background: #f8fafc;
    border: 1px solid #eef0f3;
    border-radius: 999px;
    padding: 1px 8px 1px 1px;
}
.task-control-person .avatar {
    width: 18px;
    height: 18px;
    flex: 0 0 auto;
}
.task-control-person .avatar img {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    object-fit: cover;
}
.task-control-person-name {
    font-size: 12px;
    color: #344054;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 160px;
}
.task-control-empty { padding: 28px 16px; text-align: center; color: #98a2b3; }
.task-control-release-modal .modal-content { border-radius: 14px; overflow: hidden; }
.task-control-release-modal .modal-header { border-bottom: 1px solid #eef0f3; }
.task-control-release-modal .modal-footer { border-top: 1px solid #eef0f3; }
.task-control-release-task {
    font-weight: 700;
    color: #1d2939;
    margin-bottom: 8px;
}
.task-control-release-lead {
    color: #667085;
    font-size: 13px;
    margin-bottom: 12px;
}
.task-control-release-note {
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 13px;
    margin-bottom: 12px;
}
.task-control-release-note.is-ok {
    background: #ecfdf3;
    border: 1px solid #abefc6;
    color: #067647;
}
.task-control-release-note.is-warn {
    background: #fff8f3;
    border: 1px solid #f9dbaf;
    color: #b54708;
}
.task-control-release-note.is-hidden,
#task-control-release-auditors-wrap.is-hidden { display: none; }
@media (max-width: 767px) {
    .task-control-people-row { grid-template-columns: 1fr; gap: 3px; }
}
</style>

<script>
$(document).ready(function () {
    if (typeof feather !== "undefined") {
        try {
            feather.replace();
        } catch (e) {
            console.warn("feather.replace failed", e);
        }
    }

    // Never leave a stuck full-page loader from a previous nudge attempt
    if (typeof appLoader !== "undefined") {
        appLoader.hide();
    }
    $("#app-loader").remove();

    var staffDropdown = <?php echo json_encode($staff_dropdown); ?>;
    var releaseState = { taskId: 0, hasAuditor: false };

    function initReleaseSelect(selectedIds) {
        var $input = $("#task-control-release-auditors");
        if ($input.data("select2")) {
            $input.select2("destroy");
        }
        $input.val("").select2({
            multiple: true,
            data: staffDropdown,
            placeholder: <?php echo json_encode(app_lang("task_control_release_select_auditor")); ?>,
            width: "100%"
        });
        if (selectedIds && selectedIds.length) {
            $input.val(selectedIds).trigger("change");
        }
    }

    function openReleaseModal($btn) {
        releaseState.taskId = parseInt($btn.data("task-id"), 10) || 0;
        releaseState.hasAuditor = String($btn.data("has-auditor")) === "1";
        var title = $btn.data("task-title") || "";
        var auditorIds = String($btn.data("auditor-ids") || "")
            .split(",")
            .map(function (id) { return parseInt(id, 10); })
            .filter(function (id) { return id > 0; })
            .map(String);

        $("#task-control-release-task-title").text(title);

        if (releaseState.hasAuditor) {
            $("#task-control-release-has-auditor").removeClass("is-hidden");
            $("#task-control-release-need-auditor").addClass("is-hidden");
            $("#task-control-release-auditors-wrap").addClass("is-hidden");
        } else {
            $("#task-control-release-has-auditor").addClass("is-hidden");
            $("#task-control-release-need-auditor").removeClass("is-hidden");
            $("#task-control-release-auditors-wrap").removeClass("is-hidden");
            initReleaseSelect(auditorIds);
        }

        var modalEl = document.getElementById("task-control-release-modal");
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $("#task-control-release-modal").modal("show");
        }

        if (typeof feather !== "undefined") {
            try { feather.replace(); } catch (e) {}
        }
    }

    function closeReleaseModal() {
        var modalEl = document.getElementById("task-control-release-modal");
        if (window.bootstrap && bootstrap.Modal) {
            var instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
            }
        } else {
            $("#task-control-release-modal").modal("hide");
        }
    }

    function updateOverdueCount(delta) {
        var $stat = $(".task-control-stat.is-overdue .task-control-stat-value");
        var $badge = $(".task-control-card .card-header .badge.bg-danger").first();
        var $nudgeBadge = $("#task-control-nudge-btn .badge");
        var next = Math.max(0, (parseInt($stat.text(), 10) || 0) + delta);
        $stat.text(next);
        $badge.text(next);
        if ($nudgeBadge.length) {
            if (next > 0) {
                $nudgeBadge.text(next);
            } else {
                $nudgeBadge.remove();
                $("#task-control-nudge-btn").prop("disabled", true);
            }
        }
    }

    $(document).on("click", ".task-control-release-btn", function () {
        openReleaseModal($(this));
    });

    $("#task-control-release-confirm").on("click", function () {
        var $confirm = $(this);
        if ($confirm.data("busy") || !releaseState.taskId) {
            return;
        }

        var auditorIds = [];
        if (!releaseState.hasAuditor) {
            auditorIds = $("#task-control-release-auditors").val() || [];
            if (!auditorIds.length) {
                appAlert.error(<?php echo json_encode(app_lang("task_control_release_auditor_required")); ?>);
                return;
            }
        }

        $confirm.data("busy", true).prop("disabled", true).addClass("disabled");

        $.ajax({
            url: "<?php echo get_uri('tasks/control_release_overdue'); ?>",
            type: "POST",
            dataType: "json",
            data: {
                task_id: releaseState.taskId,
                auditor_ids: auditorIds
            },
            success: function (result) {
                $confirm.data("busy", false).prop("disabled", false).removeClass("disabled");
                if (!result || !result.success) {
                    appAlert.error((result && result.message) || <?php echo json_encode(app_lang("error_occurred")); ?>);
                    return;
                }

                closeReleaseModal();
                var $row = $('.task-control-row[data-task-id="' + releaseState.taskId + '"][data-kind="overdue"]');
                $row.addClass("is-leaving");
                setTimeout(function () {
                    $row.remove();
                    updateOverdueCount(-1);
                    var $list = $(".task-control-card").first().find(".task-control-list");
                    if ($list.length && !$list.children(".task-control-row").length) {
                        $list.closest(".card-body").html('<div class="task-control-empty"><?php echo app_lang("no_data"); ?></div>');
                    }
                }, 220);
                appAlert.success(result.message || <?php echo json_encode(app_lang("task_control_release_done")); ?>);
            },
            error: function () {
                $confirm.data("busy", false).prop("disabled", false).removeClass("disabled");
                appAlert.error(<?php echo json_encode(app_lang("error_occurred")); ?>);
            }
        });
    });

    function bindNudgeButton(options) {
        var $btn = $(options.button);
        var defaultHtml = $btn.html();

        function setProgress(doneCount, total) {
            var label = <?php echo json_encode(app_lang("task_control_nudge_progress")); ?>;
            $btn.html(label.replace("%s", doneCount).replace("%s", total));
        }

        function finish(sent, failed) {
            $btn.data("busy", false).prop("disabled", false).removeClass("disabled");
            $btn.html(defaultHtml);
            if (typeof feather !== "undefined") {
                try { feather.replace(); } catch (e) {}
            }
            appAlert.success(<?php echo json_encode(app_lang("task_control_nudge_done")); ?>.replace("%s", sent).replace("%s", failed), {duration: 8000});
        }

        function fail(message) {
            $btn.data("busy", false).prop("disabled", false).removeClass("disabled");
            $btn.html(defaultHtml);
            if (typeof feather !== "undefined") {
                try { feather.replace(); } catch (e) {}
            }
            appAlert.error(message || <?php echo json_encode(app_lang("error_occurred")); ?>);
        }

        function sendBatch(offset, sentTotal, failedTotal) {
            $.ajax({
                url: options.url,
                type: "POST",
                dataType: "json",
                timeout: 45000,
                data: {
                    offset: offset,
                    limit: 10
                },
                success: function (result) {
                    if (!result || !result.success) {
                        fail(result && result.message);
                        return;
                    }

                    var sent = sentTotal + (parseInt(result.sent, 10) || 0);
                    var failed = failedTotal + (parseInt(result.failed, 10) || 0);
                    var nextOffset = parseInt(result.next_offset, 10) || (offset + 10);
                    var total = parseInt(result.total, 10) || nextOffset;

                    setProgress(Math.min(nextOffset, total), total);

                    if (result.done) {
                        finish(sent, failed);
                        return;
                    }

                    setTimeout(function () {
                        sendBatch(nextOffset, sent, failed);
                    }, 150);
                },
                error: function () {
                    fail();
                }
            });
        }

        $btn.on("click", function () {
            if ($btn.prop("disabled") || $btn.data("busy")) {
                return;
            }

            if (!window.confirm(options.confirmText)) {
                return;
            }

            $btn.data("busy", true).prop("disabled", true).addClass("disabled");
            setProgress(0, options.total);
            sendBatch(0, 0, 0);
        });
    }

    bindNudgeButton({
        button: "#task-control-nudge-btn",
        url: "<?php echo get_uri('tasks/control_nudge_overdue'); ?>",
        confirmText: <?php echo json_encode(sprintf(app_lang("task_control_nudge_confirm"), count($overdue_tasks))); ?>,
        total: <?php echo (int) count($overdue_tasks); ?>
    });

    bindNudgeButton({
        button: "#task-control-nudge-setters-btn",
        url: "<?php echo get_uri('tasks/control_nudge_setters'); ?>",
        confirmText: <?php echo json_encode(sprintf(app_lang("task_control_nudge_setters_confirm"), count($review_others_tasks))); ?>,
        total: <?php echo (int) count($review_others_tasks); ?>
    });
});
</script>
