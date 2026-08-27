<?php echo form_open(get_uri("settings/save_outbound_proxy"), array("id" => "outbound-proxy-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

    <div class="form-group">
        <label for="label"><?php echo app_lang('name'); ?></label>
        <?php
        echo form_input(array(
            "id" => "label",
            "name" => "label",
            "value" => $model_info->label,
            "class" => "form-control",
            "placeholder" => app_lang('name'),
            "data-rule-required" => true,
            "data-msg-required" => app_lang("field_required"),
        ));
        ?>
    </div>

    <div class="form-group">
        <label for="supplier"><?php echo app_lang('outbound_proxy_supplier'); ?></label>
        <?php
        echo form_input(array(
            "id" => "supplier",
            "name" => "supplier",
            "value" => $model_info->supplier,
            "class" => "form-control",
            "placeholder" => app_lang('outbound_proxy_supplier'),
        ));
        ?>
    </div>

    <div class="form-group">
        <label for="url"><?php echo app_lang('outbound_proxy_url'); ?></label>
        <?php
        echo form_input(array(
            "id" => "url",
            "name" => "url",
            "value" => $model_info->url,
            "class" => "form-control",
            "placeholder" => "socks5h://user:pass@host:port",
            "data-rule-required" => true,
            "data-msg-required" => app_lang("field_required"),
        ));
        ?>
        <span class="help-block text-off small"><?php echo app_lang('outbound_proxy_url_help'); ?></span>
    </div>

    <div class="form-group">
        <label for="priority"><?php echo app_lang('outbound_proxy_priority'); ?></label>
        <?php
        echo form_input(array(
            "id" => "priority",
            "name" => "priority",
            "type" => "number",
            "value" => $model_info->priority !== '' ? $model_info->priority : 50,
            "class" => "form-control",
            "min" => 0,
            "max" => 999,
        ));
        ?>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="enabled" class="col-md-3"><?php echo app_lang('enabled'); ?></label>
            <div class="col-md-9">
                <?php
                echo form_checkbox("enabled", "1", $model_info->enabled ? true : false, "id='enabled' class='form-check-input ml15'");
                ?>
                <span class="text-off small ml5"><?php echo app_lang('outbound_proxy_enabled_help'); ?></span>
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
    $(document).ready(function () {
        $("#outbound-proxy-form").appForm({
            closeModalOnSuccess: true,
            onSuccess: function (result) {
                if (result.message) {
                    appAlert.success(result.message);
                }
                setTimeout(function () {
                    location.reload();
                }, 200);
            }
        });
    });
</script>
