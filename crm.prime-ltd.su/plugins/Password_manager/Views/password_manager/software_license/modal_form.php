<?php echo form_open(get_uri("password_manager/save_software_license"), array("id" => "password-manager-software-license-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="name" class=" col-md-3"><?php echo app_lang('name'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "name",
                        "name" => "name",
                        "value" => $model_info->name,
                        "class" => "form-control",
                        "placeholder" => app_lang('name'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="category_id" class="col-md-3"><?php echo app_lang('category'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_dropdown("category_id", $categories_dropdown, $model_info->category_id, "class='select2 validate-hidden' id='category_id' data-rule-required='true', data-msg-required='" . app_lang('field_required') . "'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="url" class=" col-md-3"><?php echo app_lang('url'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "url",
                        "name" => "url",
                        "value" => $model_info->url,
                        "class" => "form-control",
                        "placeholder" => app_lang('url')
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="version" class=" col-md-3"><?php echo app_lang('version'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "version",
                        "name" => "version",
                        "value" => $model_info->version,
                        "class" => "form-control",
                        "placeholder" => app_lang('version')
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="license_key" class=" col-md-3"><?php echo app_lang('password_manager_license_key'); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_password(array(
                        "id" => "license_key",
                        "name" => "license_key",
                        "value" => $model_info->license_key,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_license_key'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                        "autocomplete" => "off",
                        "style" => "z-index:auto;"
                    ));
                    ?>
                </div>
                <div class="col-md-1 p0">
                    <a href="#" id="show_hide_key" class="btn btn-default" title="<?php echo app_lang('show_text'); ?>"><span data-feather="eye" class="icon-16"></span></a>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class=" col-md-3"><?php echo app_lang('description'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "description",
                        "name" => "description",
                        "value" => $model_info->description,
                        "class" => "form-control",
                        "placeholder" => app_lang('description'),
                        "data-rich-text-editor" => true
                    ));
                    ?>
                </div>
            </div>
        </div>

        <?php echo view("Password_manager\Views\password_manager\\modal_form_share_with_field", array("model_info" => $model_info)); ?>

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
        $("#password-manager-software-license-form").appForm({
            onSuccess: function (result) {
                $("#password-manager-software-license-table").appTable({newData: result.data, dataId: result.id});
            }
        });

        $("#category_id").select2();

        $("#show_hide_key").on("click", function () {
            var $target = $("#license_key"),
                    type = $target.attr("type");
            if (type === "password") {
                $(this).attr("title", "<?php echo app_lang("hide_text"); ?>");
                $(this).html("<span data-feather='eye-off' class='icon-16'></span>");
                feather.replace();
                $target.attr("type", "text");
            } else if (type === "text") {
                $(this).attr("title", "<?php echo app_lang("show_text"); ?>");
                $(this).html("<span data-feather='eye' class='icon-16'></span>");
                feather.replace();
                $target.attr("type", "password");
            }
        });

        get_specific_dropdown($("#share_with_specific_team_members"), <?php echo ($members_and_teams_dropdown); ?>);

        var clientId = "<?php echo $model_info->client_id; ?>";

        if (clientId && clientId != "0") {
            prepareShareWithClientContactsDropdown(clientId);
        }

        //show the specific client contacts readio button after select any client
        $('#clients_dropdown').select2({data: <?php echo json_encode($clients_dropdown); ?>}).on("change", function () {
            prepareShareWithClientContactsDropdown($(this).val());
        });

    });
</script>

<?php echo view("Password_manager\Views\password_manager\modal_from_script"); ?>
