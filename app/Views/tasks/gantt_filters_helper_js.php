<?php
$statuses = array();
foreach ($task_statuses as $status) {
    $is_selected = ($status->key_name != "done");
    $statuses[] = array(
        "text" => ($status->key_name ? app_lang($status->key_name) : $status->title),
        "value" => $status->id,
        "isChecked" => $is_selected
    );
}
?>
<script type="text/javascript">
    $(document).ready(function () {
        $("#gantt-filters").appFilters({
            source: "",
            targetSelector: "",
            smartFilterIdentity: "all_tasks_gantt",
            search: {name: "search", show: false},
            filterDropdown: [
                {name: "quick_filter", class: "w200", showHtml: true, options: <?php echo view("tasks/quick_filters_dropdown"); ?>},
                {name: "context", class: "w200", options: <?php echo $contexts_dropdown; ?>, onChangeCallback: function (value, filterParams) {
                        var $tableWrapper = $("#js-gantt-filter-container");
                        if (!(value == "" || value == "project")) {
                            var $milestoneSelector = $tableWrapper.find("select[name=milestone_id]");
                            var $milestoneFirstOption = $milestoneSelector.find("option:first");
                            $milestoneSelector.html("<option value='" + $milestoneFirstOption.val() + "'>" + $milestoneFirstOption.html() + "</option>");
                            $milestoneSelector.select2("val", $milestoneFirstOption.val());

                            var $projectSelector = $tableWrapper.find("select[name=project_id]");
                            $projectSelector.select2("val", "");

                            filterParams.project_id = "";
                            filterParams.milestone_id = "";
                            $tableWrapper.find("[name='project_id']").closest(".filter-item-box").addClass("hide");
                            $tableWrapper.find("[name='milestone_id']").closest(".filter-item-box").addClass("hide");
                        } else {
                            $tableWrapper.find("[name='project_id']").closest(".filter-item-box").removeClass("hide");
                            $tableWrapper.find("[name='milestone_id']").closest(".filter-item-box").removeClass("hide");
                        }
                    }
                },
                {name: "project_id", class: "w200", options: <?php echo $projects_dropdown; ?>, dependent: ["milestone_id"]},
                {name: "milestone_id", class: "w200", options: [{id: "", text: "- <?php echo app_lang('milestone'); ?> -"}], dependency: ["project_id"], dataSource: '<?php echo_uri("tasks/get_milestones_for_filter") ?>'}
                , <?php echo $custom_field_filters; ?>
            ],
            multiSelect: [
                {
                    class: "w200",
                    name: "responsible_user_id",
                    text: "<?php echo app_lang('team_responsible'); ?>",
                    options: <?php echo $team_responsible_dropdown; ?>
                },
                {
                    class: "w200",
                    name: "executors_user_id",
                    text: "<?php echo app_lang('executors'); ?>",
                    options: <?php echo $team_members_dropdown; ?>
                },
                {
                    class: "w200",
                    name: "member_user_id",
                    text: "<?php echo app_lang('team_member'); ?>",
                    options: <?php echo $team_members_dropdown; ?>
                },
                {
                    class: "w200",
                    name: "auditors_user_id",
                    text: "<?php echo app_lang('auditors'); ?>",
                    options: <?php echo $team_members_dropdown; ?>
                },
                {
                    class: "w200",
                    name: "priority_id",
                    text: "<?php echo app_lang('priority'); ?>",
                    options: <?php echo $priorities_dropdown; ?>
                },
                {
                    class: "w200",
                    name: "label_id",
                    text: "<?php echo app_lang('label'); ?>",
                    options: <?php echo $labels_dropdown; ?>
                },
                {
                    class: "w200",
                    name: "private_label_id",
                    text: "<?php echo app_lang('personal_labels'); ?>",
                    options: <?php echo $private_labels_dropdown; ?>
                },
                {
                    class: "w200",
                    name: "status_id",
                    text: "<?php echo app_lang('status'); ?>",
                    options: <?php echo json_encode($statuses); ?>
                }
            ],
            singleDatepicker: [{name: "deadline", class: "w200", defaultText: "<?php echo app_lang('deadline') ?>",
                    options: [
                        {value: "expired", text: "<?php echo app_lang('expired') ?>"},
                        {value: moment().format("YYYY-MM-DD"), text: "<?php echo app_lang('today') ?>"},
                        {value: moment().add(1, 'days').format("YYYY-MM-DD"), text: "<?php echo app_lang('tomorrow') ?>"},
                        {value: moment().add(7, 'days').format("YYYY-MM-DD"), text: "<?php echo sprintf(app_lang('in_number_of_days'), 7); ?>"},
                        {value: moment().add(15, 'days').format("YYYY-MM-DD"), text: "<?php echo sprintf(app_lang('in_number_of_days'), 15); ?>"}
                    ]}],
            beforeRelaodCallback: function () {
                appLoader.hide();
            },
            afterRelaodCallback: function () {
                appLoader.hide();
                if (typeof window.reloadGantt === "function") {
                    window.reloadGantt(true);
                }
                if (typeof window.syncResponsibleExecutorsModeToggle === "function") {
                    window.syncResponsibleExecutorsModeToggle("gantt-filters");
                }
            },
            onInitComplete: function () {
                appLoader.hide();
                if (typeof window.initResponsibleExecutorsModeToggle === "function") {
                    window.initResponsibleExecutorsModeToggle({
                        instanceId: "gantt-filters",
                        wrapperSelector: "#js-gantt-filter-container, #gantt-filters",
                        reloadType: "filters"
                    });
                }
                if (typeof window.reloadGantt === "function") {
                    window.reloadGantt(false);
                }
            }
        });

        setTimeout(function () {
            if (typeof window.initResponsibleExecutorsModeToggle === "function") {
                window.initResponsibleExecutorsModeToggle({
                    instanceId: "gantt-filters",
                    wrapperSelector: "#js-gantt-filter-container, #gantt-filters",
                    reloadType: "filters"
                });
            }
        }, 0);
    });
</script>
<?php echo view("tasks/quick_filters_helper_js"); ?>
