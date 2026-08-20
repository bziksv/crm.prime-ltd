<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "telegram_notification";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <ul data-bs-toggle="ajax-tab" class="nav nav-tabs bg-white title" role="tablist">
                    <li><a role="presentation" data-bs-toggle="tab" href="javascript:;" data-bs-target="#telegram-settings-tab"> Telegram <?php echo strtolower(app_lang('integration')); ?></a></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("telegram_notification_settings/notification_settings"); ?>" data-bs-target="#telegram-notification-settings-tab"><?php echo app_lang('telegram_integration_settings'); ?></a></li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade" id="telegram-settings-tab">
                        <?php echo form_open(get_uri("telegram_notification_settings/save_telegram_integration_settings"), array("id" => "telegram-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="row">
                                    <label for="enable_telegram" class="col-md-2 col-xs-8 col-sm-4"><?php echo app_lang('enable_telegram'); ?></label>
                                    <div class="col-md-10 col-xs-4 col-sm-8">
                                        <?php
                                        echo form_checkbox("enable_telegram", "1", get_telegram_notification_setting("enable_telegram") ? true : false, "id='enable_telegram' class='form-check-input ml15'");
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div id="telegram-details-area" class="<?php echo get_telegram_notification_setting("enable_telegram") ? "" : "hide" ?>">

                                <div class="form-group">
                                    <div class="row">
                                        <label for="bot_token" class=" col-md-2"><?php echo app_lang('telegram_bot_token'); ?></label>
                                        <div class=" col-md-10">
                                            <?php
                                            echo form_input(array(
                                                "id" => "bot_token",
                                                "name" => "bot_token",
                                                "value" => get_telegram_notification_setting("bot_token"),
                                                "class" => "form-control",
                                                "placeholder" => app_lang('telegram_bot_token'),
                                                "data-rule-required" => true,
                                                "data-msg-required" => app_lang("field_required")
                                            ));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="form-group">
                                    <div class="row">
                                        <label for="chat_id" class=" col-md-2"><?php echo app_lang('telegram_chat_id'); ?></label>
                                        <div class=" col-md-10">
                                            <?php
                                            echo form_input(array(
                                                "id" => "chat_id",
                                                "name" => "chat_id",
                                                "value" => get_telegram_notification_setting("chat_id"),
                                                "class" => "form-control",
                                                "placeholder" => app_lang('telegram_chat_id'),
                                                "data-rule-required" => true,
                                                "data-msg-required" => app_lang("field_required")
                                            ));
                                            ?>
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
                            <?php if (get_telegram_notification_setting("enable_telegram") && get_telegram_notification_setting("bot_token") && get_telegram_notification_setting("chat_id")) { ?>
                                <button id="test-telegram-btn" type="button" class="btn btn-info text-white ml15"><span data-feather="send" class="icon-16"></span> <?php echo app_lang('send_a_test_message'); ?></button>
                            <?php } ?>
                        </div>
                        <?php echo form_close(); ?>
                    </div>

                    <div role="tabpanel" class="tab-pane fade" id="telegram-notification-settings-tab"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#telegram-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                if (result.success) {
                    if ($("#enable_telegram").is(":checked")) {
                        window.location.href = "<?php echo_uri("telegram_notification_settings"); ?>";
                    } else {
                        appAlert.success(result.message, {duration: 10000});
                    }
                }
            }
        });

        //show/hide telegram details area
        $("#enable_telegram").click(function () {
            $("#test-telegram-btn").addClass("hide");
            if ($(this).is(":checked")) {
                $("#telegram-details-area").removeClass("hide");
            } else {
                $("#telegram-details-area").addClass("hide");
            }
        });

        //send a demo message
        $("#test-telegram-btn").click(function () {
            appLoader.show();
            $.ajax({
                url: '<?php echo_uri("telegram_notification_settings/test_telegram_notification") ?>',
                type: "POST",
                dataType: "json",
                success: function (result) {
                    appLoader.hide();
                    if (!result.success) {
                        appAlert.error(result.message);
                    }
                }
            });
        });

    });
</script>