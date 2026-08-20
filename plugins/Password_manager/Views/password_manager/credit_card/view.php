<div class="modal-body clearfix general-form">
    <div class="container-fluid">
        <div class="clearfix">
            <div class="row">
                <div class="col-md-12 mb10">
                    <strong class="font-18"><?php echo $model_info->name; ?></strong>
                </div>

                <?php if ($model_info->description) { ?>
                    <div class="col-md-12 mb5">
                        <blockquote class="font-14 text-justify border-info"><?php echo $model_info->description ? nl2br(link_it($model_info->description)) : "-"; ?></blockquote>
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

                <div class="col-md-12 mb15">
                    <div class="row">
                        <div class="col-md-2">
                            <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_credit_card_type') . ": "; ?></strong>
                        </div>
                        <div class="col-md-10">
                            <pre class="font-14"><?php echo $model_info->credit_card_type; ?> </pre>
                        </div>
                    </div>
                </div>

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

                <div class="col-md-12 mb15 card-number-section">
                    <div class="row">
                        <div class="col-md-2">
                            <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_card_number') . ": "; ?></strong>
                        </div>
                        <div class="col-md-10">
                            <pre class="font-14"><span class="card-number"><?php echo $model_info->card_number; ?></span> <span data-feather="copy" class="icon-14 float-end clickable text-secondary copy-card-number hide pe-auto mt-1" onclick="copyToClipboard('.card-number')"></span></pre>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb15 pin-section">
                    <div class="row">
                        <div class="col-md-2">
                            <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_pin') . ": "; ?></strong>
                        </div>
                        <div class="col-md-10">
                            <pre class="font-14"><a href="#" class="password_show mr5 text-decoration-underline"><?php echo app_lang('password_manager_show'); ?></a> <span class="password hide"><?php echo $model_info->pin; ?></span><span data-feather="copy" class="icon-14 float-end clickable text-secondary copy-pin hide pe-auto mt-1" onclick="copyToClipboard('.password')"></span></pre>
                        </div>
                    </div>
                </div>
                
                <?php if ($model_info->card_cvc) { ?>
                    <div class="col-md-12 mb15">
                        <div class="row">
                            <div class="col-md-2">
                                <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_card_cvc') . ": "; ?></strong>
                            </div>
                            <div class="col-md-10">
                                <pre class="font-14"><?php echo $model_info->card_cvc; ?> </pre>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($model_info->valid_from && $model_info->valid_to) { ?>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_valid_from') . ": "; ?></strong>
                                    </div>
                                    <div class="col-md-8">
                                        <pre class="font-14"><?php echo $model_info->valid_from; ?> </pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong class="float-start mr10 mt-2"><?php echo app_lang('password_manager_valid_to') . ": "; ?></strong>
                                    </div>
                                    <div class="col-md-8">
                                        <pre class="font-14"><?php echo $model_info->valid_to; ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <?php if ($model_info->created_by == $login_user->id || ($model_info->created_by_client == 1 && $login_user->is_admin)) { ?>
        <?php echo modal_anchor(get_uri("password_manager/credit_card_modal_form/"), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit'), array("class" => "btn btn-default", "title" => app_lang('edit'), "data-post-id" => $model_info->id)); ?>
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

        $(".pin-section").hover(function () {
            $('.copy-pin').removeClass("hide");
            $('.copy-pin').show();
        }, function () {
            $('.copy-pin').hide();
        });

        $(".card-number-section").hover(function () {
            $('.copy-card-number').removeClass("hide");
            $('.copy-card-number').show();
        }, function () {
            $('.copy-card-number').hide();
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