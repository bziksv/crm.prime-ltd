<?php
$points_dropdown_for_update = array();
foreach ($points_dropdown as $key => $value) {
    $points_dropdown_for_update[] = array("id" => $key, "text" => $value);
}
?>


<script type="text/javascript">
    $(document).ready(function () {
        let body = $("body");

        // Re-bind on each modal open so handlers don't stack (body.on without off = hell after N tasks)
        body.off('click.updateTaskInfo', '[data-act=update-task-info]').on('click.updateTaskInfo', '[data-act=update-task-info]', function (e) {
            var $instance = $(this),
                    type = $(this).attr('data-act-type'),
                    source = "",
                    select2Option = {},
                    showbuttons = false,
                    placement = "bottom",
                    editableType = "select2",
                    datepicker = {},
                    assigned_to = $('[data-act-type=assigned_to]').attr('data-value');

            if (type === "status_id") {
                source = <?php echo json_encode($statuses_dropdown); ?>;
                select2Option = {data: source};
            } else if (type === "milestone_id") {
                source = <?php echo json_encode($milestones_dropdown); ?>;
                select2Option = {data: source};
            } else if (type === "assigned_to") {
                source = <?php echo json_encode($assign_to_dropdown); ?>;
                select2Option = {data: source};
            } else if (type === "points") {
                source = <?php echo json_encode($points_dropdown_for_update); ?>;
                select2Option = {data: source};
            } else if (type === "collaborators" || type === "executors") {
                e.stopPropagation();
                e.preventDefault();

                showbuttons = true;
                source = <?php echo json_encode($collaborators_dropdown); ?>;
                select2Option = {data: source, multiple: true};
            } else if (type === "labels") {
                e.stopPropagation();
                e.preventDefault();

                source = <?php echo json_encode($label_suggestions); ?>;
                showbuttons = true;
                select2Option = {data: source, multiple: true};
                placement = "bottom";
            } else if (type === "private_labels") {
                e.stopPropagation();
                e.preventDefault();

                source = <?php echo json_encode($private_label_suggestions); ?>;
                showbuttons = true;
                select2Option = {data: source, multiple: true};
                placement = "bottom";
            } else if (type === "start_date" || type === "deadline") {
                editableType = "date";

                if (type === "deadline") {
                    datepicker["endDate"] = "<?php echo $project_deadline; ?>";

                    //don't show dates before start date
                    <?php if (is_date_exists($model_info->start_date)) { ?>
                        datepicker["startDate"] = "<?php echo format_to_date($model_info->start_date); ?>";
                    <?php } ?>

                    <?php if ($task_deadline_datepicker_view == "extended") { ?>
                        // Render day + badge; schedule ONE batch fetch after the calendar paints
                        // (do not AJAX inside beforeShowDay — that was ~42 requests and froze php -S)
                        datepicker["beforeShowDay"] = function (date) {
                            let day = date.getDate();
                            let month = date.getMonth() + 1;
                            let deadline = date.getFullYear() + '-' + month + '-' + day;

                            if (window._deadlineBadgesTimer) {
                                clearTimeout(window._deadlineBadgesTimer);
                            }
                            window._deadlineBadgesTimer = setTimeout(function () {
                                if (typeof window.fillDeadlineBadges === "function") {
                                    window.fillDeadlineBadges(assigned_to);
                                }
                            }, 30);

                            return {
                                content: '<div data-deadline="'+ deadline +'">'+ day +'</div><div class="badge rounded-pill text-bg-light font-monospace mt-0"></div>'
                            };
                        };
                    <?php } ?>

                }
            } else if (type === "priority_id") {
                source = <?php echo json_encode($priorities_dropdown); ?>;
                select2Option = {data: source};
            } else if (type === "start_time" || type === "end_time") {
                e.stopPropagation();
                e.preventDefault();

                showbuttons = true;
                placement = "bottom";
                editableType = "time";
            }

            $(this).appModifier({
                actionType: editableType,
                value: $(this).attr('data-value'),
                actionUrl: '<?php echo_uri("tasks/update_task_info") ?>/' + $(this).attr('data-id') + '/' + $(this).attr('data-act-type'),
                showbuttons: showbuttons,
                datepicker: datepicker,
                select2Option: select2Option,
                placement: placement,
                onSuccess: function (response, newValue) {
                    if (response.success) {
                        if (type === "assigned_to" && response.assigned_to_avatar) {
                            $("#task-assigned-to-avatar").attr("src", response.assigned_to_avatar);

                            if (response.assigned_to_id === "0") {
                                setTimeout(function () {
                                    $instance.html("<span class='text-off'><?php echo app_lang("add") . " " . app_lang("assignee"); ?><span>");
                                }, 50);
                            }
                        }

                        if (type === "status_id" && response.status_color) {
                            $instance.closest("span").css("background-color", response.status_color);
                        }

                        if (type === "milestone_id" && response.milestone_id === "0") {
                            setTimeout(function () {
                                $instance.html("<span class='text-off'><?php echo app_lang("add") . " " . app_lang("milestone"); ?><span>");
                            }, 50);
                        }

                        if (type === "points" && response.points) {
                            setTimeout(function () {
                                $instance.html(response.points);
                            }, 50);
                        }

                        if (type === "labels" && response.labels) {
                            setTimeout(function () {
                                $instance.html(response.labels);
                            }, 50);
                        }

                        if (type === "private_labels" && response.private_labels) {
                            setTimeout(function () {
                                $instance.html(response.private_labels);
                            }, 50);
                        }

                        if (type === "collaborators" && response.collaborators) {
                            setTimeout(function () {
                                $instance.html(response.collaborators);
                            }, 50);
                        }

                        if (type === "executors" && response.executors) {
                            setTimeout(function () {
                                $instance.html(response.executors);
                            }, 50);
                        }

                        if ((type === "start_date" || type === "deadline") && response.date) {
                            setTimeout(function () {
                                $instance.html(response.date);
                                $instance.append(response.time);

                                if (type === "deadline") {
                                    $(".task-deadline-milestone-tooltip").remove();
                                }
                            }, 50);
                        }

                        if (type === "priority_id" && response.priority_pill) {
                            setTimeout(function () {
                                $instance.prepend(response.priority_pill);
                                feather.replace();
                            }, 50);
                        }

                        if ((type === "start_time" || type === "end_time") && response.time) {
                            setTimeout(function () {
                                $instance.html(response.time);
                            }, 50);
                        }

                        $("#task-table").appTable({newData: response.data, dataId: response.id});

                        appLoader.hide();

                        window.reloadKanban = true;

                        //reload gantt
                        if (typeof window.reloadGantt === "function") {
                            window.reloadGantt(true);
                        }
                    }
                }
            });

            if (type === "deadline") {

                $(".app-popover-body").children(".popover-tempId").removeAttr("style");

                <?php if ($task_deadline_datepicker_view == "extended") { ?>
                    // Backup fill after calendar is in the DOM (beforeShowDay runs before tbody insert)
                    setTimeout(function () {
                        if (typeof window.fillDeadlineBadges === "function") {
                            window.fillDeadlineBadges(assigned_to);
                        }
                    }, 50);
                <?php } ?>

                <?php if ($task_deadline_datepicker_view == "simplified") { ?>
                    let timeout = null;
                    let popover = $(".app-popover");
                    let datepickerDaysClass = ".datepicker-days .day";

                    popover.off("mouseenter.updateTaskDeadline", datepickerDaysClass)
                        .off("mouseleave.updateTaskDeadline", datepickerDaysClass)
                        .on("mouseenter.updateTaskDeadline", datepickerDaysClass, function () {
                        let el = $(this);
                        let date = new Date(el.data("date"));

                        let year = date.getFullYear();
                        let month = date.getMonth() + 1;
                        let day = date.getDate();

                        timeout = setTimeout(() => {
                            $.ajax({
                                url: '<?php echo_uri("tasks/get_count_tasks") ?>',
                                type: "POST",
                                data: {
                                    deadline: `${year}-${month}-${day}`,
                                    user_id: assigned_to,
                                },
                                success: (response) => {
                                    const tooltip = new bootstrap.Tooltip(el, {
                                        title: 'Кол-во ' + response,
                                        trigger: 'manual'
                                    });

                                    tooltip.show();
                                }
                            });
                        }, 500);
                    })
                    .on("mouseleave.updateTaskDeadline", datepickerDaysClass, function () {
                        clearTimeout(timeout);
                        $(".tooltip").remove();
                    });
                <?php } ?>
            }

            return false;
        });

        <?php if ($task_deadline_datepicker_view == "extended") { ?>
        // Shared badge filler for extended deadline calendar (one request per visible month)
        window.fillDeadlineBadges = function (userId) {
            var assignedUserId = userId || $('[data-act-type=assigned_to]').attr('data-value') || (window.AppHelper && AppHelper.userId);
            var $days = $(".app-popover [data-deadline], .datepicker-dropdown [data-deadline], .datepicker-inline [data-deadline]");
            if (!$days.length || !assignedUserId || assignedUserId === "0") {
                return;
            }

            function padDateParts(deadlineAttr) {
                var parts = String(deadlineAttr).split('-');
                if (parts.length !== 3) {
                    return deadlineAttr;
                }
                return parts[0] + '-' + ('0' + parts[1]).slice(-2) + '-' + ('0' + parts[2]).slice(-2);
            }

            var dates = [];
            $days.each(function () {
                dates.push(padDateParts($(this).attr("data-deadline")));
            });
            dates.sort();

            var rangeKey = assignedUserId + ':' + dates[0] + ':' + dates[dates.length - 1];
            if (window._deadlineBadgesRangeKey === rangeKey && window._deadlineCountXhr && window._deadlineCountXhr.readyState !== 4) {
                return; // same month already loading
            }
            window._deadlineBadgesRangeKey = rangeKey;

            if (window._deadlineCountXhr && window._deadlineCountXhr.readyState !== 4) {
                window._deadlineCountXhr.abort();
            }

            window._deadlineCountXhr = $.ajax({
                url: '<?php echo_uri("tasks/get_count_tasks") ?>',
                type: "POST",
                dataType: "json",
                data: {
                    user_id: assignedUserId,
                    from_date: dates[0],
                    to_date: dates[dates.length - 1]
                },
                success: function (response) {
                    // Accept object map; empty PHP array becomes []
                    if (!response || typeof response !== "object" || Array.isArray(response)) {
                        response = {};
                    }
                    $(".app-popover [data-deadline], .datepicker-dropdown [data-deadline], .datepicker-inline [data-deadline]").each(function () {
                        var key = padDateParts($(this).attr("data-deadline"));
                        var count = response[key];
                        $(this).next(".badge").text(typeof count !== "undefined" ? count : 0);
                    });
                }
            });
        };
        <?php } ?>

        // Drop in-flight deadline count requests when the task modal closes
        $('#ajaxModal').off('hidden.bs.modal.updateTaskDeadline').on('hidden.bs.modal.updateTaskDeadline', function () {
            if (window._deadlineCountXhr && window._deadlineCountXhr.readyState !== 4) {
                window._deadlineCountXhr.abort();
            }
            window._deadlineBadgesRangeKey = null;
        });
    });
</script>
