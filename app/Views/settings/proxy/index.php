<?php
$status = $status ?? [];
$direct = get_array_value($status, 'direct', []);
$proxy_rows = get_array_value($status, 'proxies', []);
$send_order = get_array_value($status, 'send_order', []);
$probes_checked = !empty($status['probes_checked']);
$send_order_labels = [];
foreach ($send_order as $item) {
    if ($item === 'direct') {
        $send_order_labels[] = app_lang('outbound_proxy_direct');
    } elseif (strpos($item, 'proxy:') === 0) {
        $pid = (int) substr($item, 6);
        foreach ($proxy_rows as $row) {
            if ((int) get_array_value($row, 'id') === $pid) {
                $send_order_labels[] = get_array_value($row, 'label');
                break;
            }
        }
    }
}
?>

<div class="outbound-proxy-page">
    <div class="mb15">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <p class="text-off small mb0"><?php echo app_lang('outbound_proxy_lead'); ?></p>
            <?php echo js_anchor("<i data-feather='refresh-cw' class='icon-16'></i> " . app_lang('outbound_proxy_refresh'), array(
                "class" => "btn btn-default btn-sm",
                "id" => "outbound-proxy-refresh-btn",
                "data-action-url" => get_uri("settings/refresh_outbound_proxy"),
                "data-reload-on-success" => true,
            )); ?>
        </div>
    </div>

    <div class="row mb15">
        <div class="col-md-4 col-sm-6 mb10">
            <div class="card p15 mb0 h-100">
                <div class="text-off small"><?php echo app_lang('outbound_proxy_direct_check'); ?></div>
                <div class="font-16 strong mt5">
                    <?php if (!$probes_checked) { ?>
                        <span class="badge bg-secondary">—</span>
                    <?php } elseif (!empty($direct['ok'])) { ?>
                        <span class="badge bg-success"><?php echo app_lang('outbound_proxy_ok'); ?> (<?php echo (int) get_array_value($direct, 'http_code'); ?>)</span>
                    <?php } else { ?>
                        <span class="badge bg-danger"><?php echo app_lang('outbound_proxy_fail'); ?></span>
                    <?php } ?>
                </div>
                <?php if ($probes_checked) { ?>
                    <div class="text-off small mt5"><?php echo (int) get_array_value($direct, 'elapsed_ms'); ?> ms</div>
                <?php } ?>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb10">
            <div class="card p15 mb0 h-100">
                <div class="text-off small"><?php echo app_lang('outbound_proxy_list_count'); ?></div>
                <div class="font-16 strong mt5"><?php echo (int) get_array_value($status, 'proxy_count'); ?></div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 mb10">
            <div class="card p15 mb0 h-100">
                <div class="text-off small"><?php echo app_lang('outbound_proxy_telegram_token'); ?></div>
                <div class="font-16 strong mt5">
                    <?php if (!empty($status['token_configured'])) { ?>
                        <span class="badge bg-success"><?php echo app_lang('outbound_proxy_configured'); ?></span>
                    <?php } else { ?>
                        <span class="badge bg-warning"><?php echo app_lang('outbound_proxy_not_configured'); ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($send_order_labels) { ?>
        <div class="alert alert-info small mb15">
            <?php echo app_lang('outbound_proxy_send_order'); ?>:
            <strong><?php echo implode(' → ', $send_order_labels); ?></strong>
        </div>
    <?php } ?>

    <div class="card mb15">
        <div class="card-header clearfix">
            <strong><?php echo app_lang('outbound_proxy_list_title'); ?></strong>
            <?php echo modal_anchor(get_uri("settings/outbound_proxy_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('outbound_proxy_add'), array("class" => "btn btn-default btn-sm float-end", "title" => app_lang('outbound_proxy_add'))); ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb0">
                <thead>
                    <tr>
                        <th><?php echo app_lang('name'); ?></th>
                        <th><?php echo app_lang('outbound_proxy_supplier'); ?></th>
                        <th><?php echo app_lang('outbound_proxy_priority'); ?></th>
                        <th><?php echo app_lang('status'); ?></th>
                        <th>HTTP</th>
                        <th><?php echo app_lang('outbound_proxy_time'); ?></th>
                        <th class="text-end"><?php echo app_lang('options'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$proxy_rows) { ?>
                        <tr>
                            <td colspan="7" class="text-center text-off p20"><?php echo app_lang('outbound_proxy_empty'); ?></td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($proxy_rows as $row) {
                            $probe = get_array_value($row, 'probe', []);
                            ?>
                            <tr class="<?php echo empty($row['enabled']) ? 'text-off' : ''; ?>">
                                <td>
                                    <div class="strong"><?php echo esc($row['label']); ?></div>
                                    <code class="small"><?php echo esc($row['url_masked']); ?></code>
                                </td>
                                <td><?php echo $row['supplier'] ? esc($row['supplier']) : '—'; ?></td>
                                <td><?php echo (int) $row['priority']; ?></td>
                                <td>
                                    <?php if (!empty($row['enabled'])) { ?>
                                        <span class="badge bg-success"><?php echo app_lang('enabled'); ?></span>
                                    <?php } else { ?>
                                        <span class="badge bg-secondary"><?php echo app_lang('disabled'); ?></span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (!$probes_checked) { ?>
                                        —
                                    <?php } elseif (!empty($probe['http_code'])) { ?>
                                        <span class="badge <?php echo !empty($probe['ok']) ? 'bg-success' : 'bg-danger'; ?>"><?php echo (int) $probe['http_code']; ?></span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger">—</span>
                                    <?php } ?>
                                </td>
                                <td><?php echo $probes_checked ? ((int) get_array_value($probe, 'elapsed_ms') . ' ms') : '—'; ?></td>
                                <td class="text-end option">
                                    <?php
                                    echo modal_anchor(get_uri("settings/outbound_proxy_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array(
                                        "class" => "edit",
                                        "title" => app_lang('edit'),
                                        "data-post-id" => $row['id'],
                                    ));
                                    echo js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                                        "title" => app_lang('delete'),
                                        "class" => "delete ml10",
                                        "data-id" => $row['id'],
                                        "data-action-url" => get_uri("settings/delete_outbound_proxy"),
                                        "data-action" => "delete-confirmation",
                                        "data-success-callback" => "reloadOutboundProxyTab",
                                    ));
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb10"><?php echo app_lang('outbound_proxy_test'); ?></h5>
            <p class="text-off small"><?php echo app_lang('outbound_proxy_test_help'); ?></p>
            <?php echo js_anchor("<i data-feather='send' class='icon-16'></i> " . app_lang('outbound_proxy_test_telegram'), array(
                "class" => "btn btn-primary btn-sm",
                "id" => "outbound-proxy-test-telegram",
                "data-action-url" => get_uri("settings/test_outbound_telegram"),
            )); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    window.reloadOutboundProxyTab = function () {
        window.location.href = "<?php echo get_uri('settings/proxy'); ?>";
    };

    $(document).ready(function () {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        $("#outbound-proxy-refresh-btn").click(function () {
            var $btn = $(this);
            appLoader.show();
            $.ajax({
                url: $btn.attr("data-action-url"),
                type: 'POST',
                timeout: 120000,
                data: AppHelper.csrfHashName ? {[AppHelper.csrfHashName]: AppHelper.csrfHash} : {},
                success: function (result) {
                    appLoader.hide();
                    if (result.success) {
                        appAlert.success(result.message);
                        location.reload();
                    } else {
                        appAlert.error(result.message);
                    }
                },
                error: function () {
                    appLoader.hide();
                    appAlert.error(AppLanugage.somethingWentWrong);
                }
            });
            return false;
        });

        $("#outbound-proxy-test-telegram").click(function () {
            var $btn = $(this);
            appLoader.show();
            $.ajax({
                url: $btn.attr("data-action-url"),
                type: 'POST',
                timeout: 60000,
                data: AppHelper.csrfHashName ? {[AppHelper.csrfHashName]: AppHelper.csrfHash} : {},
                success: function (result) {
                    appLoader.hide();
                    if (result.success) {
                        appAlert.success(result.message);
                    } else {
                        appAlert.error(result.message);
                    }
                },
                error: function () {
                    appLoader.hide();
                    appAlert.error(AppLanugage.somethingWentWrong);
                }
            });
            return false;
        });
    });
</script>
