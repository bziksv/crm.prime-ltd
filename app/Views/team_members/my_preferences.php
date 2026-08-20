<div class="tab-content">
    <?php
    $user_id = $login_user->id;
    echo form_open(get_uri("team_members/save_my_preferences/"), array("id" => "my-preferences-form", "class" => "general-form dashed-row white", "role" => "form"));
    ?>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <style>
        .search-match {
            background-color: #fff3cd !important;
        }

        .search-match td:first-child {
            font-weight: 600;
        }

        #projects-notifications-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8f9fa;
        }

        #projects-notifications-table thead tr:first-child th {
            z-index: 6;
        }
    </style>
    <div class="card">
        <div class=" card-header">
            <h4> <?php echo app_lang('my_preferences'); ?></h4>
        </div>
        <div class="card-body">

            <div class="form-group">
                <div class="row">
                    <label for="task_deadline_datepicker_view" class=" col-md-2">Вид календаря крайний срок на странице задачи</label>
                    <div class=" col-md-10">
                        <?php
                        echo form_dropdown(
                            "task_deadline_datepicker_view", array(
                            "" => "По умолчанию",
                            "simplified" => "Упрощенный",
                            "extended" => "Расширенный",
                        ), get_setting('user_' . $user_id . '_task_deadline_datepicker_view'), "class='select2 mini'"
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="notification_sound_volume" class=" col-md-2"><?php echo app_lang('notification_sound_volume'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo form_dropdown(
                                "notification_sound_volume", array(
                            "0" => "-",
                            "1" => "|",
                            "2" => "||",
                            "3" => "|||",
                            "4" => "||||",
                            "5" => "|||||",
                            "6" => "||||||",
                            "7" => "|||||||",
                            "8" => "||||||||",
                            "9" => "|||||||||",
                                ), get_setting('user_' . $user_id . '_notification_sound_volume'), "class='select2 mini'"
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="enable_web_notification" class=" col-md-2"><?php echo app_lang('enable_web_notification'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo form_dropdown(
                                "enable_web_notification", array(
                            "1" => app_lang("yes"),
                            "0" => app_lang("no")
                                ), $user_info->enable_web_notification, "class='select2 mini' id='enable-web-notification'"
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group <?php echo get_setting("enable_push_notification") && $user_info->enable_web_notification ? '' : 'hide'; ?>" id="disable-push-notification-area">
                <div class="row">
                    <label for="disable_push_notification" class="col-md-2"><?php echo app_lang('disable_push_notification'); ?></label>
                    <div class="col-md-10 mt5">
                        <?php
                        $push_notification = get_setting('user_' . $user_id . '_disable_push_notification');
                        $push_notification = $push_notification ? $push_notification : "0";

                        echo form_dropdown(
                                "disable_push_notification", array(
                            "1" => app_lang("yes"),
                            "0" => app_lang("no")
                                ), $push_notification, "class='select2 mini' id='disable_push_notification'"
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="enable_email_notification" class=" col-md-2"><?php echo app_lang('enable_email_notification'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo form_dropdown(
                                "enable_email_notification", array(
                            "1" => app_lang("yes"),
                            "0" => app_lang("no")
                                ), $user_info->enable_email_notification, "class='select2 mini'"
                        );
                        ?>
                    </div>
                </div>
            </div>
            <?php if (count($language_dropdown) && !get_setting("disable_language_selector_for_team_members")) { ?>
                <div class="form-group">
                    <div class="row">
                        <label for="personal_language" class=" col-md-2"><?php echo app_lang('language'); ?></label>
                        <div class="col-md-10">
                            <?php
                            echo form_dropdown(
                                    "personal_language", $language_dropdown, $login_user->language ? $login_user->language : get_setting("language"), "class='select2 mini'"
                            );
                            ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="form-group">
                <div class="row">
                    <label for="hidden_topbar_menus" class=" col-md-2"><?php echo app_lang('hide_menus_from_topbar'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo form_input(array(
                            "id" => "hidden_topbar_menus",
                            "name" => "hidden_topbar_menus",
                            "value" => get_setting('user_' . $user_id . '_hidden_topbar_menus'),
                            "class" => "form-control",
                            "placeholder" => app_lang('hidden_topbar_menus')
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="disable_keyboard_shortcuts" class=" col-md-2"><?php echo app_lang('disable_keyboard_shortcuts'); ?></label>
                    <div class=" col-md-4">
                        <?php
                        $disable_keyboard_shortcuts = get_setting('user_' . $user_id . '_disable_keyboard_shortcuts');
                        $disable_keyboard_shortcuts = $disable_keyboard_shortcuts ? $disable_keyboard_shortcuts : "0";

                        echo form_dropdown(
                                "disable_keyboard_shortcuts", array(
                            "1" => app_lang("yes"),
                            "0" => app_lang("no")
                                ), $disable_keyboard_shortcuts, "class='select2 mini'"
                        );

                        echo modal_anchor(get_uri("team_members/keyboard_shortcut_modal_form"), "<i data-feather='info' class='icon-16'></i>", array("class" => "btn btn-default keyboard-shortcut-info-icon ml10 float-end", "title" => app_lang('keyboard_shortcuts_info'), "data-post-user_id" => $login_user->id));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="recently_meaning" class=" col-md-2"><?php echo app_lang('recently_meaning'); ?></label>
                    <div class=" col-md-3 mt5">
                        <?php
                        $recently_meaning = get_setting("user_" . $login_user->id . "_recently_meaning");
                        echo form_dropdown("recently_meaning", $recently_meaning_dropdown, $recently_meaning ? $recently_meaning : "1_days", "class='select2 mini'");
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="reminder_snooze_length" class=" col-md-2"><?php echo app_lang('snooze_length'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo form_dropdown(
                                "reminder_snooze_length", array(
                            "5" => "5 " . app_lang("minutes"),
                            "10" => "10 " . app_lang("minutes"),
                            "15" => "15 " . app_lang("minutes"),
                            "20" => "20 " . app_lang("minutes"),
                            "30" => "30 " . app_lang("minutes"),
                                ), get_setting('user_' . $user_id . '_reminder_snooze_length'), "class='select2 mini'"
                        );
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="prime_webhook_url">Synology вебхук</label>
                <input type="text" 
                    class="form-control"
                    style="background-color: #f6f8f9 !important"
                    id="prime_webhook_url" 
                    name="prime_webhook_url" 
                    value="<?= esc($prime_webhook_url); ?>" >
            </div>

            <div class="form-group">
                <label for="telegram_chat_id">Телеграм chat id</label>
                <input type="text" 
                    class="form-control"
                    style="background-color: #f6f8f9 !important"
                    id="telegram_chat_id" 
                    name="telegram_chat_id" 
                    value="<?= esc($telegram_chat_id); ?>" 
                    placeholder="Введите ваш chat_id">
                    <a href="https://t.me/Getmyid_bot" target="_blank">Не знаете свой ID, напишите боту</a>
            </div>
            
            <div class="form-group">
                <b>Типы получаемых уведомлений</b>

                <div class="card mt-2">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="220">Изменение даты</th>
                                        <th width="220">Изменение состава людей</th>
                                        <th width="220">Изменение статуса</th>
                                        <th width="220">Чужой комментарий</th>
                                        <th width="220">Мой комментарий</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                name="notify_task_date_changed"
                                                value="1"
                                                <?= !empty($user_notification_settings->notify_task_date_changed) ? "checked" : ""; ?>>
                                        </td>

                                        <td>
                                            <input type="checkbox"
                                                name="notify_task_assignees_changed"
                                                value="1"
                                                <?= !empty($user_notification_settings->notify_task_assignees_changed) ? "checked" : ""; ?>>
                                        </td>

                                        <td>
                                            <input type="checkbox"
                                                name="notify_task_status_changed"
                                                value="1"
                                                <?= !empty($user_notification_settings->notify_task_status_changed) ? "checked" : ""; ?>>
                                        </td>

                                        <td>
                                            <input type="checkbox"
                                                name="notify_task_comment_added"
                                                value="1"
                                                <?= !empty($user_notification_settings->notify_task_comment_added) ? "checked" : ""; ?>>
                                        </td>
                                        
                                        <td>
                                            <input type="checkbox"
                                                name="notify_task_my_comment_added"
                                                value="1"
                                                <?= !empty($user_notification_settings->notify_task_my_comment_added) ? "checked" : ""; ?>>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($my_projects)) : ?>
                <div class="form-group">
                    <b>Настройка уведомлений по проектам</b>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="overflow:auto; max-height:500px;">                            
                            <table id="projects-notifications-table"
                                class="table table-bordered table-striped text-center align-middle mb-0 w-100"
                                style="min-width:900px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:220px;">Проект</th>
                                        <th>Ответственный</th>
                                        <th>Исполнитель</th>
                                        <th>Участник</th>
                                    </tr>
                                    <tr>
                                        <th></th>

                                        <th class="text-center align-middle">
                                            <input type="checkbox" class="check-all" data-col="1">
                                        </th>

                                        <th class="text-center align-middle">
                                            <input type="checkbox" class="check-all" data-col="2">
                                        </th>

                                        <th class="text-center align-middle">
                                            <input type="checkbox" class="check-all" data-col="3">
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php 
                                        $roles = [
                                            "Создатель задачи",
                                            "Исполнитель",
                                            "Участник"
                                        ];
                                    ?>
                                    <?php foreach ($my_projects as $project) : ?>
                                        <tr>
                                            <th class="text-start bg-light">
                                                <a href="<?= get_uri("projects/view/" . $project->id); ?>" target="_blank">
                                                    <?= esc($project->title); ?>
                                                </a>
                                            </th>

                                            <?php foreach ($roles as $role) : ?>
                                                <td class="text-center">
                                                    <input type="checkbox"
                                                        name="notifications[<?= $project->id; ?>][<?= md5($role); ?>]"
                                                        value="1"
                                                        <?= !empty($settings_map[$project->id][$role]) ? "checked" : ""; ?>>
                                                </td>
                                            <?php endforeach; ?>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="alert alert-info mt20">
                    Вы не участвуете ни в одном проекте
                </div>
            <?php endif; ?>

            <?php app_hooks()->do_action('app_hook_team_members_my_preferences_extension'); ?>

        </div>
        <div class="card-footer rounded-0">
            <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#my-preferences-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });

        $("#my-preferences-form .select2").select2();

        $("#hidden_topbar_menus").select2({
            multiple: true,
            data: <?php echo ($hidden_topbar_menus_dropdown); ?>
        });

        $("#enable-web-notification").select2().on("change", function () {
            var value = $(this).val();
            if (value === "1") {
<?php if (get_setting("enable_push_notification")) { ?>
                    $("#disable-push-notification-area").removeClass("hide");
<?php } ?>
            } else {
                $("#disable-push-notification-area").addClass("hide");
            }
        });
    });
</script>

<script>
    $(document).ready(function () { 
        var table = $('#projects-notifications-table').DataTable({
            order: [[0, 'asc']],
            pageLength: -1,
            lengthMenu: [[-1], ["Все"]],
            columnDefs: [{ orderable: false, targets: [1,2,3] }],
            searching: false,
            language: {
                processing: "Обработка...",
                search: "Поиск:",
                lengthMenu: "Показать _MENU_ записей",
                info: "Показано с _START_ по _END_ из _TOTAL_ записей",
                infoEmpty: "Показано 0 записей",
                infoFiltered: "(отфильтровано из _MAX_ записей)",
                loadingRecords: "Загрузка...",
                zeroRecords: "Записи не найдены",
                emptyTable: "В таблице нет данных",
                paginate: {
                    first: "Первая",
                    previous: "Предыдущая",
                    next: "Следующая",
                    last: "Последняя"
                },
                aria: {
                    sortAscending: ": активировать для сортировки по возрастанию",
                    sortDescending: ": активировать для сортировки по убыванию"
                }
            },
            initComplete: function () {
                if (!$('#project-search').length) {
                    $('#projects-notifications-table_length').append(
                        '<input type="text" id="project-search" class="form-control" style="float: right; background-color: #f6f8f9 !important; width: 250px;" placeholder="Поиск по названию проекта">'
                    );
                }

                $('#projects-notifications-table_length').css({
                    display: 'flex',
                    justifyContent: 'space-between',
                    flexDirection: 'row',
                    width: '100%',
                    marginBottom: '10px'
                });

                $('#projects-notifications-table_wrapper > div:nth-child(1) > div:nth-child(1)').css({
                    width: '100%'
                })
            }
        });

        $(document).on('input', '#project-search', function () {
            var value = $(this).val().toLowerCase();

            var rows = table.rows().nodes().to$();

            rows.each(function () {
                var firstColumn = $(this).find('th:first').text().toLowerCase();

                if (value && firstColumn.includes(value)) {
                    $(this).addClass('search-match');
                } else {
                    $(this).removeClass('search-match');
                }
            });

            if (value) {
                rows.sort(function (a, b) {
                    var aMatch = $(a).hasClass('search-match') ? 0 : 1;
                    var bMatch = $(b).hasClass('search-match') ? 0 : 1;
                    return aMatch - bMatch;
                }).appendTo('#projects-notifications-table tbody');
            }
        });

        $('.check-all').on('change', function () {
            var colIndex = $(this).data('col');
            var isChecked = $(this).is(':checked');

            table.rows().nodes().to$()
                .find('td:nth-child(' + (colIndex + 1) + ') input[type="checkbox"]')
                .prop('checked', isChecked);
        });
    });
</script>
