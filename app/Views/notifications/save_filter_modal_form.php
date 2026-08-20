<?php echo form_open(get_uri("notifications/store_user_filter" .'?'. http_build_query($params)), array("id" => "notification-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix label-modal-body">
    <div class="container-fluid">
        <input type="hidden" name="params" value="" />

        <div class="form-group">
            <div class="row">
                <label for="title" class=" col-md-3"><?php echo app_lang('title'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => "",
                        "class" => "form-control",
                        "placeholder" => app_lang('title'),
                        "required" => true
                    ));
                    ?>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal-footer">
    <button type="submit" class="btn btn-default"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {

        $("#notification-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                if (result.success) {
                    window.location.reload();
                }
            }
        });

    });
</script>
