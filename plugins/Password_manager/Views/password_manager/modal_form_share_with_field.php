<?php
$all_team_members = $model_info->share_with == "all" ? "disabled" : "";
$all_client_contacts = $model_info->share_with_client == "all" ? "disabled" : "";
?>

<?php if ($login_user->user_type == "client") { ?>

    <div class="form-group text-off">
        <i data-feather="alert-triangle" class="icon-16"></i> <span><?php echo app_lang('password_manager_admin_can_manage_instruction'); ?></span>
    </div>

    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>" />
    <input type="hidden" name="created_by_client" value="1" />
<?php } else { ?>
    <div class="form-group">
        <div class="row">
            <label for="client_id" class=" col-md-3"><?php echo app_lang('client'); ?></label>
            <div class=" col-md-9">
                <?php
                echo form_input(array(
                    "id" => "clients_dropdown",
                    "name" => "client_id",
                    "value" => $model_info->client_id,
                    "class" => "form-control"
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="share_with" class=" col-md-3"><?php echo app_lang('share_with'); ?></label>
            <div class=" col-md-9">
                <!--Team members-->
                <div class="share-with-section mb15">
                    <div>
                        <?php
                        echo form_checkbox(array(
                            "id" => "share_with_all_team_members",
                            "name" => "share_with_team_members",
                            "value" => "all",
                            "class" => "toggle_specific form-check-input",
                                ), $model_info->share_with ? $model_info->share_with : "", ($model_info->share_with === "all") ? true : false);
                        ?>
                        <label for="share_with_all_team_members" class="form-check-label"><?php echo app_lang("all_team_members"); ?></label>
                    </div>

                    <div class="form-group mb-0">
                        <?php
                        echo form_checkbox(array(
                            "id" => "share_with_specific_team_members_button",
                            "name" => "share_with_team_members",
                            "value" => "specific",
                            "class" => "toggle_specific form-check-input",
                                ), $model_info->share_with ? $model_info->share_with : "", ($model_info->share_with && $model_info->share_with != "all") ? true : false, $all_team_members);
                        ?>
                        <label for="share_with_specific_team_members_button" class="form-check-label"><?php echo app_lang("specific_members_and_teams"); ?>:</label>
                        <div class="specific_dropdown">
                            <input type="text" value="<?php echo ($model_info->share_with && $model_info->share_with != "all") ? $model_info->share_with : ""; ?>" name="share_with_specific_team_members" id="share_with_specific_team_members" class="w100p validate-hidden"  data-rule-required="true" data-msg-required="<?php echo app_lang('field_required'); ?>" placeholder="<?php echo app_lang('choose_members_and_or_teams'); ?>"  />
                        </div>
                    </div>
                </div>

                <!--Clients-->
                <div id="share-with-client-contact" class="form-group mb0 hide">
                    <div>
                        <?php
                        echo form_checkbox(array(
                            "id" => "share_with_all_client_contacts",
                            "name" => "share_with_client",
                            "value" => "all",
                            "class" => "toggle_specific_client form-check-input",
                                ), $model_info->share_with_client ? $model_info->share_with_client : "", ($model_info->share_with_client === "all") ? true : false);
                        ?>
                        <label for="share_with_all_client_contacts"><?php echo app_lang("password_manager_all_client_contacts"); ?></label>
                    </div>
                    <div>
                        <?php
                        echo form_checkbox(array(
                            "id" => "share_with_specific_client_contacts_button",
                            "name" => "share_with_client",
                            "value" => "specific",
                            "class" => "toggle_specific_client form-check-input",
                                ), $model_info->share_with_client ? $model_info->share_with_client : "", ($model_info->share_with_client && $model_info->share_with_client != "all") ? true : false, $all_client_contacts);
                        ?>
                        <label for="share_with_specific_client_contacts_button" class="form-check-label"><?php echo app_lang("specific_client_contacts"); ?>:</label>
                        <div class="specific_dropdown_client">
                            <input type="text" value="<?php echo ($model_info->share_with_client && $model_info->share_with_client != "all" ) ? $model_info->share_with_client : ""; ?>" name="share_with_specific_client_contact" id="share_with_specific_client_contact" class="w100p validate-hidden"  data-rule-required="true" data-msg-required="<?php echo app_lang('field_required'); ?>" placeholder="<?php echo app_lang('choose_client_contacts'); ?>"  />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<?php } ?>