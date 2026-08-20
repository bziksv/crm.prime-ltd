<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="pl15 pt10 pr15"><?php echo app_lang("categories"); ?></h4>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("password_manager/category_modal_form/"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_category'), array("class" => "btn btn-default", "title" => app_lang('add_category'))); ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="password-manager-category-table" class="display" cellspacing="0" width="100%">            
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#password-manager-category-table").appTable({
            source: '<?php echo_uri("password_manager/categories_list_data/") ?>',
            columns: [
                {title: '<?php echo app_lang("title"); ?>'},
                {title: '<?php echo app_lang("description"); ?>'},
                {title: '<?php echo app_lang("status"); ?>'},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3]
        });
    });
</script>