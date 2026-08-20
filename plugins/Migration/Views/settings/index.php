<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "all_plugins";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="card-header">Загрузка изменений для базы данных</div>
                <?php echo form_open(get_uri("migration/settings/store"), array("id" => "request-query-form", "class" => "general-form dashed-row", "role" => "form")); ?>
                <div class="card-body">

                    <div class="form-group">
                        <div class="row">
                            <label for="chat_id" class=" col-md-2">Миграция</label>
                            <div class=" col-md-10">
                                <?php
                                echo form_dropdown([
                                        "name" => 'file',
                                        "class" => 'form-control',
                                        "options" => $options,
                                ]);
                                ?>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('send'); ?></button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#request-query-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
