<div class="card">
    <div class="table-responsive">
        <table id="password-manager-email-table" class="display" cellspacing="0" width="100%">            
        </table>
    </div>
</div>
<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#password-manager-email-table").appTable({
            source: '<?php echo_uri("password_manager/list_data_of_email/") ?>',
            columns: [
                {title: "<?php echo app_lang('id'); ?>"},
                {title: "<?php echo app_lang('name'); ?>"},
                {title: "<?php echo app_lang('category'); ?>"},
                {title: "<?php echo app_lang('password_manager_email_type'); ?>"},
                {title: "<?php echo app_lang('password_manager_host'); ?>"},
                {title: "<?php echo app_lang('password_manager_port'); ?>"},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>