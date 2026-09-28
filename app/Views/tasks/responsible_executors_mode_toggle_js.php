<script type="text/javascript">
    (function () {
        if (window.initResponsibleExecutorsModeToggle) {
            return;
        }

        window.initResponsibleExecutorsModeToggle = function (options) {
            var instanceId = options.instanceId;
            var reloadType = options.reloadType || "table";
            var $wrapper = $(options.wrapperSelector);
            if (!$wrapper.length || !instanceId) {
                return;
            }

            function getModeFromParams() {
                var settings = window.InstanceCollection && window.InstanceCollection[instanceId];
                var mode = settings && settings.filterParams ? settings.filterParams.responsible_executors_mode : "";
                return mode === "or" ? "or" : "and";
            }

            function ensureModeOnParams(mode) {
                if (!window.InstanceCollection || !window.InstanceCollection[instanceId]) {
                    return;
                }
                window.InstanceCollection[instanceId].filterParams = window.InstanceCollection[instanceId].filterParams || {};
                window.InstanceCollection[instanceId].filterParams.responsible_executors_mode = mode === "or" ? "or" : "and";
            }

            function syncToggleFromParams() {
                var mode = getModeFromParams();
                $wrapper.find(".task-filter-mode-btn").removeClass("is-active");
                $wrapper.find('.task-filter-mode-btn[data-mode="' + mode + '"]').addClass("is-active");
            }

            function reloadInstance() {
                var $instance = $("#" + instanceId);
                if (!$instance.length) {
                    return;
                }
                if (reloadType === "filters") {
                    $instance.appFilters({reload: true});
                } else {
                    $instance.appTable({reload: true});
                }
            }

            if ($wrapper.find(".task-responsible-executors-mode").length) {
                syncToggleFromParams();
                return;
            }

            var $responsibleBox = $wrapper.find("[data-name='responsible_user_id']").first().closest(".filter-item-box");
            var $executorsBox = $wrapper.find("[data-name='executors_user_id']").first().closest(".filter-item-box");
            if (!$responsibleBox.length || !$executorsBox.length) {
                return;
            }

            var andLabel = <?php echo json_encode(app_lang("filter_mode_and")); ?>;
            var orLabel = <?php echo json_encode(app_lang("filter_mode_or")); ?>;
            var title = <?php echo json_encode(app_lang("responsible_executors_mode_help")); ?>;

            var $toggle = $(
                '<div class="filter-item-box task-responsible-executors-mode" title="' + $("<div>").text(title).html() + '">'
                + '<div class="task-filter-mode-switch" role="group" aria-label="' + $("<div>").text(title).html() + '">'
                + '<button type="button" class="task-filter-mode-btn" data-mode="and">' + andLabel + '</button>'
                + '<button type="button" class="task-filter-mode-btn" data-mode="or">' + orLabel + '</button>'
                + '</div>'
                + '</div>'
            );

            $responsibleBox.after($toggle);

            ensureModeOnParams(getModeFromParams());
            syncToggleFromParams();

            $toggle.on("click", ".task-filter-mode-btn", function () {
                var mode = $(this).attr("data-mode") === "or" ? "or" : "and";
                ensureModeOnParams(mode);
                syncToggleFromParams();
                reloadInstance();
            });

            window.syncResponsibleExecutorsModeToggle = function (id) {
                if (id && id !== instanceId) {
                    return;
                }
                var uiMode = $wrapper.find(".task-filter-mode-btn.is-active").attr("data-mode");
                if (uiMode === "or" || uiMode === "and") {
                    ensureModeOnParams(uiMode);
                } else {
                    ensureModeOnParams(getModeFromParams());
                }
                syncToggleFromParams();
            };
        };
    })();
</script>

<style>
    .task-responsible-executors-mode {
        display: inline-flex;
        align-items: center;
        vertical-align: middle;
        height: 38px;
    }

    .task-filter-mode-switch {
        display: inline-flex;
        align-items: stretch;
        height: 38px;
        padding: 3px;
        border: 1px solid #e8ecf1;
        border-radius: 8px;
        background: #f4f6f8;
        box-shadow: none;
        gap: 2px;
    }

    .task-filter-mode-btn {
        appearance: none;
        border: 0;
        background: transparent;
        color: #8a93a2;
        min-width: 44px;
        padding: 0 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.02em;
        line-height: 1;
        cursor: pointer;
        transition: background .15s ease, color .15s ease, box-shadow .15s ease;
    }

    .task-filter-mode-btn:hover {
        color: #4b5563;
        background: rgba(255, 255, 255, 0.55);
    }

    .task-filter-mode-btn.is-active {
        background: #fff;
        color: #374151;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08);
    }

    .task-filter-mode-btn:focus {
        outline: none;
    }

    .task-filter-mode-btn:focus-visible {
        box-shadow: 0 0 0 2px rgba(102, 144, 244, 0.25);
    }
</style>
