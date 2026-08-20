<div class="table-responsive">
    <table id="telegram-notification-settings-table" class="display" cellspacing="0" width="100%">            
    </table>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#telegram-notification-settings-table").appTable({
            source: '<?php echo_uri("telegram_notification_settings/notification_settings_list_data") ?>',
            filterDropdown: [{name: "category", class: "w200", options: <?php echo $categories_dropdown; ?>}],
            columns: [
                {visible: false},
                {title: '<?php echo app_lang("event"); ?>'},
                {title: '<?php echo app_lang("category"); ?>', class: "w15p"},
                {title: '<?php echo app_lang("telegram_notification_enable_telegram"); ?>', class: "w15p text-center"},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w10p"}
            ],
            order: [[0, "asc"]],
            displayLength: 100
        });
    });
</script>