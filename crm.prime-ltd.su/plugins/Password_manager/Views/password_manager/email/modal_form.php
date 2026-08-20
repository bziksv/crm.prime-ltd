<?php echo form_open(get_uri("password_manager/save_email"), array("id" => "password-manager-email-form", "class" => "general-form", "role" => "form")); ?>
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
                <label for="email_type" class=" col-md-3"><?php echo app_lang('password_manager_email_type'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "email_type",
                        "name" => "email_type",
                        "value" => $model_info->email_type,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_email_type')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="auth_method" class=" col-md-3"><?php echo app_lang('password_manager_auth_method'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "auth_method",
                        "name" => "auth_method",
                        "value" => $model_info->auth_method,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_auth_method')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="host" class=" col-md-3"><?php echo app_lang('password_manager_host'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "host",
                        "name" => "host",
                        "value" => $model_info->host,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_host')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="port" class=" col-md-3"><?php echo app_lang('password_manager_port'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "port",
                        "name" => "port",
                        "value" => $model_info->port,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_port')
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="username" class=" col-md-3"><?php echo app_lang('username'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "username",
                        "name" => "username",
                        "value" => $model_info->username,
                        "class" => "form-control",
                        "placeholder" => app_lang('username')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="password" class="col-md-3"><?php echo app_lang('password'); ?></label>
                <div class="col-md-8">
                    <div class="input-group">
                        <?php
                        echo form_password(array(
                            "id" => "password",
                            "name" => "password",
                            "value" => $model_info->password,
                            "class" => "form-control",
                            "placeholder" => app_lang('password'),
                            "autocomplete" => "off",
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                            "autocomplete" => "off",
                            "style" => "z-index:auto;"
                        ));
                        ?>
                        <button type="button" class="input-group-text clickable no-border" id="generate_password"><span data-feather="key" class="icon-16"></span> <?php echo app_lang('generate'); ?></button>
                    </div>
                </div>
                <div class="col-md-1 p0">
                    <a href="#" id="show_hide_password" class="btn btn-default" title="<?php echo app_lang('show_text'); ?>"><span data-feather="eye" class="icon-16"></span></a>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="smtp_auth_method" class=" col-md-3"><?php echo app_lang('password_manager_smtp_auth_method'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "smtp_auth_method",
                        "name" => "smtp_auth_method",
                        "value" => $model_info->smtp_auth_method,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_smtp_auth_method')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="smtp_host" class=" col-md-3"><?php echo app_lang('password_manager_smtp_host'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "smtp_host",
                        "name" => "smtp_host",
                        "value" => $model_info->smtp_host,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_smtp_host')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="smtp_port" class=" col-md-3"><?php echo app_lang('password_manager_smtp_port'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "smtp_port",
                        "name" => "smtp_port",
                        "value" => $model_info->smtp_port,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_smtp_port')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="smtp_user_name" class=" col-md-3"><?php echo app_lang('password_manager_smtp_user_name'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "smtp_user_name",
                        "name" => "smtp_user_name",
                        "value" => $model_info->smtp_user_name,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_smtp_user_name')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="smtp_password" class="col-md-3"><?php echo app_lang('password_manager_smtp_password'); ?></label>
                <div class="col-md-8">
                    <div class="input-group">
                        <?php
                        echo form_password(array(
                            "id" => "smtp_password",
                            "name" => "smtp_password",
                            "value" => $model_info->smtp_password,
                            "class" => "form-control",
                            "placeholder" => app_lang('password_manager_smtp_password'),
                            "autocomplete" => "off",
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                            "autocomplete" => "off",
                            "style" => "z-index:auto;"
                        ));
                        ?>
                        <button type="button" class="input-group-text clickable no-border" id="generate_smtp_password"><span data-feather="key" class="icon-16"></span> <?php echo app_lang('generate'); ?></button>
                    </div>
                </div>
                <div class="col-md-1 p0">
                    <a href="#" id="show_hide_smtp_password" class="btn btn-default" title="<?php echo app_lang('show_text'); ?>"><span data-feather="eye" class="icon-16"></span></a>
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
        $("#password-manager-email-form").appForm({
            onSuccess: function (result) {
                $("#password-manager-email-table").appTable({newData: result.data, dataId: result.id});
            }
        });

        $("#category_id").select2();

        $("#generate_password").on("click", function () {
            $("#password").val(getRndomString(8));
        });

        $("#generate_smtp_password").on("click", function () {
            $("#smtp_password").val(getRndomString(8));
        });

        $("#show_hide_password").on("click", function () {
            var $target = $("#password"),
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

        $("#show_hide_smtp_password").on("click", function () {
            var $target = $("#smtp_password"),
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