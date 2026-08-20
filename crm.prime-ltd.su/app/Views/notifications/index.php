<div id="page-content" class="page-wrapper clearfix">
    <div class="mb-4">
        <?php foreach ($notifications_filters as $index => $notifications_filter): ?>
            <div class="btn-group" role="group">
                <a href="<?php echo get_uri("notifications" .'?'. http_build_query($notifications_filter["params"])); ?>" class="btn btn-default round" title="<?php echo $notifications_filter["title"]; ?>"><?php echo $notifications_filter["title"]; ?></a>
                <a href="<?php echo get_uri("notifications/delete_user_filter" .'?index='. $index); ?>" class="btn btn-default round" title=""><i data-feather='delete' class='icon-16'></i></a>
            </div>
        <? endforeach; ?>
    </div>

    <div class="card">

        <div class="page-title clearfix">
            <h4> <?php echo app_lang('notifications'); ?></h4>
        </div>

        <?php echo form_open(get_uri("notifications"), array("role" => "form", "method" => "get")); ?>
        <div class="form-group mt-4">
            <div class="row">
                <div class="col-3 ps-4">
                    <?php
                    echo form_input(array(
                        "id" => "notification_event_filter",
                        "name" => "notification_event_filter",
                        "value" => request()->getGet("notification_event_filter"),
                        "class" => "form-control",
                        "placeholder" => app_lang('notification_filter')
                    ));
                    ?>
                </div>
                <div class="col-1">
                    <?php
                    echo form_input(array(
                        "id" => "notification_is_read_filter",
                        "name" => "notification_is_read_filter",
                        "value" => request()->getGet("notification_is_read_filter"),
                        "class" => "form-control",
                        "placeholder" => app_lang('status')
                    ));
                    ?>
                </div>
                <div class="col-2">
                    <?php
                    echo form_input(array(
                        "id" => "notification_team_members_filter",
                        "name" => "notification_team_members_filter",
                        "value" => request()->getGet("notification_team_members_filter"),
                        "class" => "form-control",
                        "placeholder" => app_lang('team_members')
                    ));
                    ?>
                </div>
                <div class="col-2">
                    <?php
                    echo form_input(array(
                        "id" => "notification_projects_filter",
                        "name" => "notification_projects_filter",
                        "value" => request()->getGet("notification_projects_filter"),
                        "class" => "form-control",
                        "placeholder" => app_lang('projects')
                    ));
                    ?>
                </div>
                <div class="col-1">
                    <?php
                    echo form_input(array(
                            "id" => "notification_grouped_filter",
                            "name" => "notification_grouped_filter",
                            "value" => request()->getGet("notification_grouped_filter"),
                            "class" => "form-control",
                            "placeholder" => app_lang('grouped')
                    ));
                    ?>
                </div>
                <div class="col-1">
                    <?php
                    echo form_input(array(
                            "id" => "notification_order_by_filter",
                            "name" => "notification_order_by_filter",
                            "value" => request()->getGet("notification_order_by_filter"),
                            "class" => "form-control",
                            "placeholder" => app_lang('order_by')
                    ));
                    ?>
                </div>
                <div class="col-2">
                    <button type="submit" class="btn btn-default"><? echo app_lang('apply'); ?></button>
                    <? if($params = request()->getGet()): ?>
                        <?php echo modal_anchor(get_uri("notifications/save_filter_modal_form" .'?'. http_build_query($params)), "<i data-feather='tag' class='icon-16'></i> " . app_lang('save'), array("class" => "btn btn-outline-light")); ?>
                    <? endif; ?>
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>

        <div>
            <?php
            $view_data["notifications"] = $notifications;

            echo view("notifications/list_data", $view_data);
            ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#notification_event_filter').select2({multiple: true, data: <?php echo json_encode($event_dropdown); ?>});
        $('#notification_is_read_filter').select2({data: <?php echo json_encode($is_read_dropdown); ?>});
        $('#notification_grouped_filter').select2({data: <?php echo json_encode($grouped_dropdown); ?>});
        $('#notification_projects_filter').select2({data: <?php echo json_encode($projects_dropdown); ?>});
        $('#notification_team_members_filter').select2({data: <?php echo json_encode($team_members_dropdown); ?>});
        $('#notification_order_by_filter').select2({data: <?php echo json_encode($order_by_dropdown); ?>});
    });
</script>
