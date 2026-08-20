<?php echo form_open(get_uri("password_manager/save_credit_card"), array("id" => "password-manager-credit-card-form", "class" => "general-form", "role" => "form")); ?>
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
                <label for="credit_card_type" class=" col-md-3"><?php echo app_lang('password_manager_credit_card_type'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "credit_card_type",
                        "name" => "credit_card_type",
                        "value" => $model_info->credit_card_type,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_credit_card_type'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="card_number" class=" col-md-3"><?php echo app_lang('password_manager_card_number'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "card_number",
                        "name" => "card_number",
                        "value" => $model_info->card_number,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_card_number'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="pin" class="col-md-3"><?php echo app_lang('password_manager_pin'); ?></label>
                <div class="col-md-8">
                    <div class="input-group">
                        <?php
                        echo form_password(array(
                            "id" => "pin",
                            "name" => "pin",
                            "value" => $model_info->pin,
                            "class" => "form-control",
                            "placeholder" => app_lang('password_manager_pin'),
                            "autocomplete" => "off",
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                            "autocomplete" => "off",
                            "style" => "z-index:auto;"
                        ));
                        ?>
                    </div>
                </div>
                <div class="col-md-1 p0">
                    <a href="#" id="show_hide_pin" class="btn btn-default" title="<?php echo app_lang('show_text'); ?>"><span data-feather="eye" class="icon-16"></span></a>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="card_cvc" class=" col-md-3"><?php echo app_lang('password_manager_card_cvc'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "card_cvc",
                        "name" => "card_cvc",
                        "value" => $model_info->card_cvc,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_card_cvc')
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="valid_from" class=" col-md-3"><?php echo app_lang('password_manager_valid_from'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "valid_from",
                        "name" => "valid_from",
                        "value" => $model_info->valid_from,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_valid_from'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                        "autocomplete" => "off"
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="valid_to" class=" col-md-3"><?php echo app_lang('password_manager_valid_to'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "valid_to",
                        "name" => "valid_to",
                        "value" => $model_info->valid_to,
                        "class" => "form-control",
                        "placeholder" => app_lang('password_manager_valid_to'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                        "autocomplete" => "off"
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
        $("#password-manager-credit-card-form").appForm({
            onSuccess: function (result) {
                $("#password-manager-credit-card-table").appTable({newData: result.data, dataId: result.id});
            }
        });

        $("#category_id").select2();

        $("#show_hide_pin").on("click", function () {
            var $target = $("#pin"),
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

        setDatePicker("#valid_from, #valid_to");

    });
</script>

<?php echo view("Password_manager\Views\password_manager\modal_from_script"); ?>