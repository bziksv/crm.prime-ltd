<?php if ($view_type == "details") { ?>

    <div id="page-content" class="page-wrapper pb0 clearfix task-view-modal-body task-preview">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="page-title clearfix">
                        <h1><?php echo app_lang("task_info") . " #$model_info->id"; ?></h1>

                        <?php if ($can_edit_tasks) { ?>
                            <div class="title-button-group">
                                <span class="dropdown inline-block">
                                    <button class="btn btn-default dropdown-toggle caret mr0 mb0" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                        <i data-feather='settings' class='icon-16'></i> <?php echo app_lang('actions'); ?>
                                    </button>
                                    <ul class="dropdown-menu float-end" role="menu">
                                        <li role="presentation"><?php echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit_task'), array("title" => app_lang('edit_task'), "data-post-id" => $model_info->id, "data-post-view_type" => "details", "id" => "task-details-edit-btn", "class" => "dropdown-item")); ?></li>
                                        <li role="presentation"><?php echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='copy' class='icon-16'></i> " . app_lang('clone_task'), array("title" => app_lang('clone_task'), "data-post-id" => $model_info->id, "data-post-is_clone" => true, "data-post-view_type" => "details", "class" => "dropdown-item")); ?></li>
                                    </ul>
                                </span>
                            </div>
                        <?php } ?>

                    </div>

                    <div class="card-body">
                        <?php echo view("tasks/task_view_data"); ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

<?php } else if ($view_type == "panel_view") { ?>

    <div class="task-panel-view clearfix general-form task-view-modal-body">
        <div class="task-panel-view-header">
            <div class="task-panel-view-heading">
                <div class="task-panel-view-meta text-muted mb5">
                    <?php echo app_lang("task_info") . " #" . $model_info->id; ?>
                    <?php if (!empty($model_info->project_title)) { ?>
                        · <?php echo esc($model_info->project_title); ?>
                    <?php } ?>
                </div>
                <div class="task-panel-view-title text-break">
                    <?php echo esc($model_info->title); ?>
                </div>
            </div>
            <div class="task-panel-view-toolbar">
                <a href="<?php echo get_uri("tasks/view/" . $model_info->id); ?>" class="btn btn-default btn-sm" title="<?php echo app_lang('task_info'); ?>" target="_blank">
                    <i data-feather="external-link" class="icon-16"></i>
                </a>
                <button type="button" class="btn btn-default btn-sm js-task-panel-close" title="<?php echo app_lang('close'); ?>">
                    <i data-feather="x" class="icon-16"></i>
                </button>
            </div>
        </div>

        <div class="task-panel-view-body">
            <?php echo view("tasks/task_view_data"); ?>
        </div>

        <div class="task-panel-view-footer">
            <?php
            if ($can_edit_tasks) {
                echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='copy' class='icon-16'></i> " . app_lang('clone_task'), array("class" => "btn btn-default btn-sm", "data-post-is_clone" => true, "data-post-id" => $model_info->id, "title" => app_lang('clone_task')));
                echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit_task'), array("class" => "btn btn-default btn-sm", "data-post-id" => $model_info->id, "title" => app_lang('edit_task')));
            }
            ?>
        </div>
    </div>

<?php } else { ?>

    <script type="text/javascript">
        $(document).ready(function () {
            //store existing url to retrieve back on modal close
            if (!window.existingUrl) {
                window.existingUrl = window.location.href;
            }

            //change browser address when opening task details modal
            var browserState = {Url: "<?php echo get_uri("tasks/view/" . $model_info->id); ?>"};
            history.pushState(browserState, "", browserState.Url);

            //restore previous url

            if(!window.modalEventAttached){
                $('#ajaxModal').on('hidden.bs.modal', function (e) {
                    if (window.existingUrl) {
                        var browserState = {Url: window.existingUrl};
                        history.pushState(browserState, "", browserState.Url);
                        window.existingUrl = "";
                    }

                    if(window.reloadKanban){
                        window.reloadKanban = false; //reset
                        $("#reload-kanban-button:visible").trigger("click");
                    }

                });
                window.modalEventAttached = true;
            }

        });
    </script>

    <div class="modal-body clearfix general-form task-view-modal-body">
        <?php echo view("tasks/task_view_data"); ?>
    </div>

    <div class="modal-footer">
        <?php
        if ($can_edit_tasks) {
            echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='copy' class='icon-16'></i> " . app_lang('clone_task'), array("class" => "btn btn-default float-start", "data-post-is_clone" => true, "data-post-id" => $model_info->id, "title" => app_lang('clone_task')));
            echo modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit_task'), array("class" => "btn btn-default", "data-post-id" => $model_info->id, "title" => app_lang('edit_task')));
        }
        ?>
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    </div>

<?php } ?>

<?php
$task_link = anchor(get_uri("tasks/view/$model_info->id"), '<i data-feather="external-link" class="icon-16 task-link-btn"></i>', array("target" => "_blank", "class" => "p15"));
?>

<script type="text/javascript">
    $(document).ready(function () {

        //make the checklist items sortable
        var $selector = $("#checklist-items");
        if ($selector.length && typeof Sortable !== "undefined") {
        Sortable.create($selector[0], {
            animation: 150,
            chosenClass: "sortable-chosen",
            ghostClass: "sortable-ghost",
            onUpdate: function (e) {
                appLoader.show();
                //prepare checklist items indexes
                var data = "";
                $.each($selector.find(".checklist-item-row"), function (index, ele) {
                    if (data) {
                        data += ",";
                    }

                    data += $(ele).attr("data-id") + "-" + parseInt(index + 1);
                });

                //update sort indexes
                $.ajax({
                    url: '<?php echo_uri("tasks/save_checklist_items_sort") ?>',
                    type: "POST",
                    data: {sort_values: data},
                    success: function () {
                        appLoader.hide();
                    }
                });
            }
        });
        }

        //add a clickable link in task title.
        if ($("#ajaxModalTitle").length) {
            $("#ajaxModalTitle").append('<?php echo $task_link ?>');
        }
        //show the items in checklist
        $("#checklist-items").html(<?php echo $checklist_items; ?>);

        function applyChecklistHideCompleted() {
            var $rows = $("#checklist-items .checklist-item-row");
            var $toggle = $(".checklist-hide-completed-toggle");
            var total = $rows.length;

            // No checklist at all — nothing to hide
            $toggle.toggleClass("hide", total === 0);
            if (total === 0) {
                $("#checklist-hidden-count").addClass("hide").text("");
                return;
            }

            var hide = $("#checklist-hide-completed").is(":checked");
            var hiddenCount = 0;
            $rows.each(function () {
                var $row = $(this);
                var done = $row.attr("data-checked") === "1" || $row.find(".checkbox-checked").length > 0;
                if (done) {
                    $row.attr("data-checked", "1");
                    hiddenCount++;
                } else {
                    $row.attr("data-checked", "0");
                }
                $row.toggleClass("hide", hide && done);
            });

            var $badge = $("#checklist-hidden-count");
            if (hide && hiddenCount > 0) {
                $badge.text(hiddenCount).removeClass("hide");
            } else {
                $badge.addClass("hide").text("");
            }
        }

        (function initChecklistHideCompleted() {
            var stored = null;
            try {
                stored = localStorage.getItem("prime_checklist_hide_completed");
            } catch (e) {
            }
            // default: hide completed on large lists
            var hide = stored === null
                ? ($("#checklist-items .checklist-item-row").length >= 10)
                : (stored === "1");
            $("#checklist-hide-completed").prop("checked", hide);
            applyChecklistHideCompleted();
        })();

        $("#checklist-hide-completed").on("change", function () {
            var hide = $(this).is(":checked");
            try {
                localStorage.setItem("prime_checklist_hide_completed", hide ? "1" : "0");
            } catch (e) {
            }
            applyChecklistHideCompleted();
        });

        //show save & cancel button when the checklist-add-item-form is focused
        $("#checklist-add-item").focus(function () {
            $(".checklist-options-panel").removeClass("hide");
            $("#checklist-add-item-error").removeClass("hide");
        });

        $("#checklist-options-panel-close").click(function () {
            $(".checklist-options-panel").addClass("hide");
            $("#checklist-add-item-error").addClass("hide");
            $("#checklist-add-item").val("");

            $("#checklist-add-item").select2("destroy").val("");
            $("#checklist-template-toggle-button").html("<?php echo "<i data-feather='hash' class='icon-16'></i> " . app_lang('select_from_template'); ?>");
            $("#checklist-template-toggle-button").addClass('checklist-template-button');
            feather.replace();

            $(".checklist_button").removeClass("active");
            $("#type-new-item-button").addClass("active");
        });

        //count checklists
        function count_checklists() {
            var checklists = $("#checklist-items .checklist-item-row").length;
            $(".chcklists_count").text(checklists);
            applyChecklistHideCompleted();
        }

        var checklists = $("#checklist-items .checklist-item-row").length;
        $(document).off("click.taskChecklistDelete", "#checklist-items .delete-checklist-item")
            .on("click.taskChecklistDelete", "#checklist-items .delete-checklist-item", function () {
            // fade-out removes the row asynchronously — refresh after a tick
            setTimeout(function () {
                count_checklists();
                checklist_complete = $("#checklist-items .checkbox-checked").length;
                $(".chcklists_status_count").text(checklist_complete);
            }, 50);
        });

        count_checklists();

        $(".copy-checklist-item").click(function () {
            let $temp = $("<input>");

            $("body").append($temp);

            $temp.val($(this).closest(".list-group-item").find(".font-13").text()).select();

            document.execCommand("copy");

            $temp.remove();

            appAlert.success("Скопировано", {duration: 5000});
        });

        var checklist_complete = $(".checklist-items .checkbox-checked").length;
        $(".chcklists_status_count").text(checklist_complete);

        $("#checklist_form").appForm({
            isModal: false,
            onSuccess: function (response) {
                $("#checklist-add-item").val("");
                $("#checklist-add-item").focus();
                $("#checklist-items").append(response.data);

                count_checklists();
                applyChecklistHideCompleted();
                window.reloadKanban = true;
            }
        });

        $('body').off('click.taskChecklistStatus', '[data-act=update-checklist-item-status-checkbox]')
            .on('click.taskChecklistStatus', '[data-act=update-checklist-item-status-checkbox]', function () {
            var $row = $(this).closest(".checklist-item-row");
            var status_checkbox = $(this).find("span");
            status_checkbox.removeClass("checkbox-checked");
            status_checkbox.addClass("inline-loader");

            if ($(this).attr('data-value') == 0) {
                checklist_complete--;
                $(".chcklists_status_count").text(checklist_complete);
            } else {
                checklist_complete++;
                $(".chcklists_status_count").text(checklist_complete);
            }

            $.ajax({
                url: '<?php echo_uri("tasks/save_checklist_item_status") ?>/' + $(this).attr('data-id'),
                type: 'POST',
                dataType: 'json',
                data: {value: $(this).attr('data-value')},
                success: function (response) {
                    if (response.success) {
                        status_checkbox.closest("div").html(response.data);
                        if ($row.length) {
                            $row.attr("data-checked", $row.find(".checkbox-checked").length ? "1" : "0");
                        }
                        applyChecklistHideCompleted();
                        window.reloadKanban = true;
                    }
                }
            });
        });

        //show the sub tasks
        $("#sub-tasks").html(<?php echo $sub_tasks; ?>);

        //show create & cancel button when the add-sub-task-form is focused
        $("#sub-task-title").focus(function () {
            $("#sub-task-options-panel").removeClass("hide");
            $("#sub-task-title-error").removeClass("hide");
        });

        $("#sub-task-options-panel-close").click(function () {
            $("#sub-task-options-panel").addClass("hide");
            $("#sub-task-title-error").addClass("hide");
            $("#sub-task-title").val("");
        });

        $("#sub_task_form").appForm({
            isModal: false,
            onSuccess: function (response) {
                $("#sub-task-title").val("");
                $("#sub-task-title").focus();
                $("#sub-tasks").append(response.task_data);
                window.reloadKanban = true;
            }
        });

        $('body').off('click.taskSubTaskStatus', '[data-act=update-sub-task-status-checkbox]')
            .on('click.taskSubTaskStatus', '[data-act=update-sub-task-status-checkbox]', function () {
            var sub_task_status_checkbox = $(this).find("span");
            sub_task_status_checkbox.removeClass("checkbox-checked");
            sub_task_status_checkbox.addClass("inline-loader");
            $.ajax({
                url: '<?php echo_uri("tasks/save_task_status") ?>/' + $(this).attr('data-id'),
                type: 'POST',
                dataType: 'json',
                data: {value: $(this).attr('data-value'), type: "sub_task"},
                success: function (response) {
                    if (response.success) {
                        sub_task_status_checkbox.closest("div").html(response.data);
                        window.reloadKanban = true;
                    }
                }
            });
        });

<?php if ($view_type == "details") { ?>
            $("#task-details-edit-btn").click(function () {
                window.refreshAfterAddTask = true;
            });
<?php } ?>

        /* Dependency */

        var $dependencyTasksForm = $("#dependency_tasks_form"),
                $dependencyArea = $("#dependency-area"),
                $blockedByArea = $("#blocked-by-area"),
                $blockingArea = $("#blocking-area");

        //add dependency
        $(".add-dependency-btn").click(function () {
            var dependencyType = $(this).attr("data-dependency_type");
            showFormAndArea(dependencyType);
        });

        function showFormAndArea(type) {
            if ((type === "blocked_by" && $blockedByArea.find("form").length) || (type === "blocking" && $blockingArea.find("form").length)) {
                //don't show the same shown form again
                return false;
            }

            var $dependencyTasksFormClone = $dependencyTasksForm.clone();

            //show existing tasks on editing
            $.ajax({
                url: '<?php echo_uri("tasks/get_existing_dependency_tasks") ?>' + "/" + "<?php echo $task_id; ?>",
                type: "POST",
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $dependencyTasksFormClone.append("<input type='hidden' name='dependency_type' value='" + type + "' />");
                        $dependencyTasksFormClone.find("#dependency_task").select2({data: response.tasks_dropdown});
                        $dependencyArea.removeClass("hide");

                        if (type === "blocked_by") {
                            hideFromAndArea("blocking");
                            $blockedByArea.removeClass("hide");
                            $dependencyTasksFormClone.removeClass("hide");
                            $blockedByArea.append($dependencyTasksFormClone);
                        } else {
                            hideFromAndArea("blocked_by");
                            $blockingArea.removeClass("hide");
                            $dependencyTasksFormClone.removeClass("hide");
                            $blockingArea.append($dependencyTasksFormClone);
                        }

                        $dependencyTasksFormClone.appForm({
                            isModal: false,
                            onSuccess: function (result) {
                                if (result.success) {
                                    if (type === "blocked_by") {
                                        $("#blocked-by-tasks").append(result.data);
                                    } else {
                                        $("#blocking-tasks").append(result.data);
                                    }

                                    hideFromAndArea(type);
                                }
                            }
                        });
                    }
                }
            });
        }

        $('body').off('click.taskDependencyClose', '.dependency-tasks-close')
            .on('click.taskDependencyClose', '.dependency-tasks-close', function () {
            hideFromAndArea();
        });

        $('body').off('click.taskDependencyAjax', '#dependency-area [data-act="ajax-request"]')
            .on('click.taskDependencyAjax', '#dependency-area [data-act="ajax-request"]', function () {
            setTimeout(function () {
                hideFromAndArea();
            }, 800);
        });

        //hide form clone and area
        function hideFromAndArea(type) {
            var blockedByTasksLength = $("#blocked-by-tasks").html().length,
                    blockingTasksLength = $("#blocking-tasks").html().length;

            if (type === "blocked_by" || !type) {
                fadeAndRemove($blockedByArea.find("form"));


                if (!blockedByTasksLength) {
                    fadeAndHide($blockedByArea);
                }
            }

            if (type === "blocking" || !type) {
                fadeAndRemove($blockingArea.find("form"));

                if (!blockingTasksLength) {
                    fadeAndHide($blockingArea);
                }
            }

            if (!type && !blockedByTasksLength && !blockingTasksLength) {
                fadeAndHide($dependencyArea);
            }
        }

        function fadeAndRemove($selector) {
            $selector.fadeOut(300, function () {
                $(this).remove();
            });
        }

        function fadeAndHide($selector) {
            $selector.fadeOut(300, function () {
                $(this).css('display', '')
                $(this).addClass("hide");
            });
        }

        $('[data-bs-toggle="tooltip"]').tooltip();

        //change the add checklist input box type
        $("#select-from-template-button").click(function () {
            $(".checklist_button").removeClass("active");
            applySelect2OnChecklistTemplate();
            $(this).addClass("active");
            $("#is_checklist_group").val("0");
        });

        $("#select-from-checklist-group-button").click(function () {
            $(".checklist_button").removeClass("active");
            applySelect2OnChecklistGroup();
            $(this).addClass("active");
            $("#is_checklist_group").val("1");
        });

        $("#type-new-item-button").click(function () {
            $(".checklist_button").removeClass("active");
            $("#checklist-add-item").select2("destroy").val("").focus();
            $("#is_checklist_group").val("0");
            $(this).addClass("active");
        });

        $(document).off("pinCommentUpdated.taskView").on("pinCommentUpdated.taskView", function () {
            updatePinCommentAbove();
        });

        $(document).trigger("pinCommentUpdated");
    });

    function updatePinCommentAbove() {

        let pinCommentPreview = $(".pin-comment-preview");

        if (!pinCommentPreview.length) {
            return;
        }

        let container = $("#comment-pin-container").html("");

        container.append('<div class="box-title"><span>Закрепленные комментарии</span></div>');

        pinCommentPreview.each(function(i, el) {
            let self = $(el);
            let id = self.attr('id');

            if (!self.children().length) {
                return true;
            }

            let origin = $('#comment-' + id);

            origin.find(".like-button").remove();

            let comment = $("<div />", { class: origin.attr('class') })
                .html(origin.html());

            comment.find(".dropdown")
                .removeClass(['dropdown', 'comment-dropdown'])
                .html($("<a />", { href: '#comment-' + id }).html(self.find(".pin").html() + self.find(".float-start").html()));

            container.append(comment);
        });
    }

    function applySelect2OnChecklistTemplate() {
        $("#checklist-add-item").select2({
            showSearchBox: true,
            ajax: {
                url: "<?php echo get_uri("tasks/get_checklist_template_suggestion"); ?>",
                type: 'POST',
                dataType: 'json',
                quietMillis: 250,
                data: function (term, page) {
                    return {
                        q: term, // search term
                        task_id: "<?php echo $model_info->id; ?>"
                    };
                },
                results: function (data, page) {
                    return {results: data};
                    $("#checklist-add-item").val("");
                }
            }
        });
    }

    function applySelect2OnChecklistGroup() {
        $("#checklist-add-item").select2({
            showSearchBox: true,
            ajax: {
                url: "<?php echo get_uri("tasks/get_checklist_group_suggestion"); ?>",
                type: 'POST',
                dataType: 'json',
                quietMillis: 250,
                data: function (term, page) {
                    return {
                        q: term, // search term
                        task_id: "<?php echo $model_info->id; ?>"
                    };
                },
                results: function (data, page) {
                    return {results: data};
                    $("#checklist-add-item").val("");
                }
            }
        });
    }
</script>

<?php
if ($can_edit_tasks) {
    echo view("tasks/update_task_info_script");
}
?>
