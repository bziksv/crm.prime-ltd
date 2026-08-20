<?php echo form_open(get_uri("telegram_notification_settings/save_notification_settings"), array("id" => "telegram-notification-settings-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">

        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" name="event" value="<?php echo $model_info->event; ?>" />
        <div class="form-group">
            <div class="row">
                <div class="col-md-12 text-off"><i data-feather="alert-triangle" class="icon-16 text-warning"></i> <?php echo app_lang("telegram_notification_edit_instruction"); ?></div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="title" class=" col-md-3"><strong><?php echo app_lang('event'); ?></strong></label>
                <div class=" col-md-9">
                    <strong>
                        <?php
                        echo app_lang($model_info->event);
                        ?>
                    </strong>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="enable_telegram_notification" class="col-md-3"><?php echo app_lang('telegram_notification_enable_telegram'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_checkbox("enable_telegram", "1", $model_info->enable_telegram ? true : false, "id='enable_telegram_notification' class='form-check-input'");
                    ?>                       
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#telegram-notification-settings-form").appForm({
            onSuccess: function (result) {
                $("#telegram-notification-settings-table").appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>    