<div id="page-content" class="page-wrapper clearfix notifications-list-page">
    <div class="notifications-page-header">
        <div class="notifications-page-heading">
            <h4 class="mb0">Хроника</h4>
            <?php if (!empty($notifications_filters)): ?>
                <div class="notifications-saved-filters">
                    <?php foreach ($notifications_filters as $index => $notifications_filter): ?>
                        <div class="btn-group" role="group">
                            <a href="<?php echo get_uri("notifications" . '?' . http_build_query($notifications_filter["params"])); ?>" class="btn btn-default btn-sm round" title="<?php echo $notifications_filter["title"]; ?>"><?php echo $notifications_filter["title"]; ?></a>
                            <a href="<?php echo get_uri("notifications/delete_user_filter" . '?index=' . $index); ?>" class="btn btn-default btn-sm round" title=""><i data-feather='x' class='icon-14'></i></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="notifications-page-actions">
            <button type="button" class="btn btn-default btn-sm" id="notifications-toggle-filters" title="Фильтры">
                <i data-feather="filter" class="icon-16"></i>
                Фильтры
            </button>
            <?php echo js_anchor(
                "<i data-feather='check-circle' class='icon-16'></i> " . app_lang('mark_all_as_read'),
                array(
                    "class" => "btn btn-default btn-sm js-mark-all-notifications-read",
                    "title" => app_lang('mark_all_as_read'),
                    "data-action-url" => get_uri("notifications/set_notification_status_as_read"),
                )
            ); ?>
        </div>
    </div>

    <div id="notifications-filters-panel" class="notifications-filters-panel is-open">
        <?php echo form_open(get_uri("notifications"), array("role" => "form", "method" => "get", "class" => "notifications-filters-form")); ?>
        <div class="notifications-filters-bar">
            <div class="notifications-filter-field is-event">
                <?php
                echo form_input(array(
                    "id" => "notification_event_filter",
                    "name" => "notification_event_filter",
                    "value" => request()->getGet("notification_event_filter"),
                    "class" => "select2-filter-input",
                    "placeholder" => app_lang('notification_filter')
                ));
                ?>
            </div>
            <div class="notifications-filter-field">
                <?php
                echo form_input(array(
                    "id" => "notification_is_read_filter",
                    "name" => "notification_is_read_filter",
                    "value" => request()->getGet("notification_is_read_filter"),
                    "class" => "select2-filter-input",
                    "placeholder" => app_lang('status')
                ));
                ?>
            </div>
            <div class="notifications-filter-field">
                <?php
                echo form_input(array(
                    "id" => "notification_team_members_filter",
                    "name" => "notification_team_members_filter",
                    "value" => request()->getGet("notification_team_members_filter"),
                    "class" => "select2-filter-input",
                    "placeholder" => app_lang('team_members')
                ));
                ?>
            </div>
            <div class="notifications-filter-field">
                <?php
                echo form_input(array(
                    "id" => "notification_projects_filter",
                    "name" => "notification_projects_filter",
                    "value" => request()->getGet("notification_projects_filter"),
                    "class" => "select2-filter-input",
                    "placeholder" => app_lang('projects')
                ));
                ?>
            </div>
            <div class="notifications-filter-field is-narrow">
                <?php
                echo form_input(array(
                    "id" => "notification_grouped_filter",
                    "name" => "notification_grouped_filter",
                    "value" => request()->getGet("notification_grouped_filter"),
                    "class" => "select2-filter-input",
                    "placeholder" => app_lang('grouped')
                ));
                ?>
            </div>
            <div class="notifications-filter-field is-narrow">
                <?php
                echo form_input(array(
                    "id" => "notification_order_by_filter",
                    "name" => "notification_order_by_filter",
                    "value" => request()->getGet("notification_order_by_filter"),
                    "class" => "select2-filter-input",
                    "placeholder" => app_lang('order_by')
                ));
                ?>
            </div>
            <div class="notifications-filters-actions">
                <button type="submit" class="btn btn-primary btn-sm"><?php echo app_lang('apply'); ?></button>
                <?php if ($params = request()->getGet()): ?>
                    <a href="<?php echo get_uri('notifications'); ?>" class="btn btn-default btn-sm">Сбросить</a>
                    <?php echo modal_anchor(get_uri("notifications/save_filter_modal_form" . '?' . http_build_query($params)), "<i data-feather='tag' class='icon-14'></i> " . app_lang('save'), array("class" => "btn btn-default btn-sm")); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>

    <div id="notifications-page-content" class="notifications-page-content">
        <aside class="notifications-inbox-sidebar">
            <div class="notifications-inbox-toolbar">
                <div class="notifications-inbox-status-tabs">
                    <button type="button" data-tab="unread" class="is-active">Непрочитанные</button>
                    <button type="button" data-tab="all">Все</button>
                </div>
            </div>
            <div id="notifications-inbox-list" class="notifications-inbox-list"></div>
            <div id="notifications-inbox-load-more" class="notifications-inbox-load-more"></div>
        </aside>

        <section id="notifications-detail-pane" class="notifications-detail-pane">
            <div id="notification-detail-body" class="notification-detail-body">
                <div class="notification-panel-placeholder">Выберите событие из списка</div>
            </div>
        </section>
    </div>
</div>

<link rel="stylesheet" href="<?php echo base_url('assets/css/notifications-inbox.css?v=20260829r'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/tickets-panel.css?v=20260828r'); ?>">
<script type="text/javascript">
    window.notificationInboxListUrl = "<?php echo get_uri('notifications/inbox_list_data'); ?>";
    window.notificationPanelUrl = "<?php echo get_uri('notifications/view_panel'); ?>";
    window.notificationInboxFilters = <?php echo json_encode($inbox_filters ?: new stdClass()); ?>;
</script>
<script src="<?php echo base_url('assets/js/notifications-inbox.js?v=20260830a'); ?>"></script>

<script>
    $(document).ready(function () {
        var select2Opts = { width: "100%", allowClear: true };
        $('#notification_event_filter').select2($.extend({}, select2Opts, {
            multiple: true,
            data: <?php echo json_encode($event_dropdown); ?>,
            placeholder: <?php echo json_encode(app_lang('notification_filter')); ?>
        }));
        $('#notification_is_read_filter').select2($.extend({}, select2Opts, { data: <?php echo json_encode($is_read_dropdown); ?> }));
        $('#notification_grouped_filter').select2($.extend({}, select2Opts, { data: <?php echo json_encode($grouped_dropdown); ?> }));
        $('#notification_projects_filter').select2($.extend({}, select2Opts, { data: <?php echo json_encode($projects_dropdown); ?> }));
        $('#notification_team_members_filter').select2($.extend({}, select2Opts, { data: <?php echo json_encode($team_members_dropdown); ?> }));
        $('#notification_order_by_filter').select2($.extend({}, select2Opts, { data: <?php echo json_encode($order_by_dropdown); ?> }));

        $('#notifications-toggle-filters').on('click', function () {
            $('#notifications-filters-panel').toggleClass('is-open');
        });
    });
</script>
