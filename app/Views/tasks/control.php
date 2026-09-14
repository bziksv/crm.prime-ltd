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
        </div>

        <div class="task-control-nudge-preview">
            <strong><?php echo app_lang("task_control_nudge_preview"); ?>:</strong>
            <div class="task-control-nudge-text"><?php echo nl2br(htmlspecialchars($nudge_message)); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-6">
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
            <div class="col-lg-6">
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
.task-control-stat-label { margin-top: 4px; color: #667085; font-size: 13px; }
.task-control-nudge-preview {
    background: #fff8f3;
    border: 1px solid #f9dbaf;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 16px;
    color: #7a2e0e;
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
}
.task-control-row:hover { background: #fafbfc; }
.task-control-row-main { min-width: 0; flex: 1; }
.task-control-row-title { font-weight: 600; color: #1d2939; }
.task-control-row-meta { margin-top: 3px; font-size: 12px; color: #667085; }
.task-control-row-deadline { color: #b42318; font-weight: 600; white-space: nowrap; font-size: 12px; padding-top: 2px; }
.task-control-people { margin-top: 8px; display: grid; gap: 5px; }
.task-control-people-row {
    display: grid;
    grid-template-columns: 92px minmax(0, 1fr);
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
.task-control-people-list { display: flex; flex-wrap: wrap; gap: 4px 8px; min-width: 0; }
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

    var nudgeDefaultHtml = $("#task-control-nudge-btn").html();

    function setNudgeProgress(doneCount, total) {
        var label = <?php echo json_encode(app_lang("task_control_nudge_progress")); ?>;
        $("#task-control-nudge-btn").html(label.replace("%s", doneCount).replace("%s", total));
    }

    function finishNudge($btn, sent, failed) {
        $btn.data("busy", false).prop("disabled", false).removeClass("disabled");
        $btn.html(nudgeDefaultHtml);
        if (typeof feather !== "undefined") {
            try { feather.replace(); } catch (e) {}
        }
        appAlert.success(<?php echo json_encode(app_lang("task_control_nudge_done")); ?>.replace("%s", sent).replace("%s", failed), {duration: 8000});
    }

    function failNudge($btn, message) {
        $btn.data("busy", false).prop("disabled", false).removeClass("disabled");
        $btn.html(nudgeDefaultHtml);
        if (typeof feather !== "undefined") {
            try { feather.replace(); } catch (e) {}
        }
        appAlert.error(message || <?php echo json_encode(app_lang("error_occurred")); ?>);
    }

    function sendNudgeBatch($btn, offset, sentTotal, failedTotal) {
        $.ajax({
            url: "<?php echo get_uri('tasks/control_nudge_overdue'); ?>",
            type: "POST",
            dataType: "json",
            timeout: 45000,
            data: {
                offset: offset,
                limit: 10
            },
            success: function (result) {
                if (!result || !result.success) {
                    failNudge($btn, result && result.message);
                    return;
                }

                var sent = sentTotal + (parseInt(result.sent, 10) || 0);
                var failed = failedTotal + (parseInt(result.failed, 10) || 0);
                var nextOffset = parseInt(result.next_offset, 10) || (offset + 10);
                var total = parseInt(result.total, 10) || nextOffset;

                setNudgeProgress(Math.min(nextOffset, total), total);

                if (result.done) {
                    finishNudge($btn, sent, failed);
                    return;
                }

                // Keep the page usable between batches — no full-screen loader
                setTimeout(function () {
                    sendNudgeBatch($btn, nextOffset, sent, failed);
                }, 150);
            },
            error: function () {
                failNudge($btn);
            }
        });
    }

    $("#task-control-nudge-btn").on("click", function () {
        var $btn = $(this);
        if ($btn.prop("disabled") || $btn.data("busy")) {
            return;
        }

        var confirmText = <?php echo json_encode(sprintf(app_lang("task_control_nudge_confirm"), count($overdue_tasks))); ?>;
        if (!window.confirm(confirmText)) {
            return;
        }

        $btn.data("busy", true).prop("disabled", true).addClass("disabled");
        setNudgeProgress(0, <?php echo (int) count($overdue_tasks); ?>);
        sendNudgeBatch($btn, 0, 0, 0);
    });
});
</script>
