<div class="form-group general-form">
    <div class="mt5 p0">
        <?php
        echo form_textarea(array(
            "data-task_id" => $task_id,
            "id" => "task-personal-note",
            "class" => "form-control",
            "value" => $text,
            "placeholder" => app_lang('description'),
            "autocomplete" => "off",
            "style" => "min-height:400px",
        ));
        ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#task-personal-note").on("focusout", function () {
            let self = $(this);

            $.ajax({
                url: '<?php echo_uri("tasks/save_personal_note") ?>',
                type: "POST",
                data: {
                    task_id: self.data('task_id'),
                    text: self.val()
                },
                success: function () {
                    appAlert.success('<?php echo app_lang('saved_note')?>', { duration: 10000 });
                }
            });
        });
    });
</script>
