<?php echo form_open(get_uri("/tasks/update_checklist_item"), array("id" => "checklist-update-form", "class" => "general-form", "role" => "form")); ?>
<input type="hidden" name="checklist_id" value="<?php echo $checklist_id; ?>" />

<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group">
            <div class=" col-md-12">
                <?php
                echo form_textarea(array(
                    "id" => "checklist_title",
                    "name" => "checklist_title",
                    "class" => "form-control",
                    "data-rich-text-editor" => true,
                    "value" => $checklist_item->title
                ));
                ?>
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
        $("#checklist-update-form").appForm({
            onSuccess: function (result) {
                location.reload();
            }
        });
        setTimeout(function () {
            $("#checklist_title").focus();
        }, 200);

        $("#ajaxModal").unbind( "hidden.bs.modal" );

        $("#ajaxModal").bind("hidden.bs.modal", function () {
            location.reload();
        });
    });
</script>
