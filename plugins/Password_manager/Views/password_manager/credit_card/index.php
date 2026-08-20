<div class="card">
    <div class="table-responsive">
        <table id="password-manager-credit-card-table" class="display" cellspacing="0" width="100%">            
        </table>
    </div>
</div>
<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#password-manager-credit-card-table").appTable({
            source: '<?php echo_uri("password_manager/list_data_of_credit_card/") ?>',
            columns: [
                {title: "<?php echo app_lang('id'); ?>"},
                {title: "<?php echo app_lang('name'); ?>"},
                {title: "<?php echo app_lang('category'); ?>"},
                {title: "<?php echo app_lang('password_manager_credit_card_type'); ?>"},
                {title: "<?php echo app_lang('password_manager_valid_from'); ?>"},
                {title: "<?php echo app_lang('password_manager_valid_to'); ?>"},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>