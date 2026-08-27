<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "outbound_proxy";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo app_lang('outbound_proxy_settings'); ?></h4>
                </div>
                <div class="card-body">
                    <?php echo view("settings/proxy/index", array('status' => $status)); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
