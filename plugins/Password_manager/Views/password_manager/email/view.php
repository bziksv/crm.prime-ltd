<div class="modal-body clearfix general-form">
    <div class="container-fluid">
        <div class="clearfix">
            <div class="row">
                <div class="col-md-12 mb10">
                    <strong class="font-18"><?php echo $model_info->name; ?></strong>
                </div>

                <?php if ($model_info->description) { ?>
                    <div class="col-md-12 mb5">
                        <blockquote class="font-14 text-justify border-danger"><?php echo $model_info->description ? nl2br(link_it($model_info->description)) : "-"; ?></blockquote>
                    </div>
                <?php } ?>

                <div class="col-md-12 mb15">
                    <span class="text-off font-12">
                        <?php echo app_lang("created_by") . " " . $model_info->created_by_user; ?>
                    </span>
                    <span class="text-off float-end font-12">
                        <?php echo format_to_datetime($model_info->created_at); ?>
                    </span>
                </div>

                <?php if ($model_info->email_type) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_email_type') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->email_type; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php if ($model_info->auth_method) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_auth_method') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->auth_method; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($model_info->host) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_host') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->host; ?></pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($model_info->port) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_port') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->port; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($model_info->username) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('username') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->username; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <div class="col-md-12 mb15 password-section">
                    <div class="row">
                        <div class="col-md-2">
                            <strong class="float-start mr10 mt-2"><?php echo app_lang('password') . ": "; ?></strong>
                        </div>
                        <div class="col-md-10">
                            <pre class="font-14"><a href="#" class="password_show mr5 text-decoration-underline"><?php echo app_lang('password_manager_show'); ?></a> <span class="password hide"><?php echo $model_info->password; ?></span><span data-feather="copy" class="icon-14 float-end clickable text-secondary copy-password hide pe-auto mt-1" onclick="copyToClipboard('.password')"></span></pre>
                        </div>
                    </div>
                </div>
                
                <?php if ($model_info->smtp_auth_method) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_smtp_auth_method') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->smtp_auth_method; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($model_info->smtp_host) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_smtp_host') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->smtp_host; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($model_info->smtp_port) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_smtp_port') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->smtp_port; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($model_info->smtp_user_name) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_smtp_user_name') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->smtp_user_name; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <div class="col-md-12 mb15 smtp-password-section">
                    <div class="row">
                        <div class="col-md-2">
                            <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_smtp_password') . ": "; ?></strong>
                        </div>
                        <div class="col-md-10">
                            <pre class="font-14"><a href="#" class="smtp_password_show mr5 text-decoration-underline"><?php echo app_lang('password_manager_show'); ?></a> <span class="smtp_password hide"><?php echo $model_info->smtp_password; ?></span><span data-feather="copy" class="icon-14 float-end clickable text-secondary copy-smtp-password hide pe-auto mt-1" onclick="copyToClipboard('.smtp_password')"></span></pre>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <?php if ($model_info->created_by == $login_user->id || ($model_info->created_by_client == 1 && $login_user->is_admin)) { ?>
        <?php echo modal_anchor(get_uri("password_manager/email_modal_form/"), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit'), array("class" => "btn btn-default", "title" => app_lang('edit'), "data-post-id" => $model_info->id)); ?>
    <?php } ?>
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
</div>

<script type="text/javascript">
        "use strict";

    $(document).ready(function () {
        $(".password_show").on("click", function () {
            $(".password").toggleClass("hide");

            var $test = $(".password_show").text();
            if ($test === "Show") {
                $(".password_show").text("<?php echo app_lang('password_manager_hide'); ?>");
            } else if ($test === "Hide") {
                $(".password_show").text("<?php echo app_lang('password_manager_show'); ?>");
            }
        });

        $(".smtp_password_show").on("click", function () {
            $(".smtp_password").toggleClass("hide");

            var $test = $(".smtp_password_show").text();
            if ($test === "Show") {
                $(".smtp_password_show").text("<?php echo app_lang('password_manager_hide'); ?>");
            } else if ($test === "Hide") {
                $(".smtp_password_show").text("<?php echo app_lang('password_manager_show'); ?>");
            }
        });

        $(".password-section").hover(function () {
            $('.copy-password').removeClass("hide");
            $('.copy-password').show();
        }, function () {
            $('.copy-password').hide();
        });

        $(".smtp-password-section").hover(function () {
            $('.copy-smtp-password').removeClass("hide");
            $('.copy-smtp-password').show();
        }, function () {
            $('.copy-smtp-password').hide();
        });
    });

    function copyToClipboard(element) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(element).text()).select();
        document.execCommand("copy");
        $temp.remove();
    }
</script>